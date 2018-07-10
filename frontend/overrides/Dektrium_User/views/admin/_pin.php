<?php

/*
 * Added for Acuity PIN functionality
 */

/**
 * @var yii\widgets\ActiveForm    $form
 * @var dektrium\user\models\User $user
 */

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

?>

<?php
$this->beginContent('@common/overrides/Dektrium_User/views/admin/update.php', ['user' => $user])
?>

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
    ?>

    <?= $form->field($user, 'pinPrefix')->textInput(['maxlength' => 2]) ?>
    <?= $form->field($user, 'pinSuffix')->textInput(['maxlength' => 4]) ?>
    <?= $form->field($user, 'pinLastChange')->textInput(['maxlength' => 25]) ?>

    <div class="form-group">
        <div class="col-lg-offset-3 col-lg-9">
            <?= Html::submitButton(Yii::t('user', 'Update'), ['class' => 'btn btn-block btn-success']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

<?php $this->endContent() ?>


