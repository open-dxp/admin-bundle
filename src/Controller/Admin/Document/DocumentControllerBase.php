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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\Document;

use OpenDxp\Bundle\AdminBundle\Attribute\SessionIdentityAware;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\Document\ChangeMainDocument\ChangeMainDocumentHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\ChangeMainDocument\ChangeMainDocumentPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\RemoveFromSession\RemoveFromSessionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\RemoveFromSession\RemoveFromSessionPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\SaveToSession\SaveToSessionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\SaveToSession\SaveToSessionPayload;
use OpenDxp\Bundle\AdminBundle\Service\Element\ElementServiceInterface;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Security\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[IsGranted(CorePermission::Documents->value)]
abstract class DocumentControllerBase extends AdminAbstractController
{
    public const string TASK_PUBLISH = 'publish';

    public const string TASK_UNPUBLISH = 'unpublish';

    public const string TASK_SAVE = 'save';

    public const string TASK_VERSION = 'version';

    public const string TASK_SCHEDULER = 'scheduler';

    public const string TASK_AUTOSAVE = 'autosave';

    public const string TASK_DELETE = 'delete';

    public function __construct(protected ElementServiceInterface $elementService,)
    {
    }

    #[Route('/save-to-session', name: 'savetosession', methods: ['POST'])]
    #[SessionIdentityAware]
    public function saveToSessionAction(
        SaveToSessionPayload $payload,
        SaveToSessionHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/remove-from-session', name: 'removefromsession', methods: ['DELETE'])]
    #[SessionIdentityAware]
    public function removeFromSessionAction(
        RemoveFromSessionPayload $payload,
        RemoveFromSessionHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    /**
     * This is used for pages and snippets to change the main document (which is not saved with the normal save button)
     */
    #[Route('/change-main-document', name: 'changemaindocument', methods: ['PUT'])]
    public function changeMainDocumentAction(
        ChangeMainDocumentPayload $payload,
        ChangeMainDocumentHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    public function getTreeNodeConfig(ElementInterface $element): array
    {
        return $this->elementService->getElementTreeNodeConfig($element);
    }
}
