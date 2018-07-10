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
 * @var $this         \yii\web\View
 * @var $searchModel  \dektrium\rbac\models\RuleSearch
 * @var $dataProvider \yii\data\ArrayDataProvider
 */

use kartik\select2\Select2;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\Pjax;

$this->title = Yii::t('yii2-rbac', 'Rules');
$this->params['breadcrumbs'][] = $this->title;

?>

<?php $this->beginContent('@dektrium/yii2-rbac/views/layout.php') ?>

<?php Pjax::begin() ?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel'  => $searchModel,
    'layout'       => "{items}\n{pager}",
    'columns'      => [
        [
            'attribute' => 'name',
            'label'     => Yii::t('yii2-rbac', 'Name'),
            'options'   => [
                'style' => 'width: 20%'
            ],
            'filter' => Select2::widget([
                'model'     => $searchModel,
                'attribute' => 'name',
                'options'   => [
                    'placeholder' => Yii::t('yii2-rbac', 'Select rule'),
                ],
                'pluginOptions' => [
                    'ajax' => [
                        'url'      => Url::to(['search']),
                        'dataType' => 'json',
                        'data'     => new JsExpression('function(params) { return {q:params.term}; }')
                    ],
                    'allowClear' => true,
                    
                ],
            ]),
        ],
        [
            'attribute' => 'class',
            'label'     => Yii::t('yii2-rbac', 'Class'),
            'value'     => function ($row) {
                $rule = unserialize($row['data']);

                return get_class($rule);
            },
            'options'   => [
                'style' => 'width: 20%'
            ],
        ],
        [
            'attribute' => 'created_at',
            'label'     => Yii::t('yii2-rbac', 'Created at'),
            'format'    => 'datetime',
            'options'   => [
                'style' => 'width: 20%'
            ],
        ],
        [
            'attribute' => 'updated_at',
            'label'     => Yii::t('yii2-rbac', 'Updated at'),
            'format'    => 'datetime',
            'options'   => [
                'style' => 'width: 20%'
            ],
        ],
        [
            'class'      => ActionColumn::className(),
            'template'   => '{update} {delete}',
            'urlCreator' => function ($action, $model) {
                return Url::to(['/yii2-rbac/rule/' . $action, 'name' => $model['name']]);
            },
            'options'   => [
                'style' => 'width: 5%'
            ],
        ]
    ],
]) ?>

<?php Pjax::end() ?>

<?php $this->endContent() ?>
