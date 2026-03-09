<?php

$rulesFile = __DIR__ . '/.php-cs-fixer-rules-global.php';
$finderFile = __DIR__ . '/.php-cs-fixer-finder.dist.php';

if (!file_exists($finderFile)) {
    throw new RuntimeException('Finder file not found: ' . $finderFile);
}

$finder = require $finderFile;
$rules = require $rulesFile;

$rules['header_comment'] = [
    'comment_type' => 'PHPDoc',
    'header'       => 'OpenDXP' . PHP_EOL
        . PHP_EOL .
        'This source file is licensed under the GNU General Public License version 3 (GPLv3).' . PHP_EOL
        . PHP_EOL .
        'Full copyright and license information is available in' . PHP_EOL .
        'LICENSE.md which is distributed with this source code.' . PHP_EOL
        . PHP_EOL .
        '@copyright  Copyright (c) CoreShop GmbH (https://www.coreshop.com)' . PHP_EOL .
        '@copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)' . PHP_EOL .
        '@license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)'
];

$config = new PhpCsFixer\Config();
$config->setRules($rules);
$config->setFinder($finder);

return $config;