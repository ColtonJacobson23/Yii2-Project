<?php

/*
 * This file is part of the Dektrium project
 *
 * (c) Dektrium project <http://github.com/dektrium>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use dektrium\rbac\widgets\Assignments;

/**
 * @var yii\web\View $this
 * @var dektrium\user\models\User $user
 */
?>

<style>
    .col2container {
        margin-bottom:15px;
    }
    .alert
    {
        margin-bottom:0px;
    }
</style>


<?php $this->beginContent('@dektrium/user/views/admin/update.php', ['user' => $user]) ?>

<?= yii\bootstrap\Alert::widget([
    'options' => [
        'class' => 'alert-info alert-dismissible',
    ],
    'body' => Yii::t('user', 'You can assign multiple roles or permissions to user by using the form below'),
]) ?>

<?php
    if (empty(Yii::$app->controller->rbacPortal))
    {
        throw new yii\base\UserException('Internal Error -- no rbacPortal selected');
    }
?>

<?= Yii::$app->controller->rbacPortal->output($this); ?>

<?= Assignments::widget(['userId' => $user->id]) ?>

<?php $this->endContent() ?>
