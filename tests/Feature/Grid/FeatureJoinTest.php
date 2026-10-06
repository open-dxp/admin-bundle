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

use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Inheritance;
use Symfony\Component\EventDispatcher\EventDispatcher;

function classificationStoreJoin(int $key): string
{
    return sprintf(
        'LEFT JOIN object_classificationstore_data_inheritance cskey_teststore_1_%d ON (%s)',
        $key,
        implode(' and ', [
            sprintf('cskey_teststore_1_%d.id = object_localized_inheritance_en.id', $key),
            sprintf("cskey_teststore_1_%d.fieldname = 'teststore'", $key),
            sprintf('cskey_teststore_1_%d.groupId=1', $key),
            sprintf('cskey_teststore_1_%1$d.keyId=%1$d', $key),
            sprintf("cskey_teststore_1_%d.language = 'default'", $key),
        ]),
    );
}

it('joins and filters every classification store key a grid column shows', function () {
    $listing = new Inheritance\Listing();
    $listing->setCondition("(`path` = '/tmp' OR `path` like '/tmp/%') AND 1 = 1");
    $listing->setLimit(25);
    $listing->setGroupBy('oo_id');
    $featureJoins = [
        [
            'fieldname' => 'teststore',
            'groupId' => 1,
            'keyId' => 1,
            'language' => 'default',
        ],
        [
            'fieldname' => 'teststore',
            'groupId' => 1,
            'keyId' => 2,
            'language' => 'default',
        ],
    ];
    $gridHelper = new GridHelperService(new EventDispatcher());

    $gridHelper->addGridFeatureJoins(
        $listing,
        $featureJoins,
        ClassDefinition::getByName('inheritance'),
        [
            'featureJoins' => $featureJoins,
            'slugJoins' => [],
            'featureConditions' => [
                'cskey_teststore_1_1' => "`cskey_teststore_1_1` LIKE '%t%'",
                'cskey_teststore_1_2' => "`cskey_teststore_1_2` LIKE '%t77%'",
            ],
            'slugConditions' => [],
        ],
    );

    expect($listing->getDao()->getQueryBuilder()->getSQL())->toContain(
        'SELECT cskey_teststore_1_1.value AS cskey_teststore_1_1, cskey_teststore_1_2.value AS cskey_teststore_1_2 ',
        classificationStoreJoin(1),
        classificationStoreJoin(2),
        "HAVING `cskey_teststore_1_1` LIKE '%t%' AND `cskey_teststore_1_2` LIKE '%t77%'",
    );
});
