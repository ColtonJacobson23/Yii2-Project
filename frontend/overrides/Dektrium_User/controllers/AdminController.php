<?php

namespace overrides\Dektrium_User\controllers;

use Yii;
use yii\helpers\Url;
use dektrium\user\controllers\AdminController as BaseAdminController;
use yii\data\ArrayDataProvider;

use common\models\Portals;
use common\models\PortalList;
use dektrium\user\helpers\Password;
use dektrium\user\Mailer;


class AdminController extends BaseAdminController
{

  public $rbacPortal = null; //storage for RbacPortalComponent class


  public function init()
  {
    if (Yii::$app->session->get('adminUserMode', false) === true)
    {

      //override settings if currently in the adminUserMode. Use actionSwitchMode() to switch between them.
      $authManager = \Yii::$app->getAuthManager();
      $authManager->portalAccess['db'] = $authManager->adminBackendOnly['db'];
      $authManager->portalAccess['table'] = $authManager->adminBackendOnly['userPortalAccess'];
      $authManager->portalList['db'] = $authManager->adminBackendOnly['db'];
      $authManager->portalList['table'] = $authManager->adminBackendOnly['userPortalList'];
      $authManager->userTables['db'] = $authManager->adminBackendOnly['db'];
      $authManager->userTables['users_table'] =  $authManager->adminBackendOnly['userUsers'];
      $authManager->userTables['profiles'] =  $authManager->adminBackendOnly['userProfiles'];
      $authManager->userTables['profiles_fields'] =  $authManager->adminBackendOnly['userProfileFields'];

    }

    //For assignments, we need to override $authManager db tables and display the yii2-rbac portal selector
    //only works for /admin/
      $this->rbacPortal = new \overrides\Dektrium_Rbac\RbacPortalComponent();
      $this->rbacPortal->start();
  }

  /**
   * Update PIN number and information
   * @param $id User id you want to change
   * @return string|\yii\web\Response
   * @author Rick Susfalk <rick.susfalk@dri.edu>
   */
  public function actionUpdatePin($id)
  {
      Url::remember('', 'actions-redirect');
      $user = $this->findModel($id);
      $user->scenario = 'PINupdate';

      $this->performAjaxValidation($user);

      if ($user->load(Yii::$app->request->post()) && $user->save()) {
          Yii::$app->getSession()->setFlash('success', Yii::t('user', 'PIN details have been updated'));
          return $this->refresh();
      }

      return $this->render('_pin', [
          'user'    => $user,
      ]);
  }


  /**
   * @inheritdoc
   */
  public function actionUpdate($id)
  {
      Url::remember('', 'actions-redirect');
      $user = $this->findModel($id);
      $user->scenario = 'accountUpdate';
      $event = $this->getUserEvent($user);

      $this->performAjaxValidation($user);

      $this->trigger(self::EVENT_BEFORE_UPDATE, $event);
      $post = \Yii::$app->request->post();

      if ($user->load($post))
      {
        $sendEmail = false;
        //Other actions
        //a. if password does not expire, set it to empty
        if (!empty($post['User']['_pwNotExpire']) && $post['User']['_pwNotExpire'] == 1)
        {
          $user->passwordExpire = "0000-00-00 00:00:00";
        }

        //b. if random email, then set random password and 30 day pw expiration
        if (!empty($post['User']['_randomEmail']) && $post['User']['_randomEmail'] == 1)
        {
          $user->password = Password::generate(10);
          $user->passwordExpire = strftime("%Y-%m-%d %H:%M:%S", strtotime("+30 days", time()));
          $sendEmail = true;
        }

        //c. if want email on PW change. Only if password is not empty and has been changed!
        if (!empty($post['User']['_autoEmail']) && $post['User']['_autoEmail'] == 1)
        {
          if (!empty($user->password) && $user->isAttributeChanged('password'))
          {
            $sendEmail = true;
          }
        }

        $currentPw = $user->password;

        if ($user->save()) //will encrypt pw
        {
          $flash = ['success'=>[], 'danger'=>[]];
          //send email
          if ($sendEmail)
          {
//            if ($user->isAdmin) {
//                throw new ForbiddenHttpException(Yii::t('user', 'Password generation is not possible for admin users'));
//            }

            if ($user->mailer->sendGeneratedPassword($user, $currentPw)) {
              $flash['success'][] = \Yii::t('user', 'New Password has been generated and sent to user.');
            } else {
              $flash['danger'][] = \Yii::t('user', 'Error while trying to generate new password.');
            }
          }

          $flash['success'][] = \Yii::t('user', 'Account details have been updated.');

          \Yii::$app->getSession()->setFlash(empty($flash['danger']) ? 'success' : 'danger', implode(" ", $flash['success']+$flash['danger']) );

          $this->trigger(self::EVENT_AFTER_UPDATE, $event);
          return $this->refresh();
        }
        else
        {
          \Yii::$app->getSession()->setFlash('error', \Yii::t('user', 'An error occurred'));
        }
      }

      return $this->render('_account', [
          'user' => $user,
      ]);
  }


  /**
   * Update Portal Access information
   * @param $id User id that you want to update
   * @return array|string
   * @author Rick Susfalk <rick.susfalk@dri.edu>
   */
  public function actionUpdatePortals($id)
  {
    $authManager = \Yii::$app->getAuthManager();

      Url::remember('', 'actions-redirect');
      $user = $this->findModel($id);
      //$portals = $user->portals;
      //$portals->scenario = 'update';
     // $this->performAjaxValidation($portals);

    //capture if it is an ajax command
    if (Yii::$app->request->post())
    {
      $post = Yii::$app->request->post();
      // use Yii's response format to encode output as JSON
      \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
      $output = '';
      $message = 'Not a valid request'; //set default message

      if (!empty($post['hasEditable']))
      {
        $post['editableKey'] = filter_var( $post['editableKey'], FILTER_SANITIZE_STRING);
        if (!empty($post['access'])) {$post['access'] = filter_var( $post['access'], FILTER_VALIDATE_BOOLEAN);}

        $db = $authManager->portalList['db'];
        $entryExists = Yii::$app->$db->createCommand("SELECT * FROM ".$authManager->portalList['table']." WHERE portal_id=:portal_id")
                        ->bindParam(':portal_id', $post['editableKey'])
                        ->queryOne();

        if (!empty($entryExists))
        {
          $db = $authManager->portalAccess['db'];
          $p = Yii::$app->$db->createCommand("SELECT * FROM ".$authManager->portalAccess['table']." WHERE portal_id = :portal_id AND user_id = :id")
                   ->bindParam(':portal_id', $post['editableKey'])
                   ->bindParam(':id', $id)
                   ->queryOne();

          if (!empty($post['access']) && empty($p)) //access is the name of the element
          {
            $response = Yii::$app->$db->createCommand()->insert($authManager->portalAccess['table'], [
              'user_id' => $id,
              'portal_id' => $post['editableKey'],
              'user_id_vdv' => 0, //not supported in new version
              'start_url' => '', //must be something
            ])->execute();

            if ($response == 0)
            {
              $message = "Sorry, there was a database error!";
            }
            else
            {
              $output = '<span class="glyphicon glyphicon-ok text-success"></span>';
              $message = '';
            }
          }
          else
          {
            //Delete existing
            // DELETE (table name, condition)
            Yii::$app->$db->createCommand()->delete($authManager->portalAccess['table'], "user_id = " . $id . " AND portal_id ='" . $post['editableKey'] . "'")->execute();
            $output = '<span class="glyphicon glyphicon-remove text-danger"></span>';
            $message = '';
          }
        }
        else
        {
          $output = '';
          $message = 'The settings can not be modified until this project is added to the *_portal_list table';
        }
      }

      return ['output' => $output, 'message' => $message]; //default Editable AJAX response
    }

    //----Continue with developing the whole page
    //Get a list of portals by user
    $data = \common\components\acuityPortals::getPortalsList($id);

    //get dataProvider that returns list of portals and if user has access (via thier id)
    $dataProvider = new ArrayDataProvider([
        'allModels'=>$data,
        'totalCount'=>count($data),
        'key'=>'portal_id',
        'sort' => [
            'attributes' => [
                'portal_name',
                'user_id'
            ],
        ],
        'pagination' => [
            'pageSize' => 20,
        ],
    ]);

    //Count number of portals you have access to --
    $models = $dataProvider->getModels();
    $access = array_sum(array_column($models, 'access'));

      return $this->render('_portals', [
          'user'    => $user,
          'dataProvider'=> $dataProvider,
          'access'=>$access,
      ]);
  }

  /**
   * Controller action to switch mode between viewing poral users and viewing tha admin portal users
   * When (Yii::$app->session->get('adminUserMode', false) === true) you are viewing local users of the admin portal.
   * @author Rick Susfalk <rick.susfalk@dri.edu>
   */
  public function actionSwitchMode()
  {
    //switch mode
    if (Yii::$app->session->get('adminUserMode', false) === true)
    {
      Yii::$app->session->set('adminUserMode', false);
    } else {
      Yii::$app->session->set('adminUserMode', true);
    }

    return $this->redirect(['/user/admin/index']);

  }

  /**
   * @inheritdoc
   */
  public function actionInfo($id)
  {
    Url::remember('', 'actions-redirect');
    $user = $this->findModel($id);

    //get portal list reduced to only that accessible by user as well as the direct RBAC roles assigned to the user.
    $portalList = \common\components\acuityPortals::getPortalsList($id, true, true);

    return $this->render('_info', [
        'user' => $user,
        'portalList' => $portalList,
    ]);





  }




}