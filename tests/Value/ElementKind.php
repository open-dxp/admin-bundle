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

namespace OpenDxp\Bundle\AdminBundle\Tests\Value;

use Closure;
use OpenDxp\Bundle\AdminBundle\Tests\Factory\InheritanceFactory;
use OpenDxp\Model\Element\AbstractElement;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\User\UserRole;
use OpenDxp\Test\Factory\AbstractElementFactory;
use OpenDxp\Test\Factory\AbstractUserRoleFactory;
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;

/**
 * An element kind has its own tree in the admin, its own permission and its own kind of workspace.
 */
final readonly class ElementKind
{
    /**
     * @param AbstractElementFactory<AbstractElement> $folders
     * @param Closure(string): AbstractElementFactory<AbstractElement> $leaves
     * @param Closure(
     *     AbstractUserRoleFactory<UserRole>,
     *     ElementInterface,
     *     string...
     * ): AbstractUserRoleFactory<UserRole> $workspace
     */
    private function __construct(
        public string $name,
        public string $permission,
        private AbstractElementFactory $folders,
        private Closure $leaves,
        private Closure $workspace,
    ) {
    }

    public static function asset(): self
    {
        return new self(
            name: 'asset',
            permission: 'assets',
            folders: AssetFolderFactory::new(),
            leaves: static fn (string $key): AssetImageFactory => AssetImageFactory::new()
                ->with(['filename' => sprintf('%s.jpg', $key)]),
            workspace: static fn (
                AbstractUserRoleFactory $owner,
                ElementInterface $element,
                string ...$permissions,
            ): AbstractUserRoleFactory => $owner->withAssetWorkspace($element, ...$permissions),
        );
    }

    public static function document(): self
    {
        return new self(
            name: 'document',
            permission: 'documents',
            folders: DocumentFolderFactory::new(),
            leaves: static fn (string $key): DocumentPageFactory => DocumentPageFactory::new()
                ->with(['key' => $key]),
            workspace: static fn (
                AbstractUserRoleFactory $owner,
                ElementInterface $element,
                string ...$permissions,
            ): AbstractUserRoleFactory => $owner->withDocumentWorkspace($element, ...$permissions),
        );
    }

    public static function object(): self
    {
        return new self(
            name: 'object',
            permission: 'objects',
            folders: DataObjectFolderFactory::new(),
            leaves: static fn (string $key): InheritanceFactory => InheritanceFactory::new()
                ->with(['key' => $key]),
            workspace: static fn (
                AbstractUserRoleFactory $owner,
                ElementInterface $element,
                string ...$permissions,
            ): AbstractUserRoleFactory => $owner->withObjectWorkspace($element, ...$permissions),
        );
    }

    /**
     * @return AbstractElementFactory<AbstractElement>
     */
    public function folder(string $key): AbstractElementFactory
    {
        return $this->folders->with(['key' => $key]);
    }

    /**
     * @return AbstractElementFactory<AbstractElement>
     */
    public function leaf(string $key): AbstractElementFactory
    {
        return ($this->leaves)($key);
    }

    /**
     * An open workspace lists and shows its element. A closed one denies every permission.
     *
     * @template T of AbstractUserRoleFactory<UserRole>
     *
     * @param T $owner
     * @param list<ElementInterface> $open
     * @param list<ElementInterface> $closed
     *
     * @return T
     */
    public function withWorkspaces(AbstractUserRoleFactory $owner, array $open, array $closed): AbstractUserRoleFactory
    {
        foreach ($open as $element) {
            $owner = ($this->workspace)($owner, $element, 'list', 'view');
        }

        foreach ($closed as $element) {
            $owner = ($this->workspace)($owner, $element);
        }

        return $owner;
    }
}
