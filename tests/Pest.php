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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

use OpenDxp\Bundle\AdminBundle\Tests\TestCase\LoginLinkTestCase;
use OpenDxp\TestFoundation\TestCase;

pest()->extend(TestCase::class)->in(
    'Feature/Application',
    'Feature/Asset',
    'Feature/Classificationstore',
    'Feature/Document',
    'Feature/Grid',
    'Feature/Security',
    'Feature/Tree',
    'Feature/UrlSlug',
);
pest()->extend(LoginLinkTestCase::class)->in('Feature/LoginLink');
