<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlineUsers\tests\codeception\unit;

use humhub\modules\onlineUsers\services\OnlineStatusService;
use humhub\modules\onlineUsers\services\UserService;
use humhub\modules\user\models\User;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\caching\ArrayCache;
use yii\caching\CacheInterface;
use yii\caching\DummyCache;

class UserServiceTest extends HumHubDbTestCase
{
    private CacheInterface $originalCache;

    protected function _before()
    {
        parent::_before();

        $this->originalCache = Yii::$app->cache;
        Yii::$app->set('cache', new ArrayCache());

        Yii::$app->db->createCommand()->delete('user_http_session')->execute();
        $this->becomeUser('Admin');
    }

    protected function _after()
    {
        Yii::$app->set('cache', $this->originalCache);
        Yii::$app->db->createCommand()->delete('user_http_session')->execute();

        parent::_after();
    }

    public function testOnlyUsersWithOnlineFlagAreReturned()
    {
        $users = $this->getUsersByNames(['Admin', 'User1', 'User2']);
        $this->createSessions($users);
        $this->markOnline([$users['Admin'], $users['User2']]);

        $service = new UserService();

        $this->assertSame([$users['Admin']->id, $users['User2']->id], $service->getUserIds());
        $this->assertSame(2, $service->getCount());
        $this->assertSame(
            [$users['Admin']->id, $users['User2']->id],
            array_map(fn(User $user) => $user->id, $service->getUsers()),
        );
    }

    public function testOnlyRecentlyActiveUsersAreReturnedWhenAllUsersHaveSession()
    {
        // Sessions of all users are valid for hours, but only one user was recently active
        $allUsers = User::find()->where(['status' => User::STATUS_ENABLED])->all();
        $this->assertGreaterThan(3, count($allUsers));
        $this->createSessions($allUsers);
        $this->markOnline([$this->getUsersByNames(['User1'])['User1']]);

        $service = new UserService();

        $this->assertSame(1, $service->getCount());
        $this->assertCount(1, $service->getUsers());
    }

    public function testUserWithManySessionsIsReturnedOnce()
    {
        $user = $this->getUsersByNames(['User1'])['User1'];
        $this->createSessions([$user, $user, $user]);
        $this->markOnline([$user]);

        $service = new UserService();

        $this->assertSame([$user->id], $service->getUserIds());
        $this->assertSame(1, $service->getCount());
        $this->assertCount(1, $service->getUsers());
    }

    public function testExpiredSessionsAreIgnored()
    {
        $user = $this->getUsersByNames(['User1'])['User1'];
        $this->createSessions([$user], time() - 60);
        $this->markOnline([$user]);

        $this->assertSame(0, (new UserService())->getCount());
    }

    public function testLimitDoesNotChangeCountOrLaterResults()
    {
        $users = $this->getUsersByNames(['Admin', 'User1', 'User2']);
        $this->createSessions($users);
        $this->markOnline($users);

        $service = new UserService();

        $this->assertCount(2, $service->getUsers(2));
        $this->assertSame(3, $service->getCount());
        $this->assertCount(3, $service->getUsers());
        $this->assertCount(3, $service->getUsers(0));
    }

    public function testUserWhoHidesOnlineStatusIsNotReturned()
    {
        $users = $this->getUsersByNames(['Admin', 'User1']);
        $this->createSessions($users);
        $this->markOnline($users);

        // The online flag is still in the cache, but the status is hidden now
        $users['User1']->settings->set('hideOnlineStatus', 1);

        $result = (new UserService())->getUsers();

        $this->assertSame([$users['Admin']->id], array_map(fn(User $user) => $user->id, $result));
    }

    public function testNobodyIsReturnedWhenOnlineStatusIsHiddenGlobally()
    {
        $users = $this->getUsersByNames(['Admin', 'User1']);
        $this->createSessions($users);
        $this->markOnline($users);
        Yii::$app->getModule('user')->settings->set('auth.hideOnlineStatus', 1);

        $service = new UserService();

        $this->assertSame(0, $service->getCount());
        $this->assertSame([], $service->getUsers());
    }

    public function testAllSessionUsersAreReturnedWhenCacheIsDisabled()
    {
        Yii::$app->set('cache', new DummyCache());
        $users = $this->getUsersByNames(['Admin', 'User1']);
        $this->createSessions($users);

        $service = new UserService();

        $this->assertSame(2, $service->getCount());
        $this->assertCount(2, $service->getUsers());
    }

    public function testInvisibleUsersAreNotReturned()
    {
        $users = $this->getUsersByNames(['User1', 'User2']);
        $this->createSessions($users);
        $this->markOnline($users);
        User::updateAll(['visibility' => User::VISIBILITY_HIDDEN], ['id' => $users['User2']->id]);

        $this->becomeUser('User1');

        $this->assertSame([$users['User1']->id], (new UserService())->getUserIds());
    }

    /**
     * @param string[] $usernames
     * @return array<string, User> Users indexed by username
     */
    private function getUsersByNames(array $usernames): array
    {
        $users = [];
        foreach ($usernames as $username) {
            $users[$username] = User::findOne(['username' => $username]);
            $this->assertNotNull($users[$username], 'User ' . $username . ' not found');
        }

        return $users;
    }

    /**
     * @param User[] $users
     */
    private function createSessions(array $users, ?int $expire = null): void
    {
        $i = 0;
        foreach ($users as $user) {
            Yii::$app->db->createCommand()->insert('user_http_session', [
                'id' => 'test-session-' . $user->id . '-' . $i++,
                'expire' => $expire ?? time() + 3600,
                'user_id' => $user->id,
                'data' => '',
            ])->execute();
        }
    }

    /**
     * @param User[] $users
     */
    private function markOnline(array $users): void
    {
        foreach ($users as $user) {
            (new OnlineStatusService($user))->updateStatus();
        }
    }
}
