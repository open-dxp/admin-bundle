<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetNicePath;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetNicePathPayload implements ExtJsPayloadInterface
{
    /** @var array<string, mixed>|null */
    public readonly ?array $source;

    /** @var array<string, mixed>|null */
    public readonly ?array $context;

    /** @var array<string, mixed>|null */
    public readonly ?array $targets;

    public function __construct(
        ?array $source,
        ?array $context,
        ?array $targets,
        public readonly bool $loadEditModeData,
        public readonly string $idProperty = 'id',
    ) {
        $this->source = $source;
        $this->context = $context;
        $this->targets = $targets;
    }

    public static function fromRequest(Request $request): static
    {
        $source = $request->request->get('source');
        $context = $request->request->has('context') ? $request->request->get('context') : [];
        $targets = $request->request->get('targets');

        return new static(
            source: $source !== null ? (json_decode($source, true) ?? null) : null,
            context: $context !== [] ? (json_decode($context, true) ?? null) : null,
            targets: $targets !== null ? (json_decode($targets, true) ?? null) : null,
            loadEditModeData: $request->request->getBoolean('loadEditModeData'),
            idProperty: $request->request->get('idProperty', 'id'),
        );
    }
}
