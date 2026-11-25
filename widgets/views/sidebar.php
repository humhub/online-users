<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\onlineUsers\Module;
use humhub\modules\user\models\User;
use humhub\modules\user\widgets\Image;
use humhub\widgets\menu\MenuLink;
use humhub\widgets\SidebarCard;

/* @var User[] $users */
/* @var int $total */
/* @var MenuLink[] $menu */
?>
<?php SidebarCard::begin([
    'title' => Module::getInstance()->getIcon() . ' '
        . Yii::t('OnlineUsersModule.base', '<strong>Online</strong> users') . ' (' . $total . ')',
    'encodeTitle' => false,
    'menu' => $menu,
]) ?>
        <div class="d-flex gap-2 flex-wrap">
            <?php foreach ($users as $user) : ?>
                <?= Image::widget([
                    'user' => $user,
                    'width' => 32,
                    'showTooltip' => true,
                    'showSelfOnlineStatus' => true,
                ]) ?>
            <?php endforeach; ?>
        </div>
<?php SidebarCard::end() ?>
