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

namespace OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator;

use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Data\Hotspotimage;
use OpenDxp\Model\Element\ElementInterface;
use stdClass;

/**
 * @internal
 */
final class AssetMetadataGetter extends AbstractOperator
{
    private string $metaField;

    private ?string $locale = null;

    public function __construct(stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->metaField = $config->metaField ?? '';
        $this->locale = $config->locale ?? null;
    }

    public function getLabeledValue(array|ElementInterface $element): stdClass
    {
        $result = new stdClass();
        $result->label = $this->label;
        $result->value = null;

        $children = $this->getChildren();

        if ($children) {
            $newChildrenResult = [];
            foreach ($children as $c) {
                $childResult = $c->getLabeledValue($element);
                $childValues = $childResult->value ?? null;
                if ($childValues && !is_array($childValues)) {
                    $childValues = [$childValues];
                }

                $newValue = null;

                if (is_array($childValues)) {
                    foreach ($childValues as $value) {
                        if (is_array($value)) {
                            $newSubValues = [];
                            foreach ($value as $subValue) {
                                $subValue = $this->getMetadata($subValue);
                                $newSubValues[] = $subValue;
                            }
                            $newValue = $newSubValues;
                        } else {
                            $newValue = $this->getMetadata($value);
                        }
                    }
                }

                $newChildrenResult[] = $newValue;
            }
            $result->value = count($children) > 1 ? $newChildrenResult : $newChildrenResult[0];
        }

        return $result;
    }

    public function getMetadata(Hotspotimage|Asset $value): mixed
    {
        $asset = $value;
        if ($value instanceof Hotspotimage) {
            $asset = $value->getImage();
        }

        if ($asset instanceof Asset) {
            return $asset->getMetadata($this->getMetaField(), $this->getLocale());
        }

        return null;
    }

    public function getMetaField(): mixed
    {
        return $this->metaField;
    }

    public function setMetaField(mixed $metaField): void
    {
        $this->metaField = $metaField;
    }

    public function setLocale(mixed $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): mixed
    {
        return $this->locale;
    }
}
