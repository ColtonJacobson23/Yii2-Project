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
 * @var $model dektrium\rbac\models\Role
 * @var $this  yii\web\View
 */

$this->title = Yii::t('yii2-rbac', 'Create new role');
$this->params['breadcrumbs'][] = $this->title;

?>

<?php $this->beginContent('@dektrium/yii2-rbac/views/layout.php') ?>

<?= $this->render('_form', [
    'model' => $model,
]) ?>

<?php $this->endContent() ?>