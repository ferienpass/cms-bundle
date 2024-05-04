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

use Contao\CoreBundle\Exception\PageNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\CmsBundle\Controller\AbstractController;
use Ferienpass\CmsBundle\Form\EditParticipantType;
use Ferienpass\CmsBundle\Form\UserParticipantsType;
use Ferienpass\CoreBundle\Entity\Participant\BaseParticipant;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Repository\ParticipantRepositoryInterface;
use Ferienpass\CoreBundle\Ux\Flash;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ParticipantsController extends AbstractController
{
    public function __construct(private readonly ParticipantRepositoryInterface $repository, private readonly EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        if ($request->query->has('bearbeiten')) {
            return $this->edit($request->query->getInt('bearbeiten'), $request);
        }

        // TODO if originalParticipants.length eq 0 then add constraint {MinLength=1}
        $originalParticipants = $this->repository->findBy(['user' => $user]);
        $form = $this->createForm(UserParticipantsType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var iterable<int, BaseParticipant> $participants */
            $participants = $form->get('participants')->getData();

            foreach ($participants as $participant) {
                $this->entityManager->persist($participant);
            }

            $this->entityManager->flush();

            $this->addFlash(...Flash::confirmation()->text('Die Daten wurden erfolgreich gespeichert.')->create());

            return $this->redirectToRoute($request->attributes->get('_route'));
        }

        $this->tagResponse('participant.'.$participant->getId());

        return $this->render('@FerienpassCms/fragment/user_account/participants.html.twig', [
            'participants' => $originalParticipants,
            'form' => $form,
        ]);
    }

    private function edit(int $id, Request $request): Response
    {
        $participant = $this->repository->find($id);
        if (null === $participant) {
            throw new PageNotFoundException();
        }

        $this->denyAccessUnlessGranted('edit', $participant);

        $form = $this->createForm(EditParticipantType::class, $participant, [
            'action' => $this->generateUrl($request->attributes->get('_route'), ['alias' => 'teilnehmer', 'bearbeiten' => $id]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash(...Flash::confirmation()->text('Die Daten wurden erfolgreich gespeichert.')->create());

            return $this->redirectToRoute($request->attributes->get('_route'));
        }

        return $this->render('@FerienpassCms/fragment/user_account/participant_edit.html.twig', [
            'participant' => $participant,
            'form' => $form,
        ]);
    }
}
