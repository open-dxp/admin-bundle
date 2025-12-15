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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Command;

use Exception;
use MatthiasMullie\Minify\JS;
use OpenDxp\Console\AbstractCommand;
use OpenDxp\Logger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
#[AsCommand('opendxp:extjs')]
class ExtJSCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setHidden(true)
            ->setDescription('Regenerate minified ext-js file')
            ->addArgument(
                'src',
                InputOption::VALUE_REQUIRED,
                'manifest file'
            )
            ->addArgument(
                'dest',
                InputOption::VALUE_REQUIRED,
                'destination file'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->newLine();

        $src = $input->getArgument('src');
        $dest = $input->getArgument('dest');

        if (!$src) {
            $src = 'dev/open-dxp/admin-bundle/public/extjs/js/opendxp-ext-all.json';
        }

        if (!$dest) {
            $dest = 'dev/open-dxp/admin-bundle/public/extjs/js/ext-all';
        }

        $absoluteManifest = getcwd() . '/' . $src;

        $bootstrapFile = getcwd() . '/dev/open-dxp/admin-bundle/public/extjs/js/bootstrap-ext-all.js';
        $bootstrap = file_get_contents($bootstrapFile);
        if (!$bootstrap) {
            throw new Exception('bootstrap file not found');
        }

        $scriptContents = $bootstrap . "\n\n";
        $scriptContentsMinified = $bootstrap . "\n\n";

        if (is_file($absoluteManifest)) {
            $manifestContents = file_get_contents($absoluteManifest);
            $manifestContents = json_decode($manifestContents, true);

            $loadOrder = $manifestContents['loadOrder'];

            $count = 0;

            // build dependencies
            $main = $loadOrder[count($loadOrder) - 1];
            $list = [
                $main['idx'] => $main,
            ];

            $this->populate($loadOrder, $list, $main);
            ksort($list);

            // replace this with loadOrder if we want to load the entire list
            foreach ($loadOrder as $loadOrderItem) {
                $count++;
                $relativePath = $loadOrderItem['path'];

                $fullPath = OPENDXP_WEB_ROOT . $relativePath;

                if (is_file($fullPath)) {
                    $includeContents = file_get_contents($fullPath);

                    $minify = new JS($includeContents);
                    $includeContentsMinfified = $minify->minify();
                    $includeContentsMinfified .= "\r\n;\r\n";
                    $scriptContentsMinified .= $includeContentsMinfified;

                    $includeContents .= "\r\n;\r\n";
                    $scriptContents .= $includeContents;
                } else {
                    throw new Exception('file does not exist: ' . $fullPath);
                }
            }
        } else {
            throw new Exception('manifest does not exist: ' . $absoluteManifest);
        }

        $scriptPath = getcwd() . '/' . $dest;
        file_put_contents($scriptPath . '.js', $scriptContentsMinified);
        file_put_contents($scriptPath . '-debug.js', $scriptContents);

        $io->writeln('writing to ' . $scriptPath);

        $io->success('Done');

        return 0;
    }

    public function populate(array $loadOrder, array &$list, array $item): void
    {
        $depth = count(debug_backtrace());
        if ($depth > 100) {
            Logger::error('Depth: ' . $depth);
        }

        if (is_array($item['requires'])) {
            foreach ($item['requires'] as $itemId) {
                if (isset($list[$itemId])) {
                    continue;
                }
                $subItem = $loadOrder[$itemId];
                $list[$itemId] = $subItem;
                $this->populate($loadOrder, $list, $subItem);
            }
        }

        if (is_array($item['uses'])) {
            foreach ($item['uses'] as $itemId) {
                if (isset($list[$itemId])) {
                    continue;
                }
                $subItem = $loadOrder[$itemId];
                $list[$itemId] = $subItem;
                $this->populate($loadOrder, $list, $subItem);
            }
        }
    }
}
