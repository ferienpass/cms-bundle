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

namespace Ferienpass\CmsBundle\Controller\Frontend\Api;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): Response
    {
        $this->container->get('contao.framework')->initialize();

        if ($request->request->has('_target_path')) {
            return new RedirectResponse(base64_decode((string) $request->request->get('_target_path'), true));
        }

        return new RedirectResponse('/');
    }
}
