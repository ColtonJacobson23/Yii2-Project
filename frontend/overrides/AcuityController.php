<?php
  namespace overrides;

  use yii;

  /**
   * Class ControllerAcuity
   * All Acuity Controllers should branch off of this
   * @package common\components
   * @author Rick Susfalk <rick.susfalk@dri.edu>
   * Funding: Project
   */
  class AcuityController extends \yii\web\Controller
  {
    public function init()
    {
      //check if you have access to this portal.
      //If logged in user does not have access to  this portal, throw an error.
      //If only guest user access, then allow the controller or local RBAC checks hanle it.

      if (!Yii::$app->user->isGuest)
      {
        if (!Yii::$app->user->identity->checkPortalAccess(true))
        {
          throw new yii\base\UserException('You do not have permission to access this feature or portal. Please contact us if you believe this is a mistake.');
        }
      }

      return;
    }


    //========================================================
    //9/18/12 RBS
    //If you have timed out and are a guest, force the login page to load
    //This action must be specified in filters() before the accessControl filter
    //this only works on an ajax call. If it is hand loaded into browser, this will not work!
    //========================================================
    public function filterAjaxLoginRedirect($filterChain)
    {
      if (Yii::$app->user->isGuest && Yii::$app->request->isAjaxRequest && !Yii::$app->user->checkAccess('roleGuest')) {
        echo "Your login has expired.";
        echo Html::a('Please login again', ['/site/index'], ['class' => 'profile-link']);

        echo '<script type="text/javascript">
                window.location.href = "/site/index";
              </script>';
      } else {
        // call $filterChain->run() to continue filter and action execution
        $filterChain->run();
      }
    }


    /**
     * @inheritdoc
     */
    public function actions()
    {
      return [
        'error'   => [
          'class' => 'yii\web\ErrorAction',
        ],
      ];
    }

  }