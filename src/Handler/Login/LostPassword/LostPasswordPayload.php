<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\LostPassword;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class LostPasswordPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $username,
        public readonly string $clientIp,
        public readonly bool $isPost,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            username: (string) $request->request->get('username'),
            clientIp: (string) $request->getClientIp(),
            isPost: $request->isMethod('POST') && $request->request->has('username'),
        );
    }
}
