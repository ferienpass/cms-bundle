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

namespace Ferienpass\CmsBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;

interface FilterType
{
    /**
     * Allows you to modify whether the filter shall be displayed in the form, regardless of whether the filter is applied.
     */
    public function shallDisplay(): bool;

    public static function getName(): string;

    public function apply(QueryBuilder $qb, FormInterface $form);
}
