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

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Document;

use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Page;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;

function convert(User $user, Page $page, string $type): void
{
    Browser::actingAs($user)
        ->put('/admin/document/convert', ['body' => ['id' => $page->getId(), 'type' => $type]]);
}

function documentType(Page $page): string
{
    return Document::getById($page->getId(), ['force' => true])->getType();
}

it('converts a document for a user who may publish it', function () {
    $page = DocumentPageFactory::createOne();
    $user = UserFactory::new()
        ->withPermissions('documents')
        ->withDocumentWorkspace($page, 'list', 'view', 'save', 'publish')
        ->create();

    convert($user, $page, 'snippet');

    expect(documentType($page))->toBe('snippet');
});

it('keeps a document of a user who may not publish it', function () {
    $page = DocumentPageFactory::createOne();
    $user = UserFactory::new()
        ->withPermissions('documents')
        ->withDocumentWorkspace($page, 'list', 'view', 'save')
        ->create();

    convert($user, $page, 'snippet');

    expect(documentType($page))->toBe('page');
});
