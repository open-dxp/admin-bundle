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

namespace OpenDxp\Bundle\AdminBundle\GDPR\DataProvider;

use Doctrine\DBAL\Exception;
use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Bundle\AdminBundle\Service\GridData;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Element;
use ZipArchive;

/**
 * @internal
 */
class Assets extends Elements implements DataProviderInterface
{
    /**
     * @var bool[]
     */
    protected array $exportIds = [];

    public function __construct(protected ?array $config = null)
    {
    }

    public function getName(): string
    {
        return 'assets';
    }

    public function getJsClassName(): string
    {
        return 'opendxp.settings.gdpr.dataproviders.assets';
    }

    /**
     * Exports data of given asset as json
     */
    public function doExportData(Asset $asset): string
    {
        $this->exportIds = [];
        $this->exportIds[$asset->getId()] = true;

        // Prepare File
        $file = tempnam('/tmp', 'zip');
        $zip = new ZipArchive();
        $zip->open($file, ZipArchive::OVERWRITE);

        foreach (array_keys($this->exportIds) as $id) {
            $theAsset = Asset::getById((int) $id);
            if (!$theAsset instanceof Asset) {
                continue;
            }

            $resultItem = Exporter::exportAsset($theAsset);
            $resultItem = json_encode($resultItem);

            $zip->addFromString($asset->getFilename() . '.txt', $resultItem);

            if (!$theAsset instanceof Asset\Folder) {
                $zip->addFromString($theAsset->getFilename(), $theAsset->getData());
            }
        }

        $zip->close();

        $content = file_get_contents($file);
        unlink($file);

        return $content;
    }

    /**
     * @throws Exception
     */
    public function searchData(int $id, string $firstname, string $lastname, string $email, int $start, int $limit, ?string $sort = null): array
    {
        if (empty($id) && empty($firstname) && empty($lastname) && empty($email)) {
            return ['data' => [], 'success' => true, 'total' => 0];
        }

        //TODO: add orWhere only if field if set!
        $db = \OpenDxp\Db::get();
        $queryBuilder = $db->createQueryBuilder();
        $query = $queryBuilder
            ->select('assets.id')
            ->from('assets')
            ->leftJoin('assets', 'assets_metadata', 'metadata', 'assets.id = metadata.cid')
            ->where('assets.id = :id')
            ->setParameter('id', $id)
            ->setFirstResult($start)
            ->setMaxResults($limit);

        if (!empty($firstname)) {
            $query
                ->orWhere(
                    $queryBuilder->expr()->like('metadata.data', ':firstname')
                )
                ->setParameter('firstname', ('%'.$firstname.'%'));
        }

        if (!empty($lastname)) {
            $query
                ->orWhere(
                    $queryBuilder->expr()->like('metadata.data', ':lastname')
                )
                ->setParameter('lastname', ('%'.$lastname.'%'));
        }

        if (!empty($email)) {
            $query
                ->orWhere(
                    $queryBuilder->expr()->like('metadata.data', ':email')
                )
                ->setParameter('email', ('%'.$email.'%'));
        }

        $sortingSettings = QueryParams::extractSortingSettings(['sort' => $sort]);
        if ($sortingSettings['orderKey']) {
            // we need a special mapping for classname as this is stored in subtype column
            $sortMapping = [
                'type' => 'subtype',
            ];

            $sort = $sortingSettings['orderKey'];
            if (array_key_exists($sortingSettings['orderKey'], $sortMapping)) {
                $sort = $sortMapping[$sortingSettings['orderKey']];
            }

            $order = $sortingSettings['order'] ?? null;

            $query->orderBy($sort, $order);
        }

        $query = $query->executeQuery();

        $elements = [];
        if ($query->rowCount() > 0) {
            foreach ($query->fetchAllAssociative() as $hit) {
                $element = Element\Service::getElementById('asset', (int) $hit['id']);

                if ($element instanceof Asset) {
                    $data = GridData\Asset::getData($element);
                    $data['permissions'] = $element->getUserPermissions();
                    $elements[] = $data;
                }
            }
        }

        return ['data' => $elements, 'success' => true, 'total' => $query->rowCount()];
    }

    public function getSortPriority(): int
    {
        return 20;
    }
}
