<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlineUsers\services;

use humhub\modules\user\components\ActiveQueryUser;
use humhub\modules\user\components\Session;

class UserService
{
    public static function instance(): self
    {
        return new self();
    }

    public function query(): ActiveQueryUser
    {
        return Session::getOnlineUsers()->visible();
    }

    public function count(): int
    {
        return $this->query()->count();
    }
}
