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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Email;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Helper\DocumentVersionHelper;
use OpenDxp\Bundle\AdminBundle\Normalizer\ElementResponseNormalizer;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Element\EditLockService;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetEmailDataHandler
{
    public function __construct(
        private readonly EditLockService $editLockService,
        private readonly ElementResponseNormalizer $normalizer,
        private readonly AdminUserContextInterface $userContext,
    ) {}

    public function __invoke(int $id): GetEmailDataResult
    {
        $email = Document\Email::getById($id);
        if (!$email) {
            throw new NotFoundHttpException('Email not found');
        }

        if ($email->isAllowed('save') || $email->isAllowed('publish') || $email->isAllowed('unpublish') || $email->isAllowed('delete')) {
            $this->editLockService->checkAndAcquire($email->getId(), 'document', AdminEvents::DOCUMENT_GET_IS_LOCKED, $email);
        }

        $email = clone $email;
        $draftVersion = null;
        $email = DocumentVersionHelper::resolveLatestDraft($email, $draftVersion, $this->userContext->getAdminUser()?->getId());

        $versions = Element\Service::getSafeVersionInfo($email->getVersions());
        $email->setVersions(array_splice($versions, -1, 1));
        $email->setParent(null);

        // unset useless data
        $email->setEditables(null);
        $email->setChildren(null);

        $data = $email->getObjectVars();
        $data['locked'] = $email->isLocked();
        $data['url'] = $email->getUrl();

        $this->normalizer->normalize($email, $data, self::class, ['draftVersion' => $draftVersion]);

        return new GetEmailDataResult(email: $email, data: $data, draftVersion: $draftVersion);
    }
}
