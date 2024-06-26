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

use Doctrine\Common\Collections\Collection;
use Ferienpass\CmsBundle\Form\SimpleType\ContaoRequestTokenType;
use Ferienpass\CoreBundle\ApplicationSystem\ApplicationSystems;
use Ferienpass\CoreBundle\Entity\Attendance;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Facade\AttendanceFacade;
use Ferienpass\CoreBundle\Repository\AttendanceRepository;
use Ferienpass\CoreBundle\Repository\EditionTaskRepository;
use Ferienpass\CoreBundle\Ux\Flash;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplicationListController extends AbstractController
{
    public function __construct(private readonly ApplicationSystems $applicationSystems, private readonly AttendanceFacade $attendanceFacade, private readonly AttendanceRepository $attendances, private readonly EditionTaskRepository $periods)
    {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        /** @var Collection<int, Attendance> $attendances */
        $attendances = $this->attendances->createQueryBuilder('a')
            ->innerJoin('a.participant', 'p')
            ->innerJoin('a.offer', 'o')
            ->leftJoin('o.dates', 'd')
            ->orderBy('d.begin')
            ->where('p.user = :member')
            ->setParameter('member', $user->getId())
            ->getQuery()
            ->getResult()
        ;

        $forms = iterator_to_array($this->withdrawForms($attendances), true);
        foreach ($forms as $form) {
            if ($response = $this->handleWithdraw($form, $request)) {
                return $response;
            }
        }

        $prioritizeForms = iterator_to_array($this->prioritizeForms($attendances), true);
        foreach ($prioritizeForms as $form) {
            if ($response = $this->handlePrioritize($form, $request)) {
                return $response;
            }
        }

        $applicationSystems = [];
        foreach ($attendances as $attendance) {
            $applicationSystems[$attendance->getId() ?? 0] = $this->applicationSystems->findApplicationSystem($attendance->getOffer());
        }

        $payDays = $this->periods->currentPayDays();

        return $this->render('@FerienpassCms/fragment/application_list.html.twig', [
            'attendances' => $attendances,
            'withdraw' => array_map(fn (FormInterface $form) => $form->createView(), $forms),
            'prioritize' => array_map(fn (FormInterface $form) => $form->createView(), $prioritizeForms),
            'applicationSystems' => $applicationSystems,
            'payDays' => $payDays,
        ]);
    }

    /**
     * @param iterable<int, Attendance> $attendances
     */
    private function withdrawForms(iterable $attendances): \Generator
    {
        $now = new \DateTimeImmutable();

        foreach ($attendances as $attendance) {
            if ($attendance->isWithdrawn()) {
                continue;
            }

            $dates = $attendance->getOffer()->getDates();
            if ($dates->isEmpty() || $now > $dates->first()->getBegin()) {
                continue;
            }

            $deadline = $attendance->getOffer()->getApplicationDeadline();
            if ($deadline && $now >= $deadline) {
                continue;
            }

            yield $attendance->getId() => $this->container->get('form.factory')->createNamed('withdraw'.($attendance->getId() ?? ''))
                ->add('submit', SubmitType::class, ['label' => 'Abmelden'])
                ->add('id', HiddenType::class, ['data' => $attendance->getId()])
                ->add('requestToken', ContaoRequestTokenType::class)
            ;
        }
    }

    /**
     * @param iterable<int, Attendance> $attendances
     */
    private function prioritizeForms(iterable $attendances): \Generator
    {
        foreach ($attendances as $attendance) {
            if (!$attendance->isWaiting()) {
                continue;
            }

            if (1 === $attendance->getUserPriority()) {
                continue;
            }

            yield $attendance->getId() => $this->container->get('form.factory')->createNamed('prioritize'.($attendance->getId() ?? ''))
                ->add('submit', SubmitType::class, ['label' => 'Priorität erhöhen'])
                ->add('id', HiddenType::class, ['data' => $attendance->getId()])
                ->add('requestToken', ContaoRequestTokenType::class)
            ;
        }
    }

    private function handleWithdraw(FormInterface $form, Request $request): ?Response
    {
        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return null;
        }

        $attendance = $this->attendances->find($form->get('id')->getData());
        if (!$attendance instanceof Attendance) {
            return $this->redirectToRoute($request->attributes->get('_route'));
        }

        $applicationSystem = $this->applicationSystems->findApplicationSystem($attendance->getOffer());
        if (null === $applicationSystem) {
            $this->addFlash(...Flash::error()->text('Zurzeit sind keine Anmeldungen möglich')->create());

            return $this->redirectToRoute($request->attributes->get('_route'));
        }

        $this->denyAccessUnlessGranted('withdraw', $attendance);

        $this->attendanceFacade->delete($attendance);

        $this->addFlash(...Flash::confirmation()->text('Die Anmeldung wurde erfolgreich zurückgezogen')->create());

        return $this->redirectToRoute($request->attributes->get('_route'));
    }

    private function handlePrioritize(FormInterface $form, Request $request): ?Response
    {
        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return null;
        }

        $attendance = $this->attendances->find($form->get('id')->getData());
        if (!$attendance instanceof Attendance || !$attendance->isWaiting()) {
            return $this->redirectToRoute($request->attributes->get('_route'));
        }

        $this->attendanceFacade->increasePriority($attendance);

        return $this->redirectToRoute($request->attributes->get('_route'));
    }
}
