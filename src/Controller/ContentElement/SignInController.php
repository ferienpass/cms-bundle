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

namespace Ferienpass\CmsBundle\Controller\ContentElement;

use Contao\BackendTemplate;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Ferienpass\CmsBundle\Controller\Fragment\SignInController as SignInFragmentController;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsContentElement(type: 'sign_in', category: 'ferienpass')]
class SignInController extends AbstractContentElementController
{
    public function __construct(private readonly EditionRepository $editions)
    {
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['translator'] = TranslatorInterface::class;

        return $services;
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if ($this->container->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $template = new BackendTemplate('be_wildcard');

            $template->title = $this->container->get('translator')->trans('CTE.'.$this->getType().'.0', [], 'contao_modules');
            $template->wildcard = $this->container->get('translator')->trans('CTE.'.$this->getType().'.1', [], 'contao_modules');

            return new Response($template->parse());
        }

        // Hide if no edition "to show".
        if ($this->editions->count([]) > 0 && null === $this->editions->findOneToShow()) {
            // return new Response('', Response::HTTP_NO_CONTENT);
        }

        return $this->forward(SignInFragmentController::class);
    }
}
