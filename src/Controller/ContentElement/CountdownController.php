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

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Ferienpass\CoreBundle\Entity\EditionTask;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'countdown', category: 'ferienpass')]
class CountdownController extends AbstractContentElementController
{
    public function __construct(private readonly EditionRepository $editions)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $passEdition = $this->editions->findOneClosestByTask('show_offers');
        if (null === $passEdition) {
            return new Response('Fehler. Zeitraum nicht festgelegt.');
        }

        /** @var EditionTask $editionTask */
        $editionTask = $passEdition->getTasksOfType('show_offers')->current();

        return $this->render('@FerienpassCore/ContentElement/countdown.html.twig', [
            'headline' => StringUtil::deserialize($model->headline, true)['value'] ?? '',
            'task' => $editionTask,
        ]);
    }
}
