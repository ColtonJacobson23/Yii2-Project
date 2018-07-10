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
 * @var yii\widgets\ActiveForm    $form
 * @var dektrium\user\models\User $user
 */

use kartik\widgets\DateTimePicker;
use yii\helpers\Url;
use yii\web\View;

?>
<style>
  .adminBox {
    border:1px #e8e8e8 solid;
    margin-bottom:20px;
  }
  .adminTitle {
    background-color: #e7e7e7;
    width:100%;
    padding:5px 5px 5px 5px;
    font-width: bold;
  }
  .adminContents {
    padding:20px 5px 0 0;
  }
</style>

<?php
//Check if this is a create scenario:
$create = $user->scenario === 'create' ? true : false;

//update password not expire
if (empty($user->passwordExpire) || $user->passwordExpire == '0000-00-00 00:00:00')
{
    $user->_pwNotExpire = true;
}
?>


<div class="adminBox">
  <div class="adminTitle">Current Status</div>
  <div class="adminContents">
    <?= $form->field($user, 'status')->dropDownList(\overrides\Dektrium_User\models\User::itemAlias('UserStatus')) ?>

    <div class="form-group field-user-status">
      <label class="control-label col-sm-3" for="user-status">Account Expiration</label>
      <div class="col-sm-9">
      <?php
        echo DateTimePicker::widget([
             'model' => $user,
             'attribute' => 'accountExpire',
             'options' => ['placeholder' => 'No account expiration'],
             'convertFormat' => false,
             'pluginOptions' => [
                 'format' => 'yyyy-mm-dd hh:ii:ss',
                 'startDate'=> date("Y-m-s"),
                 'todayHighlight' => true,
                 'autoclose'=>true,

             ]
         ]);
        ?>
      <div class="help-block help-block-error "></div>
      </div>
    </div>


    <?php if (!$create) {echo $form->field($user, 'failedLoginNumber')->textInput(['maxlength' => 2]);} ?>
  </div>
</div>

<div class="adminBox">
  <div class="adminTitle">Username and Email</div>
  <div class="adminContents">
    <?= $form->field($user, 'username')->textInput(['maxlength' => 25]) ?>
    <?= $form->field($user, 'email')->textInput(['maxlength' => 255]) ?>
  </div>
</div>

  <div class="adminBox">
    <div class="adminTitle">Password</div>
    <div class="adminContents">
        <div style="padding:10px;color:green">TODO: Show encryption type. Allow it to generate sha-512 passwords.</div>


      <?= $form->field($user, '_randomEmail')->checkbox()->label('Create and send random password in email to user that will expire in 30 days') ?>
      <div class="manualpw">
      <?php
        echo $form->field($user, 'password')->textInput();
            //$form->field($user, 'password')->passwordInput()
      ?>

       <?= $form->field($user, '_pwNotExpire')->checkbox()->label('Password does not expire') ?>


      <div class="form-group field-user-status">
        <label class="control-label col-sm-3" for="user-status">Password Expiration</label>
        <div class="col-sm-9">
        <?php
          echo DateTimePicker::widget([
               'model' => $user,
               'attribute' => 'passwordExpire',
        //       'value' =>date("Y-m-d"),// date('yyyy-mm-dd hh:ii:ss', strtotime('+30 days')),
          //     'options' => ['value' => date('Y-m-d 00:00:00', strtotime('+30 days'))],//'Select password expiration ...'], //no don't modify. Show current value
               'convertFormat' => false,
               'pluginOptions' => [
                   'format' => 'yyyy-mm-dd hh:ii:ss',
                   'startDate'=> date("Y-m-d"),
                   'todayHighlight' => true,
             //      'pickerPosition'=>'top-left',
                   'autoclose'=>true,
               ],
                'pluginEvents' => [
                  "hide" => "function(e) { $('#user-_pwnotexpire').removeAttr('checked');}",
                ]
           ]);
          ?>
            <div class="help-block help-block-error "></div>
        </div>
      </div>
        <?= $form->field($user, '_autoEmail')->checkbox()->label('Send an email to the user with the new password') ?>
        <?php if (!$create) {echo $form->field($user, 'lastPasswordChange')->textInput(['maxlength' => 25]);} ?>
      </div>
    </div>
  </div>

<?php
    $this->registerJs("
    $('#user-_randomemail').click(function() {
        $(\".manualpw :input\").attr(\"disabled\", false);
        if ( $(this).is(':checked'))
        {
         $(\".manualpw :input\").attr(\"disabled\", true);
        }
    });
    
        $('#user-_pwnotexpire').click(function() {
        $(\"#user-passwordexpire\").attr(\"disabled\", false);
        if ( $(this).is(':checked'))
        {
         $(\"#user-passwordexpire\").attr(\"disabled\", true);
        }
    });
    
    ", View::POS_READY);

//



