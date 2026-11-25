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

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Classificationstore;

use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetProperties\GetPropertiesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetProperties\GetPropertiesPayload;
use OpenDxp\TestFoundation\Container;

it('lists the keys of the given ids only', function () {
    $result = Container::get(GetPropertiesHandler::class)(new GetPropertiesPayload(keyIds: '["0) OR (1"]'));

    expect($result->total)->toBe(0);
});
