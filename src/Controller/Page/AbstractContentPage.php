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

namespace Ferienpass\CmsBundle\Controller\Page;

use Contao\ArticleModel;
use Ferienpass\CmsBundle\Controller\AbstractController;
use Ferienpass\CmsBundle\Fragment\FragmentReference;
use Ferienpass\CmsBundle\Page\PageBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A base class for page controllers that have content composition enabled.
 * The articles will be added automatically to the page.
 */
class AbstractContentPage extends AbstractController
{
    protected bool $protected = false;

    public function __invoke(Request $request): Response
    {
        if ($this->protected) {
            $this->denyAccessUnlessGranted('ROLE_USER');
        }

        $this->initializeContaoFramework();

        $pageModel = $request->attributes->get('pageModel');

        $pageBuilder = $this->createPageBuilder($pageModel);

        $articles = ArticleModel::findPublishedByPidAndColumn($pageModel->id, 'main');
        while (null !== $articles && $articles->next()) {
            $pageBuilder->addFragment('main', new FragmentReference('ferienpass.fragment.article', ['id' => $articles->id]));
        }

        $this->modifyPage($pageBuilder, $request);

        return $pageBuilder->getResponse();
    }

    protected function modifyPage(PageBuilder $pageBuilder, Request $request): void
    {
    }
}
