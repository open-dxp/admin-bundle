<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetUrlSlugs;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class GetUrlSlugsResult implements ResultInterface
{
    /**
     * @param list<array<string, mixed>> $slugs
     */
    public function __construct(public array $slugs)
    {
    }
}
