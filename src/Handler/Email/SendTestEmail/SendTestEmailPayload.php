<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\SendTestEmail;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class SendTestEmailPayload implements ExtJsPayloadInterface
{
    /** @var array<string, mixed>|null */
    public readonly ?array $mailParameters;

    public function __construct(
        public readonly string $emailType,
        public readonly ?string $content,
        public readonly ?string $documentPath,
        ?array $mailParameters,
        public readonly ?string $from,
        public readonly string $to,
        public readonly string $subject,
    ) {
        $this->mailParameters = $mailParameters;
    }

    public static function fromRequest(Request $request): static
    {
        $mailParameters = null;
        if ($request->request->has('mailParamaters')) {
            $decodedMailParameters = json_decode($request->request->get('mailParamaters'), true) ?: null;
            $mailParameters = is_array($decodedMailParameters) ? $decodedMailParameters : null;
        }

        return new static(
            emailType: (string) $request->request->get('emailType'),
            content: $request->request->get('content'),
            documentPath: $request->request->get('documentPath'),
            mailParameters: $mailParameters,
            from: $request->request->get('from'),
            to: (string) $request->request->get('to'),
            subject: (string) $request->request->get('subject'),
        );
    }
}
