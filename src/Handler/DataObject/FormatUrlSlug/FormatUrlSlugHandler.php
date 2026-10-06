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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\FormatUrlSlug;

use OpenDxp\Bundle\AdminBundle\Service\DataObject\ContextFieldDefinitionResolver;
use OpenDxp\Model\DataObject\ClassDefinition\Data\UrlSlug;
use OpenDxp\Model\DataObject\ClassDefinition\UrlSlugContext;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Site;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class FormatUrlSlugHandler
{
    public function __construct(private ContextFieldDefinitionResolver $fieldDefinitions)
    {
    }

    public function __invoke(FormatUrlSlugPayload $payload): FormatUrlSlugResult
    {
        $object = Concrete::getById($payload->objectId)
            ?? throw new NotFoundHttpException('element_not_found');

        if (!$object->isAllowed('view')) {
            throw new AccessDeniedHttpException();
        }

        $definition = $this->fieldDefinitions->resolve($object, $payload->context);

        if (!$definition instanceof UrlSlug) {
            throw new BadRequestHttpException('The context names no URL slug field.');
        }

        $context = new UrlSlugContext(
            $object,
            $definition,
            $payload->context['language'] ?? null,
            $payload->siteId ? Site::getById($payload->siteId) : null,
        );

        return new FormatUrlSlugResult($definition->formatSlug($payload->text, $context));
    }
}
