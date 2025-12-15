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

namespace OpenDxp\Bundle\AdminBundle\Tests\Support\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Model extends \OpenDxp\Tests\Support\Helper\Model
{
    public function initializeDefinitions(): void
    {
        $this->setupOpenDxpClass_Unittest();
        $this->setupOpenDxpClass_Inheritance();
    }
}
