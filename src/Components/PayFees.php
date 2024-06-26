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

namespace Ferienpass\CmsBundle\Components;

use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\CmsBundle\Form\UserAddressType;
use Ferienpass\CoreBundle\Entity\Attendance;
use Ferienpass\CoreBundle\Entity\EditionTask;
use Ferienpass\CoreBundle\Entity\Participant\ParticipantInterface;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Payments\Provider\PaymentProviderInterface;
use Ferienpass\CoreBundle\Repository\AttendanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(route: 'live_component_cms')]
class PayFees extends AbstractController
{
    use ComponentToolsTrait;
    use ComponentWithFormTrait;
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp]
    public EditionTask $payDays;

    #[LiveProp(writable: true, url: new UrlMapping(as: 'step'))]
    public ?string $step = null;

    #[LiveProp(writable: true)]
    #[Assert\NotBlank]
    public array $selectedItems = [];

    public function __construct(private readonly AttendanceRepository $attendances, private readonly PaymentProviderInterface $paymentProvider)
    {
    }

    public function attendances()
    {
        $user = $this->getUser();

        return $this->attendances->createQueryBuilder('attendance')
            ->select('attendance', 'participant')
            ->innerJoin('attendance.participant', 'participant')
            ->where('participant.user = :user')
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->getResult()
        ;
    }

    public function participants(): array
    {
        $participants = array_map(fn (Attendance $a) => $a->getParticipant(), $this->attendances());

        return array_values(array_intersect_key($participants, array_unique(array_map(fn (ParticipantInterface $p) => $p->getId(), $participants))));
    }

    public function selectedAttendances(): array
    {
        $user = $this->getUser();

        return $this->attendances->createQueryBuilder('attendance')
            ->select('attendance', 'participant')
            ->innerJoin('attendance.participant', 'participant')
            ->where('participant.user = :user')
            ->setParameter('user', $user->getId())
            ->andWhere('attendance.id IN (:ids)')
            ->setParameter('ids', $this->selectedItems)
            ->getQuery()
            ->getResult()
        ;
    }

    public function totalAmount(): int
    {
        return array_sum(array_map(fn (Attendance $a) => $a->getOffer()->getFee(), $this->selectedAttendances()));
    }

    #[LiveListener('proceedToSelect')]
    public function proceedToSelect(): void
    {
        $this->step = 'select';
    }

    #[LiveListener('proceedToAddress')]
    public function proceedToAddress(): void
    {
        $this->step = 'address';
    }

    #[LiveListener('proceedToConfirm')]
    public function proceedToConfirm(): void
    {
        $this->step = 'confirm';
    }

    #[LiveAction]
    public function redirectToPay(#[CurrentUser] User $user): Response
    {
        $this->validate();

        // try {
        return $this->redirect($this->paymentProvider->initializePayment($this->selectedAttendances(), $user));
        // } catch (ClientException $e) {
        // }

        return new Response();
    }

    public function interpretRedirect(Request $request): int
    {
        return $this->paymentProvider->interpretRedirect($request);
    }

    #[LiveAction]
    public function saveAddress(EntityManagerInterface $entityManager): void
    {
        $this->submitForm();

        $entityManager->flush();

        $this->proceedToConfirm();
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(UserAddressType::class, $this->getUser());
    }
}
