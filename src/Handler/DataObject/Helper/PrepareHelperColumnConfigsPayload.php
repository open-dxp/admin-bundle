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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use OpenDxp\Tool\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

final class PrepareHelperColumnConfigsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly array $columns,
        public readonly array $existingHelperColumns,
        public readonly AttributeBagInterface $helperColumnsBag,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $bag = Session::getSessionBag($request->getSession(), 'opendxp_gridconfig');

        return new static(
            columns: json_decode($request->request->getString('columns'), true) ?? [],
            existingHelperColumns: $bag->get('helpercolumns', []),
            helperColumnsBag: $bag,
        );
    }
}
