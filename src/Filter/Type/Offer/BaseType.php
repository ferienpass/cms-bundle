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

namespace Ferienpass\CmsBundle\Filter\Type\Offer;

use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

class BaseType extends AbstractOfferFilterType
{
    public function __construct(#[Autowire(param: 'ferienpass.model.offer.class')] private readonly string $offerEntityClass)
    {
    }

    public static function getName(): string
    {
        return 'base';
    }

    public function getParent(): string
    {
        return EntityType::class;
    }

    public function apply(QueryBuilder $qb, FormInterface $form)
    {
        if ($form->isEmpty()) {
            return;
        }

        $k = $form->getName();
        $v = $form->getData();

        $qb
            ->andWhere($qb->expr()->orX()->add('o.id = :q_'.$k)->add('o.variantBase = :q_'.$k))
            ->setParameter('q_'.$k, $v)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'class' => $this->offerEntityClass,
            'choice_label' => 'name',
        ]);
    }

    public function shallDisplay(): bool
    {
        return false;
    }

    protected function getHumanReadableValue(FormInterface $form): null|string|TranslatableInterface
    {
        return null;
    }
}
