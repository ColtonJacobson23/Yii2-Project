<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

/**
 * @var dektrium\user\models\User
 */
?>
<?= Yii::t('user', 'Hello') ?>,

<?= Yii::t('user', "An administrator has changed your password on DRI's Acuity System.") ?>.
<?= Yii::t('user', 'Your new password is:  ') ?>:
<?= $password ?>

<?php
if (strtotime($user->passwordExpire) > 0)
{
    echo Yii::t('user', 'Your temporary password will expire on {0} if you do not login and create a new password!', $user->passwordExpire);
}
?>

<?= Yii::t('user', 'If you did not expect your password to change, please email acuity@dri.edu.') ?>
