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
use Ferienpass\CoreBundle\Entity\OfferCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

class CategoryType extends AbstractOfferFilterType
{
    public static function getName(): string
    {
        return 'category';
    }

    public function getParent(): string
    {
        return EntityType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'label' => 'Kategorie',
            'multiple' => true,
            'class' => OfferCategory::class,
            'choice_value' => fn (?OfferCategory $entity) => $entity ? $entity->getAlias() : '',
            'choice_label' => 'name',
        ]);
    }

    public function apply(QueryBuilder $qb, FormInterface $form)
    {
        if ($form->isEmpty()) {
            return;
        }

        $v = $form->getData();

        $qb->andWhere($qb->expr()->orX(...array_map(fn ($i) => ':q_'.$i.' MEMBER OF o.categories', array_keys($v->toArray()))));
        foreach ($v as $i => $cat) {
            $qb->setParameter('q_'.$i, $cat);
        }
    }

    public function getHumanReadableValue(FormInterface $form): null|string|TranslatableInterface
    {
        $value = implode(', ', array_map(fn (OfferCategory $c) => $c->getName(), $form->getData()->toArray()));

        return new TranslatableMessage('offerList.filter.category', ['value' => $value]);
    }

    public function getBlockPrefix(): string
    {
        return 'filter_category';
    }
}
