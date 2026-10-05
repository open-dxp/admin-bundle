<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Factory;

use OpenDxp\Model\DataObject\Data\UrlSlug;
use OpenDxp\Model\DataObject\Sluggable;
use OpenDxp\Test\Factory\AbstractDataObjectFactory;

/**
 * @extends AbstractDataObjectFactory<Sluggable>
 */
final class SluggableFactory extends AbstractDataObjectFactory
{
    public static function class(): string
    {
        return Sluggable::class;
    }

    public function withLocalizedSlug(string $language, string $slug): static
    {
        return $this->withLocalizedValues('lslug', [$language => [new UrlSlug($slug)]]);
    }
}
