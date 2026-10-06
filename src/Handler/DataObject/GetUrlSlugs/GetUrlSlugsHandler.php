<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetUrlSlugs;

use OpenDxp\Bundle\AdminBundle\Helper\DataObjectVersionHelper;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\DataObject\ContextFieldDefinitionResolver;
use OpenDxp\Model\DataObject\ClassDefinition\Data\UrlSlug;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Localizedfield;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class GetUrlSlugsHandler
{
    public function __construct(
        private ContextFieldDefinitionResolver $fieldDefinitions,
        private AdminUserContextInterface $userContext,
    ) {
    }

    public function __invoke(GetUrlSlugsPayload $payload): GetUrlSlugsResult
    {
        $object = Concrete::getById($payload->objectId, ['force' => true])
            ?? throw new NotFoundHttpException('element_not_found');

        if (!$object->isAllowed('view')) {
            throw new AccessDeniedHttpException();
        }

        $object = DataObjectVersionHelper::resolveLatestDraft($object, $this->userContext->getAdminUser()?->getId());
        $definition = $this->fieldDefinitions->resolve($object, $payload->context);

        if (!$definition instanceof UrlSlug || isset($payload->context['subContainerType'])) {
            throw new BadRequestHttpException('The context names no URL slug field of the class.');
        }

        $slugs = match ($payload->context['containerType'] ?? null) {
            'object' => $object->get($definition->getName()),
            'localizedfield' => $this->localizedSlugs($object, $definition, (string) ($payload->context['language'] ?? '')),
            default => throw new BadRequestHttpException('The context names no URL slug field of the class.'),
        };

        return new GetUrlSlugsResult($definition->getDataForEditmode($slugs, $object));
    }

    private function localizedSlugs(Concrete $object, UrlSlug $definition, string $language): ?array
    {
        $localizedFields = $object->get('localizedfields');

        return $localizedFields instanceof Localizedfield
            ? $localizedFields->getLocalizedValue($definition->getName(), $language, true)
            : null;
    }
}
