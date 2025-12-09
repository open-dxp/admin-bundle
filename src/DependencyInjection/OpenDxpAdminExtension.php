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

namespace OpenDxp\Bundle\AdminBundle\DependencyInjection;

use OpenDxp\Bundle\CoreBundle\DependencyInjection\ConfigurationHelper;
use OpenDxp\Config\LocationAwareConfigRepository;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * @internal
 */
final class OpenDxpAdminExtension extends Extension implements PrependExtensionInterface
{
    public const string PARAM_DATAOBJECTS_NOTES_EVENTS_TYPES = 'opendxp_admin.dataObjects.notes_events.types';

    public const string PARAM_ASSETS_NOTES_EVENTS_TYPES = 'opendxp_admin.assets.notes_events.types';

    public const string PARAM_DOCUMENTS_NOTES_EVENTS_TYPES = 'opendxp_admin.documents.notes_events.types';

    #[\Override]
    public function getAlias(): string
    {
        return 'opendxp_admin';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );

        $loader->load('services.yaml');
        $loader->load('security_services.yaml');
        $loader->load('event_listeners.yaml');
        $loader->load('export.yaml');

        //Set Config for GDPR data providers to container parameters
        $container->setParameter('opendxp.gdpr-data-extrator.dataobjects', $config['gdpr_data_extractor']['dataObjects']);
        $container->setParameter('opendxp.gdpr-data-extrator.assets', $config['gdpr_data_extractor']['assets']);

        //Set Config for Notes/Events Types to container parameters
        $container->setParameter(self::PARAM_DATAOBJECTS_NOTES_EVENTS_TYPES, $config['objects']['notes_events']['types']);
        $container->setParameter(self::PARAM_ASSETS_NOTES_EVENTS_TYPES, $config['assets']['notes_events']['types']);
        $container->setParameter(self::PARAM_DOCUMENTS_NOTES_EVENTS_TYPES, $config['documents']['notes_events']['types']);
        $container->setParameter('opendxp_admin.csrf_protection.excluded_routes', $config['csrf_protection']['excluded_routes']);
        $container->setParameter('opendxp_admin.admin_languages', $config['admin_languages']);
        $container->setParameter('opendxp_admin.custom_admin_path_identifier', $config['custom_admin_path_identifier']);
        $container->setParameter('opendxp_admin.custom_admin_route_name', $config['custom_admin_route_name']);
        $container->setParameter('opendxp_admin.user', $config['user']);

        $container->setParameter('opendxp_admin.config', $config);
        $container->setParameter('opendxp_admin.translations.path', $config['translations']['path']);
    }

    public function prepend(ContainerBuilder $container): void
    {
        LocationAwareConfigRepository::loadSymfonyConfigFiles($container, 'opendxp_admin', 'admin_system_settings');

        $builds = [
            'opendxpAdmin' => realpath(__DIR__ . '/../../public/build/admin'),
            'opendxpAdminImageEditor' => realpath(__DIR__ . '/../../public/build/imageEditor'),
        ];

        $container->prependExtensionConfig('webpack_encore', [
            'output_path' => false,
            'builds' => $builds,
        ]);

        // set firewall settings to container parameters
        if (!$container->hasParameter('opendxp_admin_bundle.firewall_settings')) {
            $containerConfig = ConfigurationHelper::getConfigNodeFromSymfonyTree($container, 'opendxp_admin');
            $container->setParameter('opendxp_admin_bundle.firewall_settings', $containerConfig['security_firewall']);
        }

        if ($container->has('security.event_dispatcher.opendxp_admin')) {
            $loader = new YamlFileLoader(
                $container,
                new FileLocator(__DIR__ . '/../../config')
            );

            $loader->load('logout_listener.yaml');
        }
    }
}
