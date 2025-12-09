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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle;

use OpenDxp\Bundle\AdminBundle\DependencyInjection\Compiler\ContentSecurityPolicyUrlsPass;
use OpenDxp\Bundle\AdminBundle\DependencyInjection\Compiler\GDPRDataProviderPass;
use OpenDxp\Bundle\AdminBundle\DependencyInjection\Compiler\ImportExportLocatorsPass;
use OpenDxp\Bundle\AdminBundle\DependencyInjection\Compiler\SerializerPass;
use OpenDxp\Bundle\AdminBundle\DependencyInjection\Compiler\TranslatorPass;
use OpenDxp\Bundle\AdminBundle\DependencyInjection\OpenDxpAdminExtension;
use OpenDxp\Bundle\AdminBundle\GDPR\DataProvider\DataProviderInterface;
use OpenDxp\Extension\Bundle\AbstractOpenDxpBundle;
use OpenDxp\Extension\Bundle\Traits\PackageVersionTrait;
use OpenDxp\HttpKernel\Bundle\DependentBundleInterface;
use OpenDxp\HttpKernel\BundleCollection\BundleCollection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;

class OpenDxpAdminBundle extends AbstractOpenDxpBundle implements DependentBundleInterface
{
    use PackageVersionTrait;

    public function getComposerPackageName(): string
    {
        return 'open-dxp/admin-bundle';
    }

    #[\Override]
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new OpenDxpAdminExtension();
        }

        return $this->extension;
    }

    public function build(ContainerBuilder $container): void
    {
        // auto-tag GDPR data providers
        $container
            ->registerForAutoconfiguration(DataProviderInterface::class)
            ->addTag('opendxp.gdpr.data-provider');

        $container->addCompilerPass(new SerializerPass());
        $container->addCompilerPass(new GDPRDataProviderPass());
        $container->addCompilerPass(new ImportExportLocatorsPass());
        $container->addCompilerPass(new TranslatorPass());
        $container->addCompilerPass(new ContentSecurityPolicyUrlsPass());
    }

    #[\Override]
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public static function registerDependentBundles(BundleCollection $collection): void
    {
        $collection->addBundle(new WebpackEncoreBundle());
    }

    public function getInstaller(): ?Installer
    {
        return $this->container->get(Installer::class);
    }
}
