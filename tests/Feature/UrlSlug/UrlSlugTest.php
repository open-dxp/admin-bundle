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

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\UrlSlug;

use OpenDxp\Bundle\AdminBundle\Tests\Factory\SluggableFactory;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;
use Zenstruck\Browser\KernelBrowser;

/**
 * @param array<string, string> $context
 */
function formatSlug(User $user, Concrete $object, array $context, string $text): KernelBrowser
{
    return Browser::actingAs($user)
        ->post('/admin/object/format-url-slug', [
            'body' => [
                'objectId' => $object->getId(),
                'context' => json_encode($context),
                'siteId' => 0,
                'text' => $text,
            ],
        ]);
}

/**
 * @param array<string, string> $context
 */
function savedSlugs(User $user, Concrete $object, array $context): KernelBrowser
{
    $query = http_build_query([
        'objectId' => $object->getId(),
        'context' => json_encode($context),
    ]);

    return Browser::actingAs($user)
        ->visit(sprintf('/admin/object/get-url-slugs?%s', $query));
}

/**
 * @return array<string, mixed>
 */
function response(KernelBrowser $browser): array
{
    return json_decode(
        $browser->content(),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

beforeEach(function () {
    $this->admin = UserFactory::new()
        ->admin()
        ->create();
    $this->germanSlug = [
        'containerType' => 'localizedfield',
        'fieldname' => 'lslug',
        'language' => 'de',
    ];
});

it('formats the text behind the prefix', function () {
    $object = SluggableFactory::createOne();

    $browser = formatSlug($this->admin, $object, $this->germanSlug, 'Grünes Rad');

    expect(response($browser))->toMatchArray(['slug' => 'gr-nes-rad']);
});

it('hands back the saved slugs of a language', function () {
    $object = SluggableFactory::new()
        ->withLocalizedSlug('de', '/de/things/rad')
        ->create();

    $browser = savedSlugs($this->admin, $object, $this->germanSlug);

    expect(response($browser)['slugs'])->toBe([
        [
            'slug' => '/de/things/rad',
            'siteId' => 0,
            'domain' => null,
        ],
    ]);
});

it('refuses a user who may not view the object', function () {
    $user = UserFactory::createOne();
    $object = SluggableFactory::createOne();

    $browser = formatSlug($user, $object, $this->germanSlug, 'Grünes Rad');

    $browser->assertStatus(403);
});

it('rejects a context that names no slug field', function () {
    $object = SluggableFactory::createOne();
    $context = [
        'containerType' => 'object',
        'fieldname' => 'name',
    ];

    $browser = formatSlug($this->admin, $object, $context, 'Grünes Rad');

    $browser->assertStatus(400);
});
