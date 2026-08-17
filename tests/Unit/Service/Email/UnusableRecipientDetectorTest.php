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

namespace OpenDxp\Bundle\AdminBundle\Tests\Unit\Service\Email;

use OpenDxp\Bundle\AdminBundle\Service\Email\UnusableRecipientDetector;
use OpenDxp\Bundle\AdminBundle\Tests\Support\Test\UnitTestCase;

class UnusableRecipientDetectorTest extends UnitTestCase
{
    public function testUsableAddressFields(): void
    {
        $detector = new UnusableRecipientDetector();

        $addressFields = [
            'empty field contributes no recipient' => '',
            'plain address'                        => 'office@example.com',
            'address with display name'            => 'Max Muster <max@example.com>',
            'address with parenthesised name'      => 'max@example.com (Max Muster)',
            'comma separated list'                 => 'a@example.com, b@example.com',
            'semicolon separated list'             => 'a@example.com; b@example.com',
        ];

        foreach ($addressFields as $case => $addressField) {
            self::assertTrue($detector->isUsableAddressField($addressField), $case);
        }
    }

    public function testUnusableAddressFields(): void
    {
        $detector = new UnusableRecipientDetector();

        $addressFields = [
            'simple placeholder'                     => '%email%',
            'placeholder inside display name syntax' => 'Max Muster <%email%>',
            'twig style placeholder'                 => '{{ email }}',
            'bracket style placeholder'              => '[email]',
            'one valid address plus one placeholder' => 'office@example.com, %email%',
            'missing domain'                         => 'office@',
        ];

        foreach ($addressFields as $case => $addressField) {
            self::assertFalse($detector->isUsableAddressField($addressField), $case);
        }
    }

    public function testNullFieldIsUsable(): void
    {
        self::assertTrue((new UnusableRecipientDetector())->isUsableAddressField(null));
    }

    public function testDocumentIdIsOptional(): void
    {
        self::assertFalse((new UnusableRecipientDetector())->hasUnusableRecipients(null));
    }
}
