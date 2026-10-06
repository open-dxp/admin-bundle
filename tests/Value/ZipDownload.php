<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Value;

final readonly class ZipDownload
{
    /**
     * @param array<string, string> $entries the content of each entry by its path
     */
    public function __construct(
        public string $filename,
        public array $entries,
    ) {
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        return array_map(strval(...), array_keys($this->entries));
    }
}
