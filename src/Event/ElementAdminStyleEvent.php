<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Event;

use OpenDxp\Model\Element\AdminStyle;
use OpenDxp\Model\Element\ElementInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ElementAdminStyleEvent extends Event
{
    /**
     * Style needed for tree
     */
    const CONTEXT_TREE = 1;

    /**
     * Style needed for element editor
     */
    const CONTEXT_EDITOR = 2;

    /**
     * Style needed for quicksearch
     */
    const CONTEXT_SEARCH = 3;

    /**
     * ElementAdminStyleEvent constructor.
     */
    public function __construct(protected ElementInterface $element, protected AdminStyle $adminStyle, protected ?int $context = null)
    {
    }

    public function getElement(): ElementInterface
    {
        return $this->element;
    }

    public function setElement(ElementInterface $element): void
    {
        $this->element = $element;
    }

    public function getAdminStyle(): AdminStyle
    {
        return $this->adminStyle;
    }

    public function setAdminStyle(AdminStyle $adminStyle): void
    {
        $this->adminStyle = $adminStyle;
    }

    /**
     * Returns the context. e.g. CONTEXT_TREE or CONTEXT_EDITOR.
     */
    public function getContext(): ?int
    {
        return $this->context;
    }

    public function setContext(?int $context): void
    {
        $this->context = $context;
    }
}
