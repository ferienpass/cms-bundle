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
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsContentElement(type: 'hyperlink_button', category: 'links')]
class HyperlinkController extends AbstractContentElementController
{
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['translator'] = TranslatorInterface::class;

        return $services;
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if (str_starts_with($model->url, 'mailto:')) {
            $model->url = StringUtil::encodeEmail($model->url);
            $icon = 'mail';
        } else {
            $model->url = StringUtil::ampersand($model->url);
        }

        if ($this->container->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $template = new BackendTemplate('be_wildcard');

            $template->title = $this->container->get('translator')->trans('CTE.'.$this->getType().'.0', [], 'contao_modules');
            $template->wildcard = $model->linkTitle.' ⇒ '.$model->url;

            return new Response($template->parse());
        }

        [$style, $size] = explode('+', (string) $model->buttonStyle, 2);

        return $this->render('@FerienpassCms/fragment/hyperlink.html.twig', [
            'href' => $model->url,
            'style' => $style,
            'icon' => $icon ?? null,
            'size' => $size ?: 'base',
            'link' => $model->linkTitle,
            'newWindow' => $model->target ?? false,
            'linkTitle' => $model->titleText ?? null,
        ]);
    }
}
