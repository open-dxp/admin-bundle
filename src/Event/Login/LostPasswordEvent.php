<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Event\Login;

use OpenDxp\Event\Traits\ResponseAwareTrait;
use OpenDxp\Model\User;
use Symfony\Contracts\EventDispatcher\Event;

class LostPasswordEvent extends Event
{
    use ResponseAwareTrait;

    protected bool $sendMail = true;

    public function __construct(protected User $user, protected string $loginUrl)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getLoginUrl(): string
    {
        return $this->loginUrl;
    }

    /**
     * Determines if lost password mail should be sent
     */
    public function getSendMail(): bool
    {
        return $this->sendMail;
    }

    /**
     * Sets flag whether to send lost password mail or not
     *
     *
     * @return $this
     */
    public function setSendMail(bool $sendMail): static
    {
        $this->sendMail = $sendMail;

        return $this;
    }
}
