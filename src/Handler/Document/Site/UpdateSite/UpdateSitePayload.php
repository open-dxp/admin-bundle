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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Site\UpdateSite;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use OpenDxp\Tool;
use Symfony\Component\HttpFoundation\Request;

final readonly class UpdateSitePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public int $rootId,
        public array $domains,
        public string $mainDomain,
        public string $errorDocument,
        public array $localizedErrorDocuments,
        public bool $redirectToMainDomain,
        public array $requestCustomSettings,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        $domainsRaw = str_replace(' ', '', $request->request->getString('domains'));
        $domains = $domainsRaw ? explode("\n", $domainsRaw) : [];

        $localizedErrorDocuments = [];
        foreach (Tool::getValidLanguages() as $language) {
            $requestValue = $request->request->get(sprintf('errorDocument_localized_%s', $language));
            if (isset($requestValue)) {
                $localizedErrorDocuments[$language] = $requestValue;
            }
        }

        return new static(
            rootId: $request->request->getInt('id'),
            domains: $domains,
            mainDomain: $request->request->getString('mainDomain'),
            errorDocument: $request->request->getString('errorDocument'),
            localizedErrorDocuments: $localizedErrorDocuments,
            redirectToMainDomain: $request->request->getBoolean('redirectToMainDomain'),
            requestCustomSettings: $request->request->all(),
        );
    }
}
