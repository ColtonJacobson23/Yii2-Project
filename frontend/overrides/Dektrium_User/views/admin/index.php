<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

//use dektrium\user\models\UserSearch;
//Below use is infcorrect.
//use overrides\models\UserSearch;

use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\jui\DatePicker;
use yii\web\View;
use yii\widgets\Pjax;

/**
 * @var View $this
 * @var ActiveDataProvider $dataProvider
 * @var UserSearch $searchModel
 */
echo '<div style="padding:10px;color:green">TODO: Confirm option needs to look at legacy parameter as well. Check this..</div>';
echo '<div style="padding:10px;color:green">TODO: Fix password only being saved as blowfish</div>';

echo '<div style="padding:10px;color:green">TODO: Status filter works, but default value not set! Need to set it</div>';

echo  '<div style="padding:10px;color:green">TODO: Allow a selection of basic portal roles -- define in admin portal set up.</div>';

$this->title = Yii::t('user', 'Manage users');
$this->params['breadcrumbs'][] = $this->title;
?>


<?= $this->render('/_alert', [
    'module' => Yii::$app->getModule('user'),
]) ?>

<?= $this->render('/admin/_menu') ?>

<?php Pjax::begin();


echo GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel'  => $searchModel,
    'layout'  => "{items}\n{pager}",
    'columns' => [
      [
          'class' => 'yii\grid\ActionColumn',
          'template' => '{update}',
      ],
        'username',
        'email:email',
      //  [
      ////      'attribute' => 'registration_ip',
      //      'value' => function ($model) {
      //              return $model->registration_ip == null
      //                  ? '<span class="not-set">' . Yii::t('user', '(not set)') . '</span>'
      //                  : $model->registration_ip;
      //          },
      //      'format' => 'html',
      //  ],
      //
     [
          'attribute' => 'status',
          'value' => function ($model) {
              return \overrides\Dektrium_User\models\User::itemAlias('UserStatus', $model->status);
          },
          'filter'=>Html::dropDownList('userAcctStatus',  null, //Note first param is the name that will be used in the POST
                    \overrides\Dektrium_User\models\User::itemAlias('UserStatus'),
                    ['prompt'=>'Select Status']
            ),

        //'filter' => Html::activeDropDownList($searchModel, 'attribute_name', ArrayHelper::map(ModelName::find()->asArray()->all(), 'ID', 'Name'),['class'=>'form-control','prompt' => 'Select Category']),

      ],
      [
          'attribute' => 'accountExpire',
          'value' => function ($model) {
              return !empty(strtotime($model->accountExpire)) && strtotime($model->accountExpire)>0 ? strftime('%Y-%m-%d %H:%M', strtotime($model->accountExpire)): "Never";
          },
          'filter' => DatePicker::widget([
              'model'      => $searchModel,
              'attribute'  => 'accountExpire',
              'dateFormat' => 'php:Y-m-d',
              'options' => [
                  'class' => 'form-control'
              ]
          ]),
      ],
      [
          'attribute' => 'passwordExpire',
          'value' => function ($model) {
              return !empty(strtotime($model->passwordExpire)) && strtotime($model->passwordExpire)>0  ? strftime('%Y-%m-%d %H:%M', strtotime($model->passwordExpire)): "Never";
          },
          'filter' => DatePicker::widget([
              'model'      => $searchModel,
              'attribute'  => 'passwordExpire',
              'dateFormat' => 'php:Y-m-d',
              'options' => [
                  'class' => 'form-control'
              ]
          ]),
      ],
        [
            'attribute' => 'lastvisit_at',
            'value' => function ($model) {
                return !empty(strtotime($model->lastvisit_at)) && strtotime($model->lastvisit_at)>0   ? strftime('%Y-%m-%d %H:%M', strtotime($model->lastvisit_at)): "Never";
            },
            'filter' => DatePicker::widget([
                'model'      => $searchModel,
                'attribute'  => 'lastvisit_at',
                'dateFormat' => 'php:Y-m-d',
                'options' => [
                    'class' => 'form-control'
                ]
            ]),
        ],
        [
            'header' => Yii::t('user', 'Confirmation'),
            'options'=> ["style"=>"width:90px;"],
            'value' => function ($model) {
                if ($model->isBlocked) {return '<div class="text-center"><span class="text-danger">' . Yii::t('user', 'Banned') . '</span></div>';}
                elseif ($model->isConfirmed) {
                    return '<div class="text-center"><span class="text-success">' . Yii::t('user', 'Confirmed') . '</span></div>';
                } else {
                    return Html::a(Yii::t('user', 'Confirm'), ['confirm', 'id' => $model->id], [
                        'class' => 'btn btn-xs btn-success btn-block',
                        'data-method' => 'post',
                        'data-confirm' => Yii::t('user', 'Are you sure you want to confirm this user?'),
                    ]);
                }
            },
            'format' => 'raw',
            'visible' => Yii::$app->getModule('user')->enableConfirmation
        ],
        [
            'header' => Yii::t('user', 'Block status'),
            'options'=> ["style"=>"width:90px;"],
            'value' => function ($model) {
                if ($model->isBlocked) {
                    return Html::a(Yii::t('user', 'Unblock'), ['block', 'id' => $model->id], [
                        'class' => 'btn btn-xs btn-success btn-block',
                        'data-method' => 'post',
                        'data-confirm' => Yii::t('user', 'Are you sure you want to unblock this user?')
                    ]);
                } else {
                    return Html::a(Yii::t('user', 'Block'), ['block', 'id' => $model->id], [
                        'class' => 'btn btn-xs btn-danger btn-block',
                        'data-method' => 'post',
                        'data-confirm' => Yii::t('user', 'Are you sure you want to block this user?')
                    ]);
                }
            },
            'format' => 'raw',
        ],
    ],
]);

Pjax::end();
