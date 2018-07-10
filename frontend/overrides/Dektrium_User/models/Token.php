<?php

/*
 * This file is modifed from the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace overrides\Dektrium_User\models;

use dektrium\user\traits\ModuleTrait;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * Token Active Record model.
 *
 * @property integer $user_id
 * @property string  $code
 * @property integer $created_at
 * @property integer $type
 * @property string  $url
 * @property bool    $isExpired
 * @property User    $user
 *
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
class Token extends \dektrium\user\models\token
{
      /** @inheritdoc */
      public static function tableName()
      {
         //override table name
          $aManager = \Yii::$app->getAuthManager();
          //If in backend portal, choose backend table. Needed to be able to login to the backend portal
        //  if (!empty($aManager->adminBackendOnly['db']) && \Yii::$app->user->getIsGuest())
        //  {
        //    return $aManager->adminBackendOnly['userProfiles'];
        //  }
        //  elseif (empty($aManager))
        if (empty($aManager))
          {
            //Trap for EventMsg called by console that does not have an authorization manager
            //This will work for all console requests to user (e.g. look up a given user)
            if (!empty(\Yii::$app->params['consoleAuth']) && !empty(\Yii::$app->params['consoleAuth']['userTables']['tokens']))
            {
              return \Yii::$app->params['consoleAuth']['userTables']['tokens'];
            }
          }
          //return default from the authManager
          return $aManager->userTables['tokens'];
      }

  public static function getDb()
  {
    $aManager = \Yii::$app->getAuthManager();
   // if (!empty($aManager->adminBackendOnly['db']) && \Yii::$app->user->getIsGuest())
   // {
      //adminBackend Only. Requried to allow user to actually login to the backend.
   //   return \Yii::$app->get($aManager->adminBackendOnly['db']);
   // }
   // elseif (empty($aManager))

    if (empty($aManager))
    {
      //Trap for EventMsg called by console that does not have an authorization manager
      //This will work for all console requests to user (e.g. look up a given user)
      if (!empty(\Yii::$app->params['consoleAuth']) && !empty(\Yii::$app->params['consoleAuth']['userTables']['db']))
      {
        return \Yii::$app->get(\Yii::$app->params['consoleAuth']['userTables']['db']);
      }
    }
    elseif (empty($aManager->userTables['db']))
    {
      $msg = "The authManager userTables have not been properly specified.";
      if (is_a(\Yii::$app,'yii\console\Application')) {error_log($msg);} else {echo $msg;}
      exit();
    }
    return \Yii::$app->get($aManager->userTables['db']);
  }
}
