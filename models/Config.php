<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlineUsers\models;

use humhub\components\SettingsManager;
use humhub\modules\onlineUsers\Module;
use Yii;
use yii\base\Model;

/**
 * This is a form for the Module config
 */
class Config extends Model
{
    protected ?SettingsManager $settings = null;
    /** @var int|string|null */
    public $sidebarOrder;
    /** @var int|string|null */
    public $limit;
    /** @var bool|int|string|null */
    public $showForGuests;

    /**
     * @inheritdoc
     */
    public function init()
    {
        /* @var $module Module */
        $module = Yii::$app->getModule('online-users');
        $this->settings = $module->settings;

        $this->sidebarOrder = (int) $this->settings->get('sidebarOrder', 300);
        $this->limit = (int) $this->settings->get('limit', 20);
        $this->showForGuests = (bool) $this->settings->get('showForGuests', false);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sidebarOrder', 'limit'], 'integer', 'min' => 0],
            [['showForGuests'], 'boolean'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'sidebarOrder' => Yii::t('OnlineUsersModule.base', 'Dashboard sidebar order'),
            'limit' => Yii::t('OnlineUsersModule.base', 'Number of users'),
            'showForGuests' => Yii::t('OnlineUsersModule.base', 'Show widget in guest mode'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'limit' => Yii::t('OnlineUsersModule.base', 'Limit users on the dashboard widget'),
        ];
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $this->settings->set('sidebarOrder', $this->sidebarOrder);
        $this->settings->set('limit', $this->limit);
        $this->settings->set('showForGuests', (bool) $this->showForGuests);

        return true;
    }

    public function isVisibleSidebar(): bool
    {
        if ($this->limit <= 0) {
            return false;
        }

        return $this->showForGuests || !Yii::$app->user->isGuest;
    }
}
