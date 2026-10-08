<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Application\Admin;

use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Model\User;
use OpenDxp\Security\User\User as UserProxy;

/**
 * Has no admin user logged in.
 */
final class MockAdminUserContext implements AdminUserContextInterface
{
    public function getAdminUser(): ?User
    {
        return null;
    }

    public function getAdminUserProxy(): ?UserProxy
    {
        return null;
    }
}
