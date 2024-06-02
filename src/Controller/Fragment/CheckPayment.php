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

use Contao\CoreBundle\Controller\AbstractController;
use Ferienpass\CoreBundle\Repository\PaymentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class CheckPayment extends AbstractController
{
    public function __construct(private readonly PaymentRepository $payments)
    {
    }

    public function __invoke(Request $request, Session $session): Response
    {
        $paymentId = $session->get('fp.processPayment');
        $payment = $this->payments->find($paymentId);

        return $this->render('@FerienpassCms/fragment/check_payment.html.twig', ['payment' => $payment]);
    }
}
