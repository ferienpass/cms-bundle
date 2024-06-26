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

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\FilesModel;
use Ferienpass\CmsBundle\Controller\AbstractController;
use Ferienpass\CoreBundle\Entity\Offer\BaseOffer;
use Ferienpass\CoreBundle\Entity\Offer\OfferInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class OfferDetailsController extends AbstractController
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private readonly string $projectDir)
    {
    }

    public function __invoke(OfferInterface $offer, Request $request): Response
    {
        // $members = $offer->getMemberAssociations()->map(fn (OfferMemberAssociation $a) => $a->getMember());

        if ($request->query->has('file')) {
            $this->handleDownload($request, $offer);
        }

        $this->tagResponse('offer.'.$offer->getId());

        return $this->render('@FerienpassCms/fragment/offer_details.html.twig', [
            'offer' => $offer,
            'attachments' => $this->getAttachments($offer),
            'members' => [],
        ]);
    }

    private function getAttachments(OfferInterface $offer): array
    {
        $files = [];
        /** @var BaseOffer $offer */
        if ($offer->requiresApplication() && $offer->getAgreementLetter() && !$offer->isDeferAgreementLetter()) {
            $files[] = $offer->getAgreementLetter();
        }

        if ($offer->getDownloads()) {
            $files = array_merge($files, $offer->getDownloads());
        }

        return $files;
    }

    private function handleDownload(Request $request, OfferInterface $offer): void
    {
        $download = $request->query->get('file');
        $file = FilesModel::findByPath($download);
        if (null === $file || !\in_array($file->uuid, $this->getAttachments($offer), true)) {
            throw new PageNotFoundException('Invalid download');
        }

        // Cannot return response because it
        // cannot be handled through fragment renderer.
        throw new ResponseException($this->file($this->projectDir.'/'.$file->path));
    }
}
