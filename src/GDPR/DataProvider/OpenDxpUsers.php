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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\GDPR\DataProvider;

use OpenDxp\Db;
use OpenDxp\Model\User;
use OpenDxp\Security\User\TokenStorageUserResolver;

/**
 * @internal
 */
class OpenDxpUsers implements DataProviderInterface
{
    public function __construct(protected TokenStorageUserResolver $userResolver, private readonly string $logsDir)
    {
    }

    public function getName(): string
    {
        return 'OpenDxpUsers';
    }

    public function getJsClassName(): string
    {
        return 'opendxp.settings.gdpr.dataproviders.openDxpUsers';
    }

    public function getSortPriority(): int
    {
        return 30;
    }

    public function searchData(int $id, string $firstname, string $lastname, string $email, int $start, int $limit, ?string $sort = null): array
    {
        if (empty($id) && empty($firstname) && empty($lastname) && empty($email)) {
            return ['data' => [], 'success' => true, 'total' => 0];
        }

        $userListing = new User\Listing();

        $conditionParams = [];
        $conditionParamData = [];
        if ($id) {
            $conditionParams[] = 'id = ?';
            $conditionParamData[] = $id;
        }
        if ($firstname) {
            $conditionParams[] = 'firstname LIKE ?';
            $conditionParamData[] = '%' . $firstname . '%';
        }
        if ($lastname) {
            $conditionParams[] = 'lastname LIKE ?';
            $conditionParamData[] = '%' . $lastname . '%';
        }
        if ($email) {
            $conditionParams[] = 'email LIKE ?';
            $conditionParamData[] = '%' . $email . '%';
        }

        $userListing->setCondition(implode(' AND ', $conditionParams), $conditionParamData);
        $userListing->setLimit($limit);
        $userListing->setOffset($start);

        $sortingSettings = \OpenDxp\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings(['sort' => $sort]);
        if ($sortingSettings['orderKey']) {
            $userListing->setOrderKey($sortingSettings['orderKey']);
        }
        if ($sortingSettings['order']) {
            $userListing->setOrder($sortingSettings['order']);
        }

        $userListing->load();

        $users = [];

        $currentUser = $this->userResolver->getUser();

        foreach ($userListing->getUsers() as $user) {
            $users[] = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                '__gdprIsDeletable' => $user->getId() != $currentUser->getId(),

            ];
        }

        return $users;
    }

    public function getExportData(int $id): array
    {
        $user = User::getById($id);
        $userData = [];
        if ($user) {
            $userData = $user->getObjectVars();
            unset($userData['password']);
            $userData['versions'] = $this->getVersionDataForUser($user);
            $userData['usageLog'] = $this->getUsageLogDataForUser($user);
        }

        return $userData;
    }

    protected function getVersionDataForUser(User\AbstractUser $user): array
    {
        $db = Db::get();

        return $db->fetchAllAssociative("SELECT ctype, cid, note, FROM_UNIXTIME(`date`) AS 'date' FROM versions WHERE userId = ?", [$user->getId()]);
    }

    protected function getUsageLogDataForUser(User\AbstractUser $user): array
    {
        $pattern = ' [' . $user->getId() . ',';
        $matches = [];

        $handle = @fopen($this->logsDir . '/usage.log', 'r');
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle);
                if ($buffer && str_contains($buffer, $pattern)) {
                    $matches[] = $buffer;
                }
            }
            fclose($handle);
        }

        $archiveFiles = glob($this->logsDir . '/usage-archive-*.log.gz');
        foreach ($archiveFiles as $archiveFile) {
            $handle = @gzopen($archiveFile, 'r');
            if ($handle) {
                while (!feof($handle)) {
                    $buffer = fgets($handle);
                    if (str_contains($buffer, $pattern)) {
                        $matches[] = $buffer;
                    }
                }
                fclose($handle);
            }
        }

        return $matches;
    }
}
