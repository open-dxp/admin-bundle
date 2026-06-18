<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\ResendEmail;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ResendEmailPayload implements ExtJsPayloadInterface
{
    /** @var array<string, string|null> */
    public readonly array $fieldOverrides;

    public function __construct(
        public readonly int $id,
        ?array $fieldOverrides,
    ) {
        $this->fieldOverrides = $fieldOverrides ?? [];
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: (int) $request->request->get('id'),
            fieldOverrides: [
                'from' => $request->request->get('from') ?: null,
                'to' => $request->request->get('to') ?: null,
                'cc' => $request->request->get('cc') ?: null,
                'bcc' => $request->request->get('bcc') ?: null,
                'replyto' => $request->request->get('replyto') ?: null,
            ],
        );
    }
}
