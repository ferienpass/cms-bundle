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
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Error401 extends AbstractController
{
    public function __construct(private readonly EditionRepository $editions)
    {
    }

    public function __invoke(Request $request): Response
    {
        // If no Ferienpass active, show error message, otherwise forward to sign-in fragment
        if ($this->editions->count([]) && null === $this->editions->findOneToShow()) {
            return $this->render('@FerienpassCms/fragment/error401.html.twig');
        }

        return $this->forward(SignInController::class);
    }
}
