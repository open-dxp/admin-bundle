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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GenerateQrCode;

use Endroid\QrCode\Builder\Builder;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GenerateQrCode\GenerateQrCodePayload;
use Endroid\QrCode\Writer\PngWriter;
use OpenDxp\Model\Document;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GenerateQrCodeHandler
{
    public function __invoke(GenerateQrCodePayload $payload): GenerateQrCodeResult
    {
        $page = Document\Page::getById($payload->id);

        if (!$page) {
            throw new NotFoundHttpException('Page not found');
        }

        $url = $page->getUrl();

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($url)
            ->size($payload->download ? 4000 : 500)
            ->build();

        $tmpFile = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/qr-code-' . uniqid('', false) . '.png';
        $result->saveToFile($tmpFile);

        return new GenerateQrCodeResult($tmpFile);
    }
}
