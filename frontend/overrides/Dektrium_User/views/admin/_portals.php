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
use kartik\grid\GridView;

?>

<style>
    .edit_arrow
    {
        font-size:20px;
        font-weight:bold;
    }
</style>
<?php
$this->beginContent('@common/overrides/Dektrium_User/views/admin/update.php', ['user' => $user]);

if ($access<1) {
  echo '<div class="alert alert-danger" role="alert">Warning: this user does not have access to any portals!!!!!</div>';
}

?>

<div style="margin-bottom:20px;font-weight: bold;font-size:14px;">Select which portals this user can access.</div>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
  //TODO
  //'filterModel' => $searchModel,

    'pjaxSettings'=>[
        'neverTimeout'=>true,
    ],

  /*'toolbar' => [
      '{export}',
      '{toggleData}'
  ],*/
/*  'panel' => [
      'heading'=>'<h3 class="panel-title"><i class="glyphicon glyphicon-globe"></i> Portals</h3>',
      'type'=>'success',
     // 'before'=>Html::a('<i class="glyphicon glyphicon-plus"></i> Create Country', ['create'], ['class' => 'btn btn-success']),
     // 'after'=>Html::a('<i class="glyphicon glyphicon-repeat"></i> Reset Grid', ['index'], ['class' => 'btn btn-info']),
      'footer'=>false
  ],*/
    'columns' => [
        //'portal_id',

      /*  [ 'class' => 'yii\grid\CheckboxColumn',
          'checkboxOptions' => function($model, $key, $index, $column)
          {
            return ['checked' => ($model['access']?true:false)];
          }
        ],*/
    /*    [
            'class' => '\kartik\grid\BooleanColumn',
            'trueLabel' => 'No',
            'falseLabel' => 'No',
            'showNullAsFalse' => true,
            'value'=>'access',
        ],*/
        [
            'class'=>'kartik\grid\EditableColumn',
            'format'=>'html',
            'attribute'=>'access',
            'header'=>'Portal Access',
            'value'=>function ($model, $key, $index, $widget) {
                  $class = (!empty($model['access'])?"glyphicon-ok text-success" : "glyphicon-remove text-danger");
                  return '<span class="glyphicon '.$class.'"></span>';},
            'vAlign'=>'middle',
            'hAlign'=>'center',
            'width'=>'100px',
            //'refreshGrid'=>true, //trigger full grid refresh
            //Note: always use the anonymous function version so every row is unique
            'editableOptions'=>function ($model, $key, $index) {
              return [
                    'name'=>'access', //used in POST to transfer value. Only present on true.
                    'header'=>'Portal Access',
                    'valueIfNull'=>false,
                  //'asPopover'=>false,
                    'size'=>'md',
                    'inputType'=>\kartik\editable\Editable::INPUT_SWITCH,
                    'options'=>[
                      'name' => 'access',
                      'pluginOptions' => [
                        'size' => 'large',
                        'onText' => 'Yes',
                        'offText' => 'No',
                        'state'=>empty($model['access'])?false:true,
                        ],
                    ],

    /*                'pluginEvents' => [
      !!!!-->Not needed. Will
                      //7/9/15 -- The value of the switch is not being sent in ajax, so do a workaround
                      //Note also, that editableChange is only called before editableSubmit and not on
                      //every change as the documents (http://demos.krajee.com/editable) suggest
                      //Also, the "val" parameter is not functioning, so let's look at the margin-left to determine
                      //  the status of the buttons. One big HACK Alert.
                      "editableChange"=>"function(event, val) {
                                //add class if not present, add it

                                var state = parseInt($(this).find('.kv-editable-form').find('.kv-editable-parent').find('.bootstrap-switch-container').css('margin-left'))<0?false:true;

                                var form = $(this).find('.kv-editable-form');
                                if (form.find('.ac-value').length>0)
                                {
                                    form.find('.ac-value').val(state);
                                }
                                else
                                {
                                    form.prepend('<input type=\"hidden\" class=\"ac-value\" name=\"editableValue\" value=\"'+state+'\">');
                                }
                            }",
                    ],
    */
              ];
            },
        ],
      [
        'format'=>'html',
        'attribute'=>'portal_id',
        'header'=>'Edit Roles',
        'vAlign'=>'middle',
        'hAlign'=>'center',
        'width'=>'50px',
        'value'=>function ($model, $key, $index, $widget) {
              return (($model['access'] && !empty($model['portal_name'])) ? "<span class='editTag'>".\yii\helpers\Html::a("<span class='edit_arrow glyphicon glyphicon-arrow-right'></span>", $url = ["/user/admin/assignments?id=39&portal=".$model['portal_id']])." </span>" : "");}
      ],
      [
           'format'=>'html',
           'attribute'=>'portal_name',
           'value'=>function ($model, $key, $index, $widget) {
                 return !empty($model['portal_name']) ? $model['portal_name'] : $model['portal_id']."<span class=\"glyphicon glyphicon-exclamation-sign\" style=\"margin-left:10px;color:red;\"></span><span style=\"margin-left:4px;color:red\">This portal is not listed in the *_portal_list table!</span>";
            }
      ],
       // 'portal_name',
    ],
]) ?>

<?php $this->endContent() ?>


