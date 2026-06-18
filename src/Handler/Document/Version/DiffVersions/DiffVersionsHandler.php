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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Version\DiffVersions;

use Imagick;
use OpenDxp\Bundle\AdminBundle\Exception\Document\DocumentNotFoundException;
use OpenDxp\Config;
use OpenDxp\Document\Renderer\DocumentRendererInterface;
use OpenDxp\Image\HtmlToImage;
use OpenDxp\Model\Document;
use OpenDxp\Model\Version;
use Symfony\Component\Routing\RouterInterface;

final class DiffVersionsHandler
{
    public function __construct(
        private readonly DocumentRendererInterface $documentRenderer,
        private readonly RouterInterface $router,
    ) {}

    public function __invoke(DiffVersionsPayload $payload): DiffVersionsResult
    {
        if (!HtmlToImage::isSupported() || !class_exists('Imagick')) {
            return new DiffVersionsResult(supported: false);
        }

        $versionFrom = Version::getById($payload->from);
        $docFrom = $versionFrom?->loadData();

        if (!$docFrom instanceof Document\PageSnippet) {
            throw new DocumentNotFoundException($payload->from);
        }

        $versionTo = Version::getById($payload->to);
        $docTo = $versionTo?->loadData();

        if (!$docTo instanceof Document\PageSnippet) {
            throw new DocumentNotFoundException($payload->to);
        }

        $comparisonId = uniqid(date('Y-m-d') . '-', true);
        $tempFileTemplate = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/version-diff-tmp-' . $comparisonId . '-%s.%s';
        $fromImageFile = sprintf($tempFileTemplate, 'from', 'png');
        $toImageFile = sprintf($tempFileTemplate, 'to', 'png');
        $fromHtmlFile = sprintf($tempFileTemplate, 'from', 'html');
        $toHtmlFile = sprintf($tempFileTemplate, 'to', 'html');

        file_put_contents($fromHtmlFile, $this->documentRenderer->render($docFrom));
        file_put_contents($toHtmlFile, $this->documentRenderer->render($docTo));

        $prefix = Config::getSystemConfiguration('documents')['preview_url_prefix'] ?: $payload->schemeAndHost;

        try {
            HtmlToImage::convert($prefix . $this->router->generate('opendxp_admin_document_document_diff_versions_html', ['id' => basename($fromHtmlFile)]), $fromImageFile);
            HtmlToImage::convert($prefix . $this->router->generate('opendxp_admin_document_document_diff_versions_html', ['id' => basename($toHtmlFile)]), $toImageFile);
        } finally {
            unlink($fromHtmlFile);
            unlink($toHtmlFile);
        }

        $image1 = new Imagick($fromImageFile);
        $image2 = new Imagick($toImageFile);

        if ($image1->getImageWidth() === $image2->getImageWidth() && $image1->getImageHeight() === $image2->getImageHeight()) {
            $diff = $image1->compareImages($image2, Imagick::METRIC_MEANSQUAREERROR);
            $diff[0]->setImageFormat('png');
            $image = base64_encode($diff[0]->getImageBlob());
            $diff[0]->clear();
            $diff[0]->destroy();
            $result = new DiffVersionsResult(supported: true, image: $image);
        } else {
            $result = new DiffVersionsResult(
                supported: true,
                image1: base64_encode(file_get_contents($fromImageFile)),
                image2: base64_encode(file_get_contents($toImageFile)),
            );
        }

        $image1->clear();
        $image1->destroy();
        $image2->clear();
        $image2->destroy();

        unlink($fromImageFile);
        unlink($toImageFile);

        return $result;
    }
}
