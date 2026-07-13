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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\CopyInfo;

use OpenDxp\Bundle\AdminBundle\Exception\DataObject\DataObjectNotFoundException;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\CopySessionGateway;
use OpenDxp\Model\DataObject;
use Symfony\Component\Routing\RouterInterface;

final class CopyInfoHandler
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly CopySessionGateway $copySession,
    ) {}

    public function __invoke(CopyInfoPayload $payload): CopyInfoResult
    {
        $transactionId = time();
        $this->copySession->startTransaction((string) $transactionId);
        $pasteJobs = [];

        if ($payload->type === 'recursive' || $payload->type === 'recursive-update-references') {
            $pasteJobs[] = [[
                'url' => $this->router->generate('opendxp_admin_dataobject_dataobject_copy'),
                'method' => 'POST',
                'params' => [
                    'sourceId' => $payload->sourceId,
                    'targetId' => $payload->targetId,
                    'type' => 'child',
                    'transactionId' => $transactionId,
                    'saveParentId' => true,
                ],
            ]];

            $object = DataObject::getById($payload->sourceId) ?? throw new DataObjectNotFoundException($payload->sourceId);
            $childIds = [];

            if ($object->hasChildren(DataObject::$types)) {
                $list = new DataObject\Listing();
                $list->setCondition('`path` LIKE ' . $list->quote($list->escapeLike($object->getRealFullPath()) . '/%'));
                $list->setOrderKey('LENGTH(`path`)', false);
                $list->setOrder('ASC');
                $list->setObjectTypes(DataObject::$types);
                $childIds = $list->loadIdList();
            }

            foreach ($childIds as $id) {
                $pasteJobs[] = [[
                    'url' => $this->router->generate('opendxp_admin_dataobject_dataobject_copy'),
                    'method' => 'POST',
                    'params' => [
                        'sourceId' => $id,
                        'targetParentId' => $payload->targetId,
                        'sourceParentId' => $payload->sourceId,
                        'type' => 'child',
                        'transactionId' => $transactionId,
                    ],
                ]];
            }

            if ($payload->type === 'recursive-update-references' && count($childIds) > 0) {
                for ($i = 0; $i < (count($childIds) + 1); $i++) {
                    $pasteJobs[] = [[
                        'url' => $this->router->generate('opendxp_admin_dataobject_dataobject_copyrewriteids'),
                        'method' => 'PUT',
                        'params' => [
                            'transactionId' => $transactionId,
                            '_dc' => uniqid('', false),
                        ],
                    ]];
                }
            }
        } elseif ($payload->type === 'child' || $payload->type === 'replace') {
            $pasteJobs[] = [[
                'url' => $this->router->generate('opendxp_admin_dataobject_dataobject_copy'),
                'method' => 'POST',
                'params' => [
                    'sourceId' => $payload->sourceId,
                    'targetId' => $payload->targetId,
                    'type' => $payload->type,
                    'transactionId' => $transactionId,
                ],
            ]];
        }

        return new CopyInfoResult($transactionId, $pasteJobs);
    }
}
