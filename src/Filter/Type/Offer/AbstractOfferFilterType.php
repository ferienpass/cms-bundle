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

use Ferienpass\CmsBundle\Filter\Type\FilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

abstract class AbstractOfferFilterType extends AbstractType implements FilterType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['isEmpty'] = $form->isEmpty();
        $view->vars['humanReadable'] = $this->getHumanReadableValue($form);
        $view->vars['display'] = $this->shallDisplay();
    }

    public function shallDisplay(): bool
    {
        return true;
    }

    abstract protected function getHumanReadableValue(FormInterface $form): null|string|TranslatableInterface;
}
