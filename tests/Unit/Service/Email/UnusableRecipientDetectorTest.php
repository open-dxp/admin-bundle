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

it('accepts an address field a mail can be sent to', function (?string $field) {
    expect((new UnusableRecipientDetector())->isUsableAddressField($field))->toBeTrue();
})->with([
    'no field' => [null],
    'an empty field' => [''],
    'a plain address' => ['office@example.com'],
    'an address with a display name' => ['Max Muster <max@example.com>'],
    'an address with a name in parentheses' => ['max@example.com (Max Muster)'],
    'a list separated by commas' => ['a@example.com, b@example.com'],
    'a list separated by semicolons' => ['a@example.com; b@example.com'],
]);

it('refuses an address field that still holds a placeholder or a broken address', function (string $field) {
    expect((new UnusableRecipientDetector())->isUsableAddressField($field))->toBeFalse();
})->with([
    'a percent placeholder' => ['%email%'],
    'a placeholder inside a display name' => ['Max Muster <%email%>'],
    'a twig placeholder' => ['{{ email }}'],
    'a bracket placeholder' => ['[email]'],
    'a valid address next to a placeholder' => ['office@example.com, %email%'],
    'an address without a domain' => ['office@'],
]);

it('finds no unusable recipients when no document is named', function () {
    expect((new UnusableRecipientDetector())->hasUnusableRecipients(null))->toBeFalse();
});
