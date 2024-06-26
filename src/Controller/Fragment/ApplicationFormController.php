<?php

declare(strict_types=1);

/*
 * This file is part of the Ferienpass package.
 *
 * (c) Richard Henkenjohann <richard@ferienpass.online>
 *
 * For more information visit the project website <https://ferienpass.online>
 * or the documentation under <https://docs.ferienpass.online>.
 */

namespace Ferienpass\CmsBundle\Controller\Fragment;

use Contao\CoreBundle\OptIn\OptIn;
use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\CmsBundle\Controller\AbstractController;
use Ferienpass\CmsBundle\Form\ApplyFormParticipantType;
use Ferienpass\CmsBundle\Form\ApplyFormType;
use Ferienpass\CmsBundle\Form\SimpleType\ContaoRequestTokenType;
use Ferienpass\CoreBundle\ApplicationSystem\ApplicationSystems;
use Ferienpass\CoreBundle\Entity\AgreementLetterSignature;
use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Entity\Offer\OfferInterface;
use Ferienpass\CoreBundle\Entity\Participant\ParticipantInterface;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Facade\AttendanceFacade;
use Ferienpass\CoreBundle\Repository\AgreementLetterSignaturesRepository;
use Ferienpass\CoreBundle\Repository\AttendanceRepository;
use Ferienpass\CoreBundle\Ux\Flash;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApplicationFormController extends AbstractController
{
    public function __construct(private readonly ApplicationSystems $applicationSystems, private readonly AttendanceFacade $attendanceFacade, private readonly AttendanceRepository $attendances, private readonly ManagerRegistry $doctrine, private readonly OptIn $optIn, private readonly FormFactoryInterface $forms, private readonly AgreementLetterSignaturesRepository $signatures)
    {
    }

    public function __invoke(OfferInterface $offer, Request $request): Response
    {
        if ($request->query->has('token') && ($optInToken = $this->optIn->find($request->query->get('token'))) && $optInToken->isValid()) {
            $optInToken->confirm();

            return new RedirectResponse($this->generateUrl($request->attributes->get('_route'), ['alias' => $offer->getAlias()]));
        }

        if (!$offer->requiresApplication() || !$offer->isOnlineApplication() || $offer->isCancelled()) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $applicationSystem = $this->applicationSystems->findApplicationSystem($offer);
        if (null === $applicationSystem) {
            return $this->render('@FerienpassCms/fragment/application_form.html.twig', ['offer' => $offer]);
        }

        /** @var User|null $user */
        $user = $this->getUser();
        if ($offer->getEdition()->hasAgreementLetter() && null !== $user && null === $this->signatures->findValidForEdition($offer->getEdition(), $user)) {
            $signForm = $this->forms->createNamedBuilder('sign')->add('requestToken', ContaoRequestTokenType::class)->add('submit', SubmitType::class, ['label' => 'Unterzeichnen'])->getForm();
            $signForm->handleRequest($request);
            if ($signForm->isSubmitted() && $signForm->isValid()) {
                return $this->handleSign($user, $offer->getEdition(), $request);
            }

            /** @noinspection FormViewTemplate */
            return $this->render('@FerienpassCms/fragment/application_form.html.twig', [
                'offer' => $offer,
                'signForm' => $signForm,
            ]);
        }

        $countParticipants = $this->attendances->count(['status' => 'confirmed', 'offer' => $offer]) + $this->attendances->count(['status' => 'waitlisted', 'offer' => $offer]);
        $vacant = $offer->getMaxParticipants() > 0 ? $offer->getMaxParticipants() - $countParticipants : null;

        $allowAnonymous = (bool) $applicationSystem->getTask()?->isAllowAnonymous();
        $allowAnonymousFee = (bool) $applicationSystem->getTask()?->isAllowAnonymousFee();
        $participantForm = $this->forms->create(ApplyFormParticipantType::class, null, ['edition' => $applicationSystem->getTask()?->getEdition()]);
        $applicationForm = $this->forms->create(ApplyFormType::class, null, [
            'offer' => $offer,
            'application_system' => $applicationSystem,
        ]);

        $participantForm->handleRequest($request);
        if ($participantForm->isSubmitted() && $participantForm->isValid() && ($user || $allowAnonymous)) {
            return $this->handleSubmitParticipant($participantForm->get('participant')->getData(), $offer, $request);
        }

        $applicationForm->handleRequest($request);
        if ($applicationForm->isSubmitted() && $applicationForm->isValid() && ($user || (!$offer->getFee() && $allowAnonymous) || ($offer->getFee() && $allowAnonymousFee))) {
            return $this->handleSubmitApplications($applicationForm->get('participants')->getData(), $offer, $request);
        }

        /** @noinspection FormViewTemplate */
        return $this->render('@FerienpassCms/fragment/application_form.html.twig', [
            'offer' => $offer,
            'form' => $applicationForm,
            'newParticipant' => $participantForm,
            'applicationSystem' => $applicationSystem,
            'vacant' => null === $vacant ? null : max(0, $vacant),
        ]);
    }

    private function handleSubmitApplications(iterable $participants, OfferInterface $offer, Request $request): Response
    {
        foreach ($participants as $participant) {
            $this->attendanceFacade->create($offer, $participant);
        }

        $this->addFlash(...Flash::confirmation()->text('Die Anmeldungen wurden angenommen')->create());

        return $this->redirect($request->getUri());
    }

    private function handleSubmitParticipant(ParticipantInterface $participant, OfferInterface $offer, Request $request): Response
    {
        $this->doctrine->getManager()->persist($participant);
        $this->doctrine->getManager()->flush();

        if (null === $participant->getUser()) {
            $request->getSession()->set('participant_ids', array_unique(array_merge($request->getSession()->get('participant_ids', []), [$participant->getId()])));

            // Verify email address
            $optInToken = $this->optIn->create('apply', $participant->getEmail(), ['Participant' => [$participant->getId()]]);
            $optInToken->send(
                'Bitte bestätigen Sie Ihre E-Mail-Adresse',
                sprintf(
                    "Bitte bestätigen Sie Ihre E-Mail-Adresse für die Anmeldung beim Ferienpass\n\n\n%s",
                    $this->generateUrl($request->attributes->get('_route'), ['alias' => $offer->getAlias(), 'token' => $optInToken->getIdentifier()], UrlGeneratorInterface::ABSOLUTE_URL)
                )
            );
        }

        $this->addFlash(...Flash::confirmationModal()
            ->headline('Los geht\'s!')
            ->text('Nun können Sie loslegen und sich zu Ferienpass-Angeboten anmelden.')
            ->linkText('Zurück zum Angebot')
            ->create()
        );

        return $this->redirect($request->getUri());
    }

    private function handleSign(User $user, Edition $edition, Request $request): Response
    {
        $signature = AgreementLetterSignature::fromEdition($edition, $user);

        $this->doctrine->getManager()->persist($signature);
        $this->doctrine->getManager()->flush();

        return $this->redirect($request->getUri());
    }
}
