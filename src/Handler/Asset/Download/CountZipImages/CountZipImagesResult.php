<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\CountZipImages;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class CountZipImagesResult implements ResultInterface
{
    public function __construct(public int $count)
    {
    }
}
