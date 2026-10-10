<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\GridExport\StartGridExport;

use DateTimeZone;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportFormat;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportHeader;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSettings;
use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class StartGridExportPayload implements ExtJsPayloadInterface
{
    // The export keeps the parameters in the TmpStore. Filters and sorting of a grid need a fraction of this.
    private const int MAXIMUM_PARAMETERS_LENGTH = 65536;

    /**
     * @param array<string, mixed> $parameters
     * @param list<string> $selectedIds
     */
    public function __construct(
        public string $source,
        public GridExportSettings $settings,
        public string $language,
        public string $timezone,
        public array $parameters = [],
        public array $selectedIds = [],
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        $parameters = $request->request->getString('parameters', '{}');
        if (strlen($parameters) > self::MAXIMUM_PARAMETERS_LENGTH) {
            throw new BadRequestHttpException('The parameters of the grid export are too long.');
        }

        $parameters = json_decode($parameters, true);
        if (!is_array($parameters)) {
            throw new BadRequestHttpException('The parameters of the grid export are no JSON object.');
        }

        $delimiter = $request->request->getString('delimiter', ';');
        if (strlen($delimiter) !== 1) {
            throw new BadRequestHttpException('The delimiter of a grid export is one character.');
        }

        $timezone = $request->request->getString('timezone') ?: date_default_timezone_get();
        if (!in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            throw new BadRequestHttpException('The grid export has no such timezone.');
        }

        return new static(
            source: $request->request->getString('source'),
            settings: new GridExportSettings(
                format: GridExportFormat::tryFrom(
                    $request->request->getString('format', GridExportFormat::CSV->value),
                ) ?? throw new BadRequestHttpException('The grid export has no such format.'),
                header: GridExportHeader::tryFrom(
                    $request->request->getString('header', GridExportHeader::LABEL->value),
                ) ?? throw new BadRequestHttpException('The grid export has no such header.'),
                delimiter: $delimiter,
            ),
            language: $request->request->getString('language') ?: $request->getLocale(),
            timezone: $timezone,
            parameters: $parameters,
            selectedIds: array_values(array_map(strval(...), $request->request->all('selectedIds'))),
        );
    }
}
