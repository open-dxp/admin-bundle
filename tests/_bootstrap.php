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

use OpenDxp\Tests\Support\Util\Autoloader;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    include __DIR__ . '/../vendor/autoload.php';
    $openDxpTestDir = __DIR__ . '/../vendor/open-dxp/opendxp/tests';
} elseif (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    include __DIR__ . '/../../../../vendor/autoload.php';
    $openDxpTestDir = __DIR__ . '/../../../../vendor/open-dxp/opendxp/tests';
} elseif (getenv('OPENDXP_PROJECT_ROOT') != '' && file_exists(getenv('OPENDXP_PROJECT_ROOT') . '/vendor/autoload.php')) {
    include getenv('OPENDXP_PROJECT_ROOT') . '/vendor/autoload.php';
    $openDxpTestDir = getenv('OPENDXP_PROJECT_ROOT') . '/vendor/open-dxp/opendxp/tests';
} elseif (getenv('OPENDXP_PROJECT_ROOT') != '') {
    throw new \Exception('Invalid OpenDXP project root "' . getenv('OPENDXP_PROJECT_ROOT') . '"');
} else {
    throw new \Exception('Unknown configuration! OpenDXP project root not found, please set env variable OPENDXP_PROJECT_ROOT.');
}

$openDxpTestsSupportDir = $openDxpTestDir . '/Support';

include $openDxpTestsSupportDir . '/Util/Autoloader.php';

\OpenDxp\Bootstrap::setProjectRoot();
\OpenDxp\Bootstrap::bootstrap();

//error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_WARNING);
Autoloader::addNamespace('OpenDxp\Tests\Support', $openDxpTestsSupportDir);
Autoloader::addNamespace('OpenDxp\Model\DataObject', OPENDXP_CLASS_DIRECTORY . '/DataObject');
Autoloader::addNamespace('OpenDxp\Bundle\AdminBundle\Tests', __DIR__);

echo __DIR__ . '/_support';

if (!defined('TESTS_PATH')) {
    define('TESTS_PATH', __DIR__);
}

if (!defined('OPENDXP_TEST')) {
    define('OPENDXP_TEST', true);
}
