<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlineUsers\services;

use humhub\modules\user\components\ActiveQueryUser;
use humhub\modules\user\components\Session;
use humhub\modules\user\models\User;
use Yii;
use yii\caching\DummyCache;

class UserService
{
    /**
     * @var int[]|null IDs of the online users, ordered by ID
     */
    private ?array $userIds = null;

    /**
     * Query of the users with an active session, which are visible for the current user
     */
    public function query(): ActiveQueryUser
    {
        return Session::getOnlineUsers()->visible();
    }

    public function getCount(): int
    {
        return count($this->getUserIds());
    }

    /**
     * @param int|null $limit Max number of users, all users if empty
     * @return User[]
     */
    public function getUsers(?int $limit = null): array
    {
        $userIds = $this->getUserIds();

        if ($limit > 0) {
            $userIds = array_slice($userIds, 0, $limit);
        }

        if ($userIds === []) {
            return [];
        }

        $users = User::find()->where(['user.id' => $userIds])->indexBy('id')->all();

        $result = [];
        foreach ($userIds as $userId) {
            // The online flag may be set shortly before the user hides the online status
            if (isset($users[$userId]) && (new OnlineStatusService($users[$userId]))->isEnabled()) {
                $result[] = $users[$userId];
            }
        }

        return $result;
    }

    /**
     * Get IDs of the visible users who are really online (see OnlineStatusService),
     * without loading the user records.
     *
     * @return int[]
     */
    public function getUserIds(): array
    {
        if ($this->userIds === null) {
            $this->userIds = $this->isOnlineStatusHidden() ? [] : $this->filterByOnlineStatus(
                array_map('intval', $this->query()->select('user.id')->distinct()->orderBy('user.id')->column()),
            );
        }

        return $this->userIds;
    }

    private function isOnlineStatusHidden(): bool
    {
        return (bool) Yii::$app->getModule('user')->settings->get('auth.hideOnlineStatus');
    }

    /**
     * Keep only users with the "is online" flag in the cache, all checked by a single cache request.
     *
     * @param int[] $userIds
     * @return int[]
     */
    private function filterByOnlineStatus(array $userIds): array
    {
        // Don't check when cache is disabled, the flag can't be stored there
        if ($userIds === [] || Yii::$app->cache instanceof DummyCache) {
            return $userIds;
        }

        $cacheKeys = [];
        foreach ($userIds as $userId) {
            $cacheKeys[$userId] = (new OnlineStatusService(new User(['id' => $userId])))->getCacheKey();
        }

        $flags = Yii::$app->cache->multiGet($cacheKeys);

        return array_values(array_filter($userIds, fn(int $userId) => !empty($flags[$cacheKeys[$userId]])));
    }
}
