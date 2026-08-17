<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Translation\BuildContentExportJobs;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class BuildContentExportJobsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly array $data,
        public readonly string $source,
        public readonly string $target,
        public readonly string $jobUrl,
        public readonly int $elementsPerJob,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $type = $request->request->get('type');
        $jobUrl = $request->request->get('job_url', $request->getBaseUrl() . '/admin/translation/' . $type . '-export');
        $data = json_decode((string) $request->request->get('data'), true);

        return new static(
            data: $data && is_array($data) ? $data : [],
            source: str_replace('_', '-', $request->request->get('source', '')),
            target: str_replace('_', '-', $request->request->get('target', '')),
            jobUrl: (string) $jobUrl,
            elementsPerJob: max(1, (int) $request->request->get('elements_per_job', 10)),
        );
    }
}
