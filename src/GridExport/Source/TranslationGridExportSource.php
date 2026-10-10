<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport\Source;

use OpenDxp\Bundle\AdminBundle\Attribute\AsGridExportSource;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Model\Translation;
use OpenDxp\Model\User;
use OpenDxp\Security\CorePermission;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AsGridExportSource(name: 'translations', permission: CorePermission::Translations->value)]
final class TranslationGridExportSource extends AbstractTranslationGridExportSource
{
    protected function getDomain(GridExportQuery $query): string
    {
        $domain = (string) ($query->parameters['domain'] ?? Translation::DOMAIN_DEFAULT);
        if ($domain === Translation::DOMAIN_ADMIN) {
            throw new AccessDeniedException('The admin translations are a grid export source of their own.');
        }

        return $domain;
    }

    protected function getLanguages(GridExportQuery $query): array
    {
        return User::getById($query->userId)?->getAllowedLanguagesForViewingWebsiteTranslations() ?? [];
    }
}
