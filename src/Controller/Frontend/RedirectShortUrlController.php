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

namespace Ferienpass\CmsBundle\Controller\Frontend;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;
use Ferienpass\CoreBundle\Entity\Offer\OfferInterface;
use Ferienpass\CoreBundle\Repository\OfferRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/{id}', requirements: ['id' => '\d+'], defaults: ['_scope' => ContaoCoreBundle::SCOPE_FRONTEND])]
class RedirectShortUrlController extends AbstractController
{
    public function __invoke(int $id, OfferRepositoryInterface $offers, ContaoFramework $contaoFramework)
    {
        $contaoFramework->initialize();

        /** @var OfferInterface $offer */
        $offer = $offers->find($id);
        if (null === $offer) {
            throw new PageNotFoundException();
        }

        if (!$offer->isPublished()) {
            throw new PageNotFoundException();
        }

        if ($offer->isVariant() || $offer->hasVariants()) {
            $base = $offer->getVariantBase() ? $offer->getVariantBase()->getId() : $offer->getId();
            $edition = $offer->getEdition();

            $page = PageModel::findOneBy(['type="offer_list"', 'edition=?'], [$edition->getId()]);
            if (null !== $page) {
                return $this->redirectToRoute('tl_page.'.$page->id, ['base' => $base], Response::HTTP_MOVED_PERMANENTLY);
            }

            return $this->redirectToRoute('offer_list', ['base' => $base], Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->redirectToRoute('offer_details', ['alias' => $offer->getAlias()], Response::HTTP_MOVED_PERMANENTLY);
    }
}
