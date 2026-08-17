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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Email\GetEmailData;

use OpenDxp\Bundle\AdminBundle\Enricher\Document\DocumentMetaEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Document\DraftEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Document\PropertiesEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Document\TranslationEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\AdminStyleEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\PhpMetaEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\PreSendDataEventEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\UserNamesEnricher;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Helper\DocumentVersionHelper;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Element\EditLockService;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetEmailDataHandler
{
    public function __construct(
        private readonly EditLockService $editLockService,
        private readonly AdminUserContextInterface $userContext,
        private readonly DocumentMetaEnricher $documentMetaEnricher,
        private readonly AdminStyleEnricher $adminStyleEnricher,
        private readonly UserNamesEnricher $userNamesEnricher,
        private readonly PropertiesEnricher $propertiesEnricher,
        private readonly TranslationEnricher $translationEnricher,
        private readonly DraftEnricher $draftEnricher,
        private readonly PhpMetaEnricher $phpMetaEnricher,
        private readonly PreSendDataEventEnricher $preSendDataEventEnricher,
    ) {
    }

    public function __invoke(IdQueryPayload $payload): GetEmailDataResult
    {
        $email = Document\Email::getById($payload->id);
        if (!$email) {
            throw new NotFoundHttpException('Email not found');
        }

        if (!$email->isAllowed('view')) {
            throw new AccessDeniedHttpException();
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

        $this->documentMetaEnricher->enrich($email, $data);
        $this->phpMetaEnricher->enrich($email, $data);
        $this->adminStyleEnricher->forEditor($email, $data);
        $this->userNamesEnricher->enrich($email, $data);
        $this->propertiesEnricher->enrich($email, $data);
        $this->translationEnricher->enrich($email, $data);
        $this->draftEnricher->enrich($email, $data, $draftVersion);

        $this->preSendDataEventEnricher->enrich($email, $data);

        return new GetEmailDataResult(data: $data);
    }
}
