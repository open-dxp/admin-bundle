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

namespace OpenDxp\Bundle\AdminBundle\Handler\Misc\GetAvailableTemplates;

use OpenDxp\Bundle\AdminBundle\Payload\Common\EmptyPayload;
use OpenDxp\Controller\Config\ControllerDataProvider;

final class GetAvailableTemplatesHandler
{
    public function __construct(
        private readonly ControllerDataProvider $provider,
    ) {}

    public function __invoke(EmptyPayload $payload): GetAvailableTemplatesResult
    {
        $templates = $this->provider->getTemplates();

        sort($templates, SORT_NATURAL | SORT_FLAG_CASE);

        $data = array_map(static fn ($template) => [
            'path' => $template,
        ], $templates);

        return new GetAvailableTemplatesResult(data: $data);
    }
}
