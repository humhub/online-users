<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlineUsers\services;

use humhub\modules\user\services\IsOnlineService;

/**
 * Makes the cache key of the core IsOnlineService public,
 * so the status of many users can be checked with a single cache request.
 */
class OnlineStatusService extends IsOnlineService
{
    public function getCacheKey(): string
    {
        return parent::getCacheKey();
    }
}
