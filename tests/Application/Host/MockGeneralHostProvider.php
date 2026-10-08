<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Application\Host;

use OpenDxp\Http\Request\Host\GeneralHostProviderInterface;

/**
 * Provides one host and remembers each context it was asked with.
 */
final class MockGeneralHostProvider implements GeneralHostProviderInterface
{
    /**
     * @var list<array<string, mixed>>
     */
    public array $contexts = [];

    public function __construct(private readonly string $host)
    {
    }

    public function provide(array $context = []): ?string
    {
        $this->contexts[] = $context;

        return $this->host;
    }
}
