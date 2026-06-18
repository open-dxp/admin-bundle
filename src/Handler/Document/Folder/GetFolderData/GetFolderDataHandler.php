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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Folder\GetFolderData;

use OpenDxp\Bundle\AdminBundle\Enricher\Document\DocumentMetaEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Document\PropertiesEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Document\TranslationEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\AdminStyleEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\UserNamesEnricher;
use OpenDxp\Model\Document\Folder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetFolderDataHandler
{
    public function __construct(
        private readonly DocumentMetaEnricher $documentMetaEnricher,
        private readonly AdminStyleEnricher $adminStyleEnricher,
        private readonly UserNamesEnricher $userNamesEnricher,
        private readonly PropertiesEnricher $propertiesEnricher,
        private readonly TranslationEnricher $translationEnricher,
    ) {}

    public function __invoke(GetFolderDataPayload $payload): GetFolderDataResult
    {
        $folder = Folder::getById($payload->id);
        if (!$folder) {
            throw new NotFoundHttpException('Folder not found');
        }

        $folder = clone $folder;
        $folder->setParent(null);

        $data = $folder->getObjectVars();
        $data['locked'] = $folder->isLocked();

        $this->documentMetaEnricher->enrich($folder, $data);
        $this->adminStyleEnricher->forEditor($folder, $data);
        $this->userNamesEnricher->enrich($folder, $data);
        $this->propertiesEnricher->enrich($folder, $data);
        $this->translationEnricher->enrich($folder, $data);

        return new GetFolderDataResult(folder: $folder, data: $data);
    }
}
