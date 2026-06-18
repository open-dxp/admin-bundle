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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObjectPreviewUrl;

use OpenDxp\Bundle\AdminBundle\Service\Element\SessionService;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\PreviewGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetDataObjectPreviewUrlHandler
{
    public function __construct(
        private readonly PreviewGeneratorInterface $defaultPreviewGenerator,
        private readonly SessionService $sessionService,
    ) {}

    public function __invoke(GetDataObjectPreviewUrlPayload $payload): string
    {
        $object = $this->sessionService->getObject('object', $payload->id);

        if (!$object instanceof DataObject\Concrete) {
            throw new NotFoundHttpException(sprintf('Expected an object of type "%s", got "%s"', DataObject\Concrete::class, get_debug_type($object)));
        }

        $queryParams = $payload->queryParams;
        $url = null;
        if ($previewService = $object->getClass()->getPreviewGenerator()) {
            $url = $previewService->generatePreviewUrl($object, ['preview' => true, ...$queryParams]);
        } elseif ($object->getClass()->getLinkGenerator()) {
            $parameters = [
                'preview' => true,
            ];

            $url = $this->defaultPreviewGenerator->generatePreviewUrl($object, [...$parameters, ...$queryParams]);
        }

        if (!$url) {
            throw new NotFoundHttpException('Cannot render preview due to empty URL');
        }

        // replace all remaining % signs
        $url = str_replace('%', '%25', $url);

        $urlParts = parse_url($url);

        $redirectParameters = array_filter([
            'opendxp_object_preview' => $object->getId(),
            'site' => $queryParams[PreviewGeneratorInterface::PARAMETER_SITE] ?? null,
            'dc' => time(),
        ]);

        return $urlParts['path'] . '?' . http_build_query($redirectParameters) . (isset($urlParts['query']) ? '&' . $urlParts['query'] : '');
    }
}
