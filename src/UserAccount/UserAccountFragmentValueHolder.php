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

namespace Ferienpass\CmsBundle\UserAccount;

class UserAccountFragmentValueHolder
{
    public function __construct(private readonly string $key, private readonly string $alias, private readonly string $icon)
    {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }
}
