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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Page;

use OpenDxp\Bundle\AdminBundle\Payload\Document\RenderAreabrickIndexEditmodePayload;
use OpenDxp\Document\Editable\Block\BlockStateStack;
use OpenDxp\Document\Editable\EditmodeEditableDefinitionCollector;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\Document;
use OpenDxp\Templating\Renderer\EditableRenderer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class RenderAreabrickIndexEditmodeHandler
{
    public function __construct(
        private readonly BlockStateStack $blockStateStack,
        private readonly EditmodeEditableDefinitionCollector $definitionCollector,
        private readonly EditableRenderer $editableRenderer,
        private readonly LocaleServiceInterface $localeService,
    ) {}

    public function __invoke(RenderAreabrickIndexEditmodePayload $payload): RenderAreabrickIndexEditmodeResult
    {
        $document = Document\PageSnippet::getById($payload->documentId);
        if (!$document) {
            throw new NotFoundHttpException();
        }

        $document = clone $document;
        $document->setEditables([]);

        $this->blockStateStack->loadArray($payload->blockStateStack);
        $this->localeService->setLocale($document->getProperty('language'));

        /** @var Document\Editable\Areablock $areablock */
        $areablock = $this->editableRenderer->getEditable($document, 'areablock', $payload->realName, $payload->areaBlockConfig, true);
        $areablock->setRealName($payload->realName);
        $areablock->setEditmode(true);
        $areablock->setDataFromEditmode($payload->areaBrickData);
        $htmlCode = trim($areablock->renderIndex($payload->index, true));

        return new RenderAreabrickIndexEditmodeResult(
            document: $document,
            editableDefinitions: $this->definitionCollector->getDefinitions(),
            htmlCode: $htmlCode,
        );
    }
}