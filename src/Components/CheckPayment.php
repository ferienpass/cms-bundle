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

use Ferienpass\CoreBundle\Entity\Payment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(route: 'live_component_cms')]
class CheckPayment extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public Payment $payment;

    #[LiveProp]
    public int $i = 0;

    #[LiveAction]
    public function check(): void
    {
        if ($this->i > 9) {
            return;
        }

        ++$this->i;
    }
}
