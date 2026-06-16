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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef;

use OpenDxp\Model\DataObject;

final class GetTextLayoutPreviewHandler
{
    public function __invoke(
        string $objPath,
        ?string $className,
        ?string $renderingData,
        ?string $renderingClass,
        ?string $html,
    ): GetTextLayoutPreviewResult {
        $fqClassName = '\\OpenDxp\\Model\\DataObject\\' . $className;
        $obj = DataObject::getByPath($objPath) ?? new $fqClassName();

        $textLayout = new DataObject\ClassDefinition\Layout\Text();
        $textLayout->setName('textLayoutPreview' . $className);

        $context = [
            'data' => $renderingData,
        ];

        if ($renderingClass) {
            $textLayout->setRenderingClass($renderingClass);
            $textLayout->setRenderingData($renderingData);
        }

        if ($html) {
            $textLayout->setHtml($html);
        }

        $renderedHtml = $textLayout->enrichLayoutDefinition($obj, $context)->getHtml();

        $content =
            "<html>\n" .
            "<head>\n" .
            '<style type="text/css">' . "\n" .
            file_get_contents(OPENDXP_WEB_ROOT . '/bundles/opendxpadmin/css/admin.css') .
            "</style>\n" .
            "</head>\n\n" .
            "<body class='objectlayout_element_text'>\n" .
            $renderedHtml .
            "\n\n</body>\n" .
            "</html>\n";

        return new GetTextLayoutPreviewResult(content: $content);
    }
}
