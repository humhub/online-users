<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlineUsers\widgets;

use humhub\components\Widget;
use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\onlineUsers\models\Config;
use humhub\modules\onlineUsers\Module;
use humhub\modules\onlineUsers\services\UserService;
use humhub\widgets\menu\MenuLink;
use Yii;

/**
 * SidebarWidget shows online users inside the dashboard sidebar.
 */
class SidebarWidget extends Widget
{
    /**
     * @inheritdoc
     */
    public function beforeRun()
    {
        return parent::beforeRun() && Config::instance()->isVisibleSidebar();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $userService = new UserService();
        if ($userService->getCount() === 0) {
            return '';
        }

        return $this->render('sidebar', [
            'total' => $userService->getCount(),
            'users' => $userService->getUsers(Config::instance()->limit),
            'menu' => $this->getMenu(),
        ]);
    }

    /**
     * @return MenuLink[] the entries of the card's ⋮ menu
     */
    private function getMenu(): array
    {
        if (!Yii::$app->user->can(ManageModules::class)) {
            return [];
        }

        return [new MenuLink([
            'label' => Yii::t('OnlineUsersModule.base', 'Settings'),
            'url' => Module::getInstance()->getConfigUrl(),
            'icon' => 'settings',
        ])];
    }
}
