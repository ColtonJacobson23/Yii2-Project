<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use dektrium\user\models\User;
use yii\bootstrap\Nav;
use yii\web\View;

/**
 * @var View $this
 * @var User $user
 */

$this->title = Yii::t('user', 'Update user account');
$this->params['breadcrumbs'][] = ['label' => Yii::t('user', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>

<?= $this->render('/_alert', [
    'module' => Yii::$app->getModule('user'),
]) ?>

<?= $this->render('/admin/_menu') ?>

<style>
    .nav_header
    {
        font-weight:bold;
        padding: 5px 0;
        margin-bottom:5px;
        background-color: #ffc73d;
        border-radius: 5px;

    }
</style>


<div class="row">
    <div class="col-md-3">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="text-center nav_header"><?= $user->username ?></div>
                <?= Nav::widget([
                    'options' => [
                        'class' => 'nav-pills nav-stacked'
                    ],
                    'items' => [

                      ['label' => Yii::t('user', 'Summary'), 'url' => ['/user/admin/info', 'id' => $user->id]],

                        ['label' => Yii::t('user', 'Account details'), 'url' => ['/user/admin/update', 'id' => $user->id]],



                        ['label' => Yii::t('user', 'Profile details'), 'url' => ['/user/admin/update-profile', 'id' => $user->id]],
                      //RBS Added 7/5/15 -- also add to update.php
                      ['label' => Yii::t('user', 'Portal Access'), 'url' => ['/user/admin/update-portals', 'id' => $user->id]],

                      [
                          'label' => Yii::t('user', 'RBAC Assignments'),
                          'url' => ['/user/admin/assignments', 'id' => $user->id],
                          'visible' => isset(Yii::$app->extensions['dektrium/yii2-rbac']),
                      ],
                        //RBS Added 7/5/15 -- also add to update.php
                        ['label' => Yii::t('user', 'PIN details'), 'url' => ['/user/admin/update-pin', 'id' => $user->id]],
                        '<hr>',
                        [
                            'label' => Yii::t('user', 'Confirm'),
                            'url'   => ['/user/admin/confirm', 'id' => $user->id],
                            'visible' => !$user->isConfirmed,
                            'linkOptions' => [
                                'class' => 'text-success',
                                'data-method' => 'post',
                                'data-confirm' => Yii::t('user', 'Are you sure you want to confirm this user?')
                            ],
                        ],
                        [
                            'label' => Yii::t('user', 'Block'),
                            'url'   => ['/user/admin/block', 'id' => $user->id],
                            'visible' => !$user->isBlocked,
                            'linkOptions' => [
                                'class' => 'text-danger',
                                'data-method' => 'post',
                                'data-confirm' => Yii::t('user', 'Are you sure you want to block this user?')
                            ],
                        ],
                        [
                            'label' => Yii::t('user', 'Unblock'),
                            'url'   => ['/user/admin/block', 'id' => $user->id],
                            'visible' => $user->isBlocked,
                            'linkOptions' => [
                                'class' => 'text-success',
                                'data-method' => 'post',
                                'data-confirm' => Yii::t('user', 'Are you sure you want to unblock this user?')
                            ],
                        ],
                        [
                            'label' => Yii::t('user', 'Delete'),
                            'url'   => ['/user/admin/delete', 'id' => $user->id],
                            'linkOptions' => [
                                'class' => 'text-danger',
                                'data-method' => 'post',
                                'data-confirm' => Yii::t('user', 'USERS SHOULD NOT BE DELETED UNDER NORMAL CIRCUMSTANCES! Instead you should ban the user if you want to blcok access. Are you really sure you want to delete this user?')
                            ],
                        ],
                    ]
                ]) ?>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="panel panel-default">
            <div class="panel-body">
                <?= $content ?>
            </div>
        </div>
    </div>
</div>