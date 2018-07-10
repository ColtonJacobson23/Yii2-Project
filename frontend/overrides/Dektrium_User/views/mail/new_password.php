<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use yii\helpers\Html;

/**
 * @var dektrium\user\Module          $module
 * @var dektrium\user\models\User     $user
 * @var dektrium\user\models\Password $password
 */

?>
<p style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; font-weight: normal; margin: 0 0 10px; padding: 0;">
    <?= Yii::t('user', 'Hello') ?>,
</p>

<p style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; font-weight: normal; margin: 0 0 10px; padding: 0;">
    <?php //  Yii::t('user', 'Your account on {0} has a new password', Yii::$app->name).?>

    <?= Yii::t('user', "An administrator has changed your password on DRI's Acuity System.")?>
    <?= Yii::t('user', 'Your new password is') ?>: <strong><?= $password ?></strong>

    <?php
    if (strtotime($user->passwordExpire) > 0)
    {
        echo Yii::t('user', '<div>Your temporary password will expire on {0} if you do not login and create a new password!</div>', $user->passwordExpire);
    }
    ?>

</p>

<p style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; font-weight: normal; margin: 0 0 10px; padding: 0;">
    <?= Yii::t('user', 'If you did not expect your password to change, please email acuity@dri.edu.') ?>
</p>