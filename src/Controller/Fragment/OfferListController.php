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
use Contao\CoreBundle\Exception\RedirectResponseException;
use Doctrine\DBAL\Types\Types;
use Ferienpass\CmsBundle\Filter\OfferListFilter;
use Ferienpass\CoreBundle\Entity\Offer\OfferInterface;
use Ferienpass\CoreBundle\Pagination\Paginator;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Ferienpass\CoreBundle\Repository\OfferRepositoryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

final class OfferListController extends AbstractController
{
    public function __construct(private readonly EditionRepository $editions, private readonly OfferRepositoryInterface $offers, private readonly OfferListFilter $filter, private readonly FormFactoryInterface $formFactory)
    {
    }

    public function __invoke(Request $request, Session $session): Response
    {
        $qb = $this->offers->createQueryBuilder('o')->where('o.state = :state_published')->setParameter('state_published', OfferInterface::STATE_PUBLISHED);
        $hasEditions = $this->editions->count([]) > 0;
        $pageModel = $request->attributes->get('pageModel');
        if ($pageModel->edition) {
            $edition = $this->editions->find($pageModel->edition);
        } else {
            $edition = $this->editions->findOneToShow($pageModel);
        }

        if ($hasEditions && null !== $edition) {
            $qb->andWhere('o.edition = :edition')->setParameter('edition', $edition->getId(), Types::INTEGER);
        }

        if ($hasEditions && (null === $edition || !$edition->isOnline())) {
            return $this->render('@FerienpassCms/fragment/offer_list.html.twig');
        }

        $qb->leftJoin('o.dates', 'dates');

        if ('Restplätze' === $pageModel->title) {
            $qb
                ->andWhere('o.onlineApplication = 1')
                ->andWhere($qb->expr()->orX($qb->expr()->isNull('o.applicationDeadline'), 'o.applicationDeadline > CURRENT_TIMESTAMP()'))
                ->leftJoin('o.attendances', 'a')
                ->addSelect('COUNT(a.id) as HIDDEN attCount')
                ->groupBy('o.id', 'a.status', 'o.maxParticipants')
                ->having('a.status = :status_confirmed')
                ->andHaving($qb->expr()->orX($qb->expr()->isNull('o.maxParticipants'), 'o.maxParticipants < 1', 'o.maxParticipants > attCount'))
                ->setParameter('status_confirmed', 'confirmed')

                ->andWhere($qb->expr()->orX()->add('dates IS NULL')->add('dates.begin >= CURRENT_TIMESTAMP()'))
            ;
        }

        $filterForm = $this->instantiateForm($request);
        $this->filter->apply($qb, $filterForm);

        $qb->orderBy('dates.begin');

        $paginator = (new Paginator($qb))->paginate($request->query->getInt('page', 1));

        if ($request->query->has('filter') && $filterForm->handleRequest($request)) {
            if ($filterForm->isSubmitted() && $filterForm->isValid()) {
                throw new RedirectResponseException($this->getFilterUrl($filterForm, $request));
            }
        }

        $this->tagResponse(array_map(fn (OfferInterface $offer) => 'offer.'.$offer->getId(), (array) $paginator->getResults()));

        return $this->render('@FerienpassCms/fragment/offer_list.html.twig', [
            'filterForm' => $filterForm,
            'edition' => $edition ?? null,
            'filter' => $filter ?? null,
            'pagination' => $paginator,
        ]);
    }

    protected function instantiateForm(Request $request): FormInterface
    {
        $filterDataFromUrl = array_filter($request->query->all(), fn (string $key) => \in_array($key, $this->filter->getFilterNames(), true), \ARRAY_FILTER_USE_KEY);

        $filterForm = $this->formFactory->create($this->filter::class);
        $filterForm->submit($filterDataFromUrl);

        return $this->formFactory->create($this->filter::class, $filterForm->getData(), ['action' => $request->getUri()]);
    }

    private function getFilterUrl(FormInterface $form, Request $request): string
    {
        $params = HeaderUtils::parseQuery((string) $request->getQueryString());

        foreach (array_keys((array) $form->getViewData()) as $attr) {
            if (!$form->has($attr) || $form->get($attr)->isEmpty()) {
                unset($params[$attr]);
                continue;
            }

            $value = $form->get($attr)->getViewData();
            $params[$attr] = $value;
        }

        // New filter settings are not compatible with pagination
        unset($params['page'], $params['filter']);

        $qs = \count($params) ? '?'.http_build_query($params) : '';

        return $request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo().$qs;
    }
}
