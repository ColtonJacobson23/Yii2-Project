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
 * @var $this     yii\web\View
 * @var $content string
 */

use dektrium\rbac\widgets\Menu;
use kartik\widgets\Select2;
use yii\web\View;
use yii\web\Request;

?>

<?= Menu::widget() ?>

<?php
    //RBS - inject portal selector
    print_r($this->context->module->rbacPortal->output($this));
?>



<div style="padding: 10px 0">
    <?= $content ?>
</div>