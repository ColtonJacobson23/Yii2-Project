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
 * @var $this  yii\web\View
 * @var $model dektrium\rbac\models\Role
 */

use kartik\select2\Select2;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
?>

<?php $form = ActiveForm::begin([
    'enableClientValidation' => false,
    'enableAjaxValidation'   => true,
]) ?>

<?= $form->field($model, 'name') ?>

<?= $form->field($model, 'description')->textarea() ?>

<?= $form->field($model, 'rule')->widget(Select2::className(), [
    'options'   => [
        'placeholder' => Yii::t('yii2-rbac', 'Select rule'),
    ],
    'pluginOptions' => [
        'ajax' => [
            'url'  => Url::to(['/yii2-rbac/rule/search']),
            'data' => new JsExpression('function(params) { return {q:params.term}; }')
        ],
        'allowClear' => true,
    ],
]) ?>

<?php if ($model->dataCannotBeDecoded): ?>
    <div class="alert alert-info">
        <?= Yii::t('yii2-rbac', 'Data cannot be decoded') ?>
    </div>
<?php else: ?>
    <?= $form->field($model, 'data')->textarea([
        'rows' => 3
    ]) ?>
<?php endif ?>

<?= $form->field($model, 'children')->widget(Select2::className(), [
    'data' => $model->getUnassignedItems(),
    'options' => [
        'id' => 'children',
        'multiple' => true
    ],
]) ?>

<?= Html::submitButton(Yii::t('yii2-rbac', 'Save'), ['class' => 'btn btn-success btn-block']) ?>

<?php ActiveForm::end() ?>