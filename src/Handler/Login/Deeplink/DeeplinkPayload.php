<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\Deeplink;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class DeeplinkPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $queryString,
        public readonly string $perspective,
        public readonly ?string $deeplink = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $queryString = (string) ($request->server->get('QUERY_STRING') ?? '');
        $perspective = (string) $request->query->get('perspective', '');
        $perspective = strip_tags($perspective);

        $deeplink = null;
        if (preg_match('/(document|asset|object)_(\d+)_([a-z]+)/', $queryString, $matches)) {
            $deeplink = $matches[0];
        }

        return new static(
            queryString: $queryString,
            perspective: $perspective,
            deeplink: $deeplink,
        );
    }
}
