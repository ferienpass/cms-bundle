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

namespace Ferienpass\CmsBundle\Filter;

use Doctrine\ORM\QueryBuilder as DoctrineQueryBuilder;
use Ferienpass\CmsBundle\Filter\Type\FilterType;
use Ferienpass\CmsBundle\Form\SimpleType\ContaoRequestTokenType;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

class OfferListFilter extends AbstractType
{
    /** @var array<string, TranslatableInterface> */
    private array $filtersViewData = [];

    /** @param array<string,FilterType> $filterTypes */
    private array $filterTypes = [];

    public function __construct(#[TaggedIterator('ferienpass.filter.offer_list_type', defaultIndexMethod: 'getName')] iterable $filterTypes)
    {
        $this->filterTypes = $filterTypes instanceof \Traversable ? iterator_to_array($filterTypes, true) : $this->filterTypes;
    }

    public function apply(DoctrineQueryBuilder $qb, FormInterface $form): void
    {
        foreach ($this->filterTypes as $k => $filter) {
            if (!is_a($filter, FilterType::class, true)) {
                continue;
            }

            $filter->apply($qb, $form->get($k));
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->filterTypes as $filterName => $filterType) {
            if ($builder->has($filterName)) {
                continue;
            }

            $builder->add($filterName, $filterType::class);
        }

        $builder->add('submit', SubmitType::class, ['label' => 'Filtern']);
        $builder->add('request_token', ContaoRequestTokenType::class);
    }

    public function getFilterNames(): array
    {
        return array_keys($this->filterTypes);
    }

    /**
     * Used in the template to retrieve human-readable versions of the applied filters.
     *
     * @return array<string, TranslatableInterface>
     */
    public function humanReadable(): array
    {
        return $this->filtersViewData;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('csrf_protection', false);
    }
}
