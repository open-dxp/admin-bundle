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

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog\GetEmailLogParams;

use Exception;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Logger;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Tool;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetEmailLogParamsHandler
{
    public function __invoke(IdQueryPayload $payload): GetEmailLogParamsResult
    {
        $emailLog = Tool\Email\Log::getById($payload->id);
        if (!$emailLog) {
            throw new NotFoundHttpException();
        }

        try {
            $params = $emailLog->getParams();
        } catch (Exception) {
            Logger::warning('Could not decode JSON param string');
            $params = [];
        }

        foreach ($params as &$entry) {
            $this->enhanceLoggingData($entry);
        }

        return new GetEmailLogParamsResult(params: $params);
    }

    private function enhanceLoggingData(?array &$data, ?array &$fullEntry = null): void
    {
        if (!is_array($data)) {
            return;
        }

        if (!empty($data['objectClass'])) {
            $class = '\\' . ltrim($data['objectClass'], '\\');
            $reflection = new ReflectionClass($class);

            if (!empty($data['objectId']) && $reflection->implementsInterface(ElementInterface::class)) {
                $obj = $class::getById($data['objectId']);
                $data['objectPath'] = is_null($obj) ? '' : $obj->getRealFullPath();

                if (stristr($class, '\\OpenDxp\\Model') === false) {
                    $niceClassName = '\\' . ltrim($reflection->getParentClass()->getName(), '\\');
                } else {
                    $niceClassName = $class;
                }

                $niceClassName = str_replace(['\\OpenDxp\\Model\\', '_'], ['', '\\'], $niceClassName);

                $tmp = explode('\\', $niceClassName);
                if (in_array($tmp[0], ['DataObject', 'Document', 'Asset'])) {
                    $data['objectClassBase'] = $tmp[0];
                    $data['objectClassSubType'] = $tmp[1];
                }
            }
        }

        foreach ($data as &$value) {
            if (!is_array($value)) {
                continue;
            }

            $this->enhanceLoggingData($value, $data);
        }

        unset($value);

        if ($data['children'] ?? false) {
            foreach ($data['children'] as $key => $entry) {
                if (is_string($key)) {
                    unset($data['children'][$key]);
                }
            }
            $data['iconCls'] = 'opendxp_icon_folder';
            $data['data'] = ['type' => 'simple', 'value' => 'Children (' . count($data['children']) . ')'];
        } else {
            if (empty($data['iconCls'])) {
                if (($data['objectClassBase'] ?? '') === 'DataObject') {
                    $fullEntry['iconCls'] = 'opendxp_icon_object';
                } elseif (($data['objectClassBase'] ?? '') === 'Asset') {
                    $data['iconCls'] = match ($data['objectClassSubType']) {
                        'Image' => 'opendxp_icon_image',
                        'Video' => 'opendxp_icon_wmv',
                        'Text' => 'opendxp_icon_txt',
                        'Document' => 'opendxp_icon_pdf',
                        default => 'opendxp_icon_asset',
                    };
                } elseif (str_starts_with($data['objectClass'] ?? '', 'Document')) {
                    $fullEntry['iconCls'] = 'opendxp_icon_' . strtolower($data['objectClassSubType']);
                } else {
                    $data['iconCls'] = 'opendxp_icon_text';
                }
            }

            $data['leaf'] = true;
        }
    }
}
