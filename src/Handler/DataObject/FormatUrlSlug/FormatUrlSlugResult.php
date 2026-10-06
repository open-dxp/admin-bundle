<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\FormatUrlSlug;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class FormatUrlSlugResult implements ResultInterface
{
    public function __construct(public string $slug)
    {
    }
}
