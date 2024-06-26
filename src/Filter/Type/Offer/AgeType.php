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

use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

class AgeType extends AbstractOfferFilterType
{
    public static function getName(): string
    {
        return 'age';
    }

    public function getParent(): string
    {
        return IntegerType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'label' => 'Alter',
        ]);
    }

    public function apply(QueryBuilder $qb, FormInterface $form)
    {
        if ($form->isEmpty()) {
            return;
        }

        $k = $form->getName();
        $v = $form->getData();

        $qb
            ->andWhere($qb->expr()->andX('o.minAge IS NULL OR o.minAge = 0 OR o.minAge <= :q_'.$k, 'o.maxAge IS NULL OR o.maxAge = 0 OR o.maxAge >= :q_'.$k))
            ->setParameter('q_'.$k, $v, ParameterType::INTEGER)
        ;
    }

    public function getHumanReadableValue(FormInterface $form): null|string|TranslatableInterface
    {
        return new TranslatableMessage('offerList.filter.age', ['value' => $form->getViewData()]);
    }

    public function getBlockPrefix(): string
    {
        return 'filter_age';
    }
}
