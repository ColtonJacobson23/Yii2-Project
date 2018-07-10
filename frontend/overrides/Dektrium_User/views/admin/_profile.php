<?php

//Override User Form for Acuity version.
//To override and use these, use the  " 'view' => 'theme' => 'pathMap' " defined in component config.
//See: https://github.com/dektrium/yii2-user/blob/master/docs/overriding-views.md

/* 
 * This file is part of the Dektrium project
 * 
 * (c) Dektrium project <http://github.com/dektrium>
 * 
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var dektrium\user\models\User $user
 */

?>

<?php $this->beginContent('@dektrium/user/views/admin/update.php', ['user' => $user]) ?>

    <?php $form = ActiveForm::begin([
        'layout' => 'horizontal',
        'enableAjaxValidation'   => true,
        'enableClientValidation' => false,
        'fieldConfig' => [
            'horizontalCssClasses' => [
                'wrapper' => 'col-sm-9',
            ]
        ],
    ]);


    //ACUITY -- remove unneeded fields:
    //            website, location, gravatar_emial, bio, public email

    ?>

<?= $form->field($profile, 'firstname') ?>
<?= $form->field($profile, 'lastname') ?>

    <?= $form->field($profile, 'company') ?>

    <div class="form-group">
        <div class="col-lg-offset-3 col-lg-9">
            <?= Html::submitButton(Yii::t('user', 'Update'), ['class' => 'btn btn-block btn-success']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

<?php $this->endContent() ?>
