<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetNicePath;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetNicePathPayload implements ExtJsPayloadInterface
{
    /** @var array<string, mixed> */
    public readonly array $source;

    /** @var array<string, mixed> */
    public readonly array $context;

    /** @var array<string, mixed> */
    public readonly array $targets;

    public function __construct(
        array $source,
        array $context,
        array $targets,
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
        $context = $request->request->has('context') ? $request->request->get('context') : null;
        $targets = $request->request->get('targets');

        return new static(
            source: self::decodeToArray($source),
            context: self::decodeToArray($context),
            targets: self::decodeToArray($targets),
            loadEditModeData: $request->request->getBoolean('loadEditModeData'),
            idProperty: (string) $request->request->get('idProperty', 'id'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeToArray(mixed $value): array
    {
        if (!is_string($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
