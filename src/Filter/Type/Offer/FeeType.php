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
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

class FeeType extends AbstractOfferFilterType
{
    public static function getName(): string
    {
        return 'fee';
    }

    public function getParent(): string
    {
        return MoneyType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'label' => 'max. Kosten',
            'divisor' => 100,
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
            ->andWhere($qb->expr()->andX('o.fee IS NULL OR o.fee = 0 OR o.fee <= :q_'.$k))
            ->setParameter('q_'.$k, $v, ParameterType::INTEGER)
        ;
    }

    public function getHumanReadableValue(FormInterface $form): null|string|TranslatableInterface
    {
        return new TranslatableMessage('offerList.filter.fee', ['value' => number_format((float) $form->getViewData(), 2, ',', '.')]);
    }

    public function getBlockPrefix(): string
    {
        return 'filter_fee';
    }
}
