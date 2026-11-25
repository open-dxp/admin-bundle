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

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Grid;

use ArrayObject;
use OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator\PHP;
use OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator\Text;
use OpenDxp\Test\Factory\DataObjectFolderFactory;

it('unserializes a value without creating its objects', function () {
    $operator = new PHP((object) [
        'label' => 'Unserialized',
        'mode' => 'u',
        'children' => [
            new Text((object) [
                'label' => 'Serialized',
                'textValue' => serialize(new ArrayObject(['a'])),
                'children' => [],
            ]),
        ],
    ]);

    $value = $operator->getLabeledValue(DataObjectFolderFactory::createOne())->value;

    expect($value)->not->toBeInstanceOf(ArrayObject::class);
});
