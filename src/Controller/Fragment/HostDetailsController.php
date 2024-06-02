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

use Doctrine\DBAL\Types\Types;
use Ferienpass\CmsBundle\Controller\AbstractController;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Ferienpass\CoreBundle\Repository\OfferRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class HostDetailsController extends AbstractController
{
    public function __construct(private readonly OfferRepositoryInterface $offers, private readonly EditionRepository $editions)
    {
    }

    public function __invoke(Host $host, Request $request): Response
    {
        $this->tagResponse('host.'.$host->getId());

        return $this->render('@FerienpassCms/fragment/host_details.html.twig', [
            'host' => $host,
            'offers' => $this->fetchOffers($host),
        ]);
    }

    private function fetchOffers(Host $host): ?array
    {
        $editions = $this->editions->findWithActiveTask('show_offers');

        $qb = $this->offers->createQueryBuilder('o')
            ->leftJoin('o.dates', 'dates')
            ->innerJoin('o.hosts', 'hosts')
            ->andWhere('hosts.id = :host')->setParameter('host', $host->getId(), Types::INTEGER)
            ->andWhere('o.edition IN (:editions)')->setParameter('editions', $editions)
            ->orderBy('dates.begin')
        ;

        return $qb->getQuery()->getResult();
    }
}
