<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Controller;

use JsonSerializable;
use OpenDxp\Bundle\AdminBundle\Handler\ConditionalResultInterface;
use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;
use OpenDxp\Controller\Traits\JsonHelperTrait;
use OpenDxp\Controller\UserAwareController;
use OpenDxp\Model\User;
use OpenDxp\Security\User\User as UserProxy;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * @internal
 */
abstract class AdminAbstractController extends UserAwareController
{
    use JsonHelperTrait;

    /**
     * Returns a JsonResponse that uses the admin serializer
     */
    protected function adminJson(mixed $data, int $status = 200, array $headers = [], array $context = [], bool $useAdminSerializer = true): JsonResponse
    {
        if ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        return $this->jsonResponse($data, $status, $headers, $context, $useAdminSerializer);
    }

    /**
     * Serializes a Handler Result into the admin wire response: `success` plus the
     * Result's own typed properties.
     * `success` is true unless the Result implements ConditionalResultInterface.
     *
     * TODO:
     * Once no Result ever holds a raw domain object as a property, every value handled
     * here is already a plain scalar/array (at that point this method (and adminJson()'s
     * detour through the opendxp serializer) can be replaced with plain symfony normalizer.
     *
     * $rootProperty is for endpoints whose established wire contract is the bare value of a
     * single Result property (a raw array/list, no envelope) rather than an enveloped
     * `{success, ...}` object.
     *
     * $context recognizes AbstractObjectNormalizer::SKIP_NULL_VALUES, reused here only as a
     * familiar name: the admin serializer never reaches Symfony's AbstractObjectNormalizer,
     * so the flag is interpreted directly below rather than delegated to it.
     * Pass it only for the specific action whose Result has a genuinely optional/conditional.
     */
    protected function apiJson(ResultInterface $result, int $status = 200, ?string $rootProperty = null, array $context = []): JsonResponse
    {
        if ($rootProperty !== null) {
            if (!property_exists($result, $rootProperty)) {
                throw new \LogicException(sprintf('%s has no property "%s" to use as apiJson() rootProperty.', $result::class, $rootProperty));
            }

            return $this->adminJson($result->$rootProperty, $status);
        }

        $success = $result instanceof ConditionalResultInterface ? $result->isSuccessful() : true;
        $data = get_object_vars($result);

        if ($context[AbstractObjectNormalizer::SKIP_NULL_VALUES] ?? false) {
            $data = array_filter($data, static fn (mixed $value): bool => $value !== null);
        }

        return $this->adminJson(['success' => $success, ...$data], $status);
    }

    /**
     * Wire response for void Command handlers: no data to report, only that it succeeded.
     */
    protected function apiOk(int $status = 200): JsonResponse
    {
        return $this->adminJson(['success' => true], $status);
    }

    /**
     * Get user from user proxy object which is registered on security component
     */
    protected function getAdminUser(bool $proxyUser = false): UserProxy|User|null
    {
        return $this->getOpenDxpUser($proxyUser);
    }
}
