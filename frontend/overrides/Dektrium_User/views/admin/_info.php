<?php

/*
 * This file is part of the Dektrium project
 *
 * (c) Dektrium project <http://github.com/dektrium>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

/**
 * @var yii\web\View $this
 * @var dektrium\user\models\User $user
 */
?>

<?php $this->beginContent('@dektrium/user/views/admin/update.php', ['user' => $user]) ?>

<style>
    .portalTitle {
        font-size:1.1em;
        font-weght:bold;
        padding-right: 5px;
    }
    .editTag
    {
        width:30px;
    }
</style>

<table class="table">
    <tr>
        <td><strong><?= Yii::t('user', 'Registration time') ?>:</strong></td>
        <td><?= Yii::t('user', '{0, date, MMMM dd, YYYY HH:mm}', [$user->created_at]) ?></td>
    </tr>
    <?php if ($user->registration_ip !== null): ?>
        <tr>
            <td><strong><?= Yii::t('user', 'Registration IP') ?>:</strong></td>
            <td><?= $user->registration_ip ?></td>
        </tr>
    <?php endif ?>
    <tr>
        <td><strong><?= Yii::t('user', 'Confirmation status') ?>:</strong></td>
        <?php if ($user->isConfirmed): ?>
            <td class="text-success">
                <?= Yii::t('user', 'Confirmed at {0, date, MMMM dd, YYYY HH:mm}', [$user->confirmed_at]) ?>
            </td>
        <?php else: ?>
            <td class="text-danger"><?= Yii::t('user', 'Unconfirmed') ?></td>
        <?php endif ?>
    </tr>
    <tr>
        <td><strong><?= Yii::t('user', 'Block status') ?>:</strong></td>
        <?php if ($user->isBlocked): ?>
            <td class="text-danger">
                <?= Yii::t('user', 'Blocked at {0, date, MMMM dd, YYYY HH:mm}', [$user->blocked_at]) ?>
            </td>
        <?php else: ?>
            <td class="text-success"><?= Yii::t('user', 'Not blocked') ?></td>
        <?php endif ?>
    </tr>
    <tr>
        <td><strong><?= Yii::t('user', 'Portals and Roles') ?>:</strong></td>
        <?php //error if NO portals and if no roles in a portal ?>
        <td class="text-success">
            <?php
                foreach($portalList as $p)
                {
                   $hasError = false;
                   if (!empty($p['yii2-rbac']['errors']) || (empty($p['yii2-rbac']['items']) && empty($p['yii2-rbac']['errors'])))
                   {$hasError = true;}

                    echo '<div>';
                    if (empty($p['yii2-rbac']['errors']))
                    {
                       echo "<span class='editTag'>".\yii\helpers\Html::a("[Edit]", $url = ["/user/admin/assignments?id=39&portal=".$p['portal_id']])." </span>";
                    }

                    echo '<span class="portalTitle '.($hasError ? "text-danger" : "text-success").'">'.(empty($p['portal_name']) ? $p['portal_id'] : $p['portal_name']).':</span>';
                    if (!empty($p['yii2-rbac']['items'])) {echo '<span class="text-success">'.implode(', ',array_column($p['yii2-rbac']['items'], 'name')) .' </span> ';}
                    if (!empty($p['yii2-rbac']['errors'])) {echo '<span class="text-danger"> ERRORS: '.implode(', ',$p['yii2-rbac']['errors']) .'</span>';}

                    if (empty($p['yii2-rbac']['items']) && empty($p['yii2-rbac']['errors']))
                    {
                      echo '<span class="text-danger">WARNING: User has no roles assigned!</span>';
                    }



                    echo '</div>';
                }
            ?>
        </td>
    </tr>




</table>

<?php $this->endContent() ?>
