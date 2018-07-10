<?php
  namespace overrides\Dektrium_User\models;

//See link to modify profile fields
//https://github.com/dektrium/yii2-user/blob/master/docs/adding-new-field-to-user-model.md

//Override Class so that Dektrium/User can use legacy Yii1 user tables from Acuity
//Requires add the following fields to the legacy table:
//		confirmed_at, unconfirmed_email, blocked_at, registration_ip, updated_at, flags
//
/*
   ALTER TABLE `yii`.`yuser_remas_users`
      ADD COLUMN `confirmed_at` INT (11) DEFAULT NULL AFTER `previousPinSuffices`,
      ADD COLUMN `unconfirmed_email` VARCHAR (255) DEFAULT NULL AFTER `confirmed_at`,
      ADD COLUMN `blocked_at` INT (11) NOT NULL AFTER `unconfirmed_email`,
      ADD COLUMN `registration_ip` VARCHAR (45) DEFAULT NULL AFTER `blocked_at`,
      ADD COLUMN `updated_at` INT (11) NOT NULL AFTER `registration_ip`,
      ADD COLUMN `flags` INT (11) NOT NULL DEFAULT 0 AFTER `updated_at`;
*/
// Events below (afterFind, beforeSave) are intercepted to place information already existing in the 
//		legacy table into the Dektrium/User object. This includes:
//		Dektrium				    Legacy
//		created_at    <->   strtotime($this->create_at); <-note legacy uses human readable!
//		password_hash <->   $this->password;
//		auth_key      <->   $this->activkey;
//
//	Following fields are the same in both tables.
//		Dektrium	    Legacy
//		id			      id
//		username	    username	ACTION: need to change to varchar(25)
//		email		      email		ACTION: need to change to varchar(255)

// NOTE -- NOTE -- NOTE
// In converting RBAC

namespace overrides\Dektrium_User\models;

use yii\db\Expression;
use dektrium\user\helpers\Password;
use dektrium\user\models\User as BaseUser;


class User extends BaseUser
{

   //Below variables are in both legacy user table and in Dektrium user table
   //However, they are called different things, so need to be changed
   //These variables are the Dektrium variables, and are populated via event methods below.
   public $created_at;
   public $password_hash;
   public $auth_key;

   public $unconfirmed_email;


   public $portal_list; //tracks portals

   public $last_login_at; //2/3/17 for compatibility with new user version 0.9.12

  public $_autoEmail = true; //internal setting. User profile page - send email with request
  public $_randomEmail = false; //internal setting. User profile page - send email with random password
  public $_pwNotExpire = false;

  //Added constants
  const STATUS_NOACTIVE=0;
 	const STATUS_ACTIVE=1;
 	const STATUS_BANNED=-1;
  const STATUS_EMAIL_INVALID=-2;
  const STATUS_ACCOUNT_EXPIRED=-3;
  const STATUS_ACCOUNT_TO_MANY_BADLOGINS = -4;
  const STATUS_INACTIVITY = -5;
  const STATUS_PASSWORD_INACTIVITY = -6;
  const STATUS_PASSWORD_EXPIRED = -7;

   //Note ORDektriumUser_User.php is currently hardcoded to use the following db:
    //a. Use the database defined as db_yii (see static::getDb())
    //b. Use yuser_remas_users table if Yii::$app->params['systemName']=="REMAS"
    //          else use yuser_acuity_users


   //override the DB
  //4/19/17 RBS check if this is a backend portal and if you are a guest, then use yuser_admin settings to login
    public static function getDb()
    {
      $aManager = \Yii::$app->getAuthManager();
      if (!empty($aManager->adminBackendOnly['db']) && \Yii::$app->user->getIsGuest())
      {
        //adminBackend Only. Requried to allow user to actually login to the backend.
        return \Yii::$app->get($aManager->adminBackendOnly['db']);
      }
      elseif (empty($aManager))
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
   
	  //override the table name
    //7/4/15 RBS This depends on the systemName defined in the portals parameters
    //Not the best solution, but no easy way to pass in module settings
    //2/3/17 RBS change to settings
  //4/19/17 RBS check if this is a backend portal and if you are a guest, then use yuser_admin settings to login
    public static function tableName()
    {
      $aManager = \Yii::$app->getAuthManager();
      //If in backend portal, choose backend table. Needed to be able to login to the backend portal
      if (!empty($aManager->adminBackendOnly['db']) && \Yii::$app->user->getIsGuest())
      {
        return $aManager->adminBackendOnly['userUsers'];
      }
      elseif (empty($aManager))
      {
        //Trap for EventMsg called by console that does not have an authorization manager
        //This will work for all console requests to user (e.g. look up a given user)
        if (!empty(\Yii::$app->params['consoleAuth']) && !empty(\Yii::$app->params['consoleAuth']['userTables']['users_table']))
        {
          return \Yii::$app->params['consoleAuth']['userTables']['users_table'];
        }
      }
      //return default from the authManager
      return $aManager->userTables['users_table'];
     // return \Yii::$app->getAuthManager()->userTables['users_table'];
    }


    //Add new fields to rules
  public function rules()
  {
      $rules = parent::rules();

      $rules[] = [['accountExpire','lastPasswordChange','passwordExpire'], 'default', 'value' => null];
     //If allow following rule, will not accept 0000-00-00 00:00:00
     // $rules[] = [['accountExpire','lastPasswordChange','passwordExpire'], 'date', 'format'=>'yyyy-MM-dd HH:mm:ss'];

      $rules[] = [['status','failedLoginNumber'], 'integer', 'integerOnly'=>true];

      $rules[] = [['pinPrefix', 'pinSuffix'], 'integer', 'integerOnly'=>true];
      $rules[] = ['pinPrefix', 'string', 'max'=>2, 'min' => 2,'message' =>"Pin Prefix must be 2 digits"];
      $rules[] = ['pinPrefix', 'match', 'pattern' => '/^[0-8]{2}$/','message' =>"Pin Prefix can not contain a '9'."];
      $rules[] = ['pinSuffix', 'string', 'max'=>4, 'min' => 4,'message' => "Pin Suffix must be 4 digits"];
      $rules[] = ['pinSuffix', 'match', 'pattern' => '/^[0-8]{4}$/','message' =>"Pin Prefix can not contain a '9'."];
      $rules[] = ['pinLastChange', 'default', 'value' => null];

//testing
    //$rules[] = ['passwordExpire', 'required','on' => ['create']];

      return $rules;
  }

  //Add new fields to scenarios
  public function scenarios()
  {
      $scenarios = parent::scenarios();

      //Add to these scenarios
      $scenarios['create'] = array_merge( $scenarios['create'],     ['status', 'accountExpire','passwordExpire']);
      $scenarios['update'] = array_merge( $scenarios['update'],     ['status', 'failedLoginNumber', 'accountExpire','passwordExpire', 'lastPasswordChange']);
      $scenarios['settings'] = array_merge( $scenarios['settings'], ['stauts', 'failedLoginNumber', 'accountExpire','passwordExpire', 'lastPasswordChange']);

      //Create new scenarios
      $scenarios['accountUpdate'] = ['status', 'accountExpire', 'failedLoginNumber', 'email', 'username', 'passwordExpire', 'password', '_autoEmail', '_autoEmail','lastPasswordChange'];
      $scenarios['PINupdate'] = ['pinPrefix', 'pinSuffix', 'pinLastChange'];

   //   $scenarios['create'][]   = 'updated_at';

      return $scenarios;
  }

  //Add new fields to attributeLabels()

  /**
   * @inheritdoc
   */
  public function attributeLabels()
  {
      $labels = parent::attributeLabels();
      $labels['updated_at'] = 'Updated at';
      $labels['lastvisit_at'] = 'Last Visited';
      return $labels;
  }

  //Alias list (based on Yii1 code)
  public static function itemAlias($type,$code=NULL) {
 		$_items = array(
 			'UserStatus' => array(
 				self::STATUS_NOACTIVE => 'Not active',
 				self::STATUS_ACTIVE => 'Active/Confirmed',
 				self::STATUS_BANNED => 'Banned/Blocked',
        self::STATUS_EMAIL_INVALID =>'Email Invalid',
        self::STATUS_ACCOUNT_EXPIRED =>'Expired',
        self::STATUS_ACCOUNT_TO_MANY_BADLOGINS => 'Inactive - Exceeded login attempts',
        self::STATUS_INACTIVITY => 'Inactive - No activity',
        self::STATUS_PASSWORD_INACTIVITY => 'Inactive - Password not updated in time',
        self::STATUS_PASSWORD_EXPIRED => 'Inactive - Password expired',
    	),
 		);
 		if (isset($code))
 			return isset($_items[$type][$code]) ? $_items[$type][$code] : false;
 		else
 			return isset($_items[$type]) ? $_items[$type] : false;
 	}




  /**
   * Gets a list of portals this user has access to.
   * REQUIRES in config/main:
   * 'components' => [
   *      'authManager' => [
   *        'class'           => 'overrides\Dektrium_Rbac\DbManager',
   *         ....
   *        'portalAccess'    =>  ['db'=>'db_yii',
   *                               'table'=>'yuser_acuity_portal_access'
   *                                    ],
   *        'portalList'      => ['db'=>'db_yii',
   *                              'table'=>'yuser_acuity_portal_access'
   *                             ],
   * @return array
   * @author Rick Susfalk <rick.susfalk@dri.edu>
   */
  public function getPortalList()
  {
    if (empty($this->portal_list)) {$this->checkPortalAccess();}
    $authManager = \Yii::$app->getAuthManager();
    return (new \yii\db\Query())->select(['portal_id'])->from($authManager->portalList['table'])->where(['portal_id' =>$this->portal_list])->all(\common\components\acuityDb::getDbConnection($authManager->portalList['db']));
  }



  /**
   * Check if user has access to this particular portal.
   * Loads the full user portal list if necessary and caches it.
   * REQUIRES in config/main:
   * 'components' => [
   *      'authManager' => [
   *        'class'           => 'overrides\Dektrium_Rbac\DbManager',
   *         ....
   *        'portalAccess'    =>  ['db'=>'db_yii',
   *                               'table'=>'yuser_acuity_portal_access'
   *                                    ],
   *        'portalList'      => ['db'=>'db_yii',
   *                              'table'=>'yuser_acuity_portal_access'
   *                             ],
   * @ignoreCache bool If true, ignore Cache
   * @forceReturn bool = false.  If calling this function recursively, this will force the result to be returned before it checks again before a recursive call should be made.
   * @return bool
   * @author Rick Susfalk <rick.susfalk@dri.edu>
   */
  public function checkPortalAccess($ignoreCache = false, $forceReturn = false)
  {
    if (empty($this->portal_list) || $ignoreCache)
    {
      $this->portal_list = \Yii::$app->session->get('userPortalList', false);
      if ($this->portal_list  === false || $ignoreCache)
      {
        $authManager = \Yii::$app->getAuthManager();

        //Check $authManager->adminBackendOnly  -- should be non-empty only in the backend portals
        if (empty($portals) && !empty($authManager->adminBackendOnly) && !empty($authManager->adminBackendOnly['userPortalAccess']))
        {
          //for admin portal users
          $portals = (new \yii\db\Query())->select(['portal_id'])->from($authManager->adminBackendOnly['userPortalAccess'])->where(['user_id' => \Yii::$app->user->identity->id])->all(\common\components\acuityDb::getDbConnection($authManager->adminBackendOnly['db']));
        }
        else
        {
          //For normal users
          $portals = (new \yii\db\Query())->select(['portal_id'])->from($authManager->portalAccess['table'])->where(['user_id' => \Yii::$app->user->identity->id])->all(\common\components\acuityDb::getDbConnection($authManager->portalAccess['db']));
        }

        if (!empty($portals))
        {
          $this->portal_list = array_column($portals, 'portal_id');
          \Yii::$app->session->set('userPortalList', $this->portal_list);
        }
      }
    }

    //If this is a recursive run, force return its value.
    if ($forceReturn) {return in_array(\Yii::$app->id, $this->portal_list);}

    //If this is a normal run and it's not found, update array
    if (!in_array(\Yii::$app->id, $this->portal_list))
    {
      $this->checkPortalAccess(true, true);
    }

//print_r([ \Yii::$app->user->identity->id, \Yii::$app->id, $this->portal_list]);exit();

    return in_array(\Yii::$app->id, $this->portal_list);
  }

  /**
   * Confirms the user by setting 'confirmed_at' field to current time.
   */
  public function confirm()
  {
      parent::confirm();
      return (bool) $this->updateAttributes(['status' => self::STATUS_ACTIVE]);
  }

  /**
   * Blocks the user by setting 'blocked_at' field to current time.
   */
  public function block()
  {
    parent::block();
      return (bool) $this->updateAttributes(['status' => self::STATUS_BANNED]);
  }

  /**
   * UnBlocks the user by setting 'blocked_at' field to null.
   */
  public function unblock()
  {
    parent::unblock();
      return (bool) $this->updateAttributes(['status' => self::STATUS_NOACTIVE]);
  }

          //RELATIONSHIPS
    public function getProfile()
    {
        //8/9/15 RBS
        //Do a workaround to use a table alias on the joined table.
        return $this->hasOne(Profile::className(), ['user_id' => 'id'])->from(['tProfile' => Profile::tableName()]);
    }


  /**
   * @inheritdoc
   */
  public function getIsConfirmed()
  {
    return ($this->status == self::STATUS_ACTIVE) ? true : false;

    //return $this->confirmed_at != null;
  }

  /**
   * @inheritdoc
   */
  public function getIsBlocked()
  {
    return ($this->status == self::STATUS_BANNED) ? true : false;
     // return $this->blocked_at != null;
  }


	//3/14/15 RBS
	//Convert between legacy User DB table and the Dektrium User object
	//Dektrium = legacy
	public function afterFind()
	{
		parent::afterFind(); //always call parent!

    $this->created_at = strtotime($this->create_at);
		$this->last_login_at = strtotime($this->lastvisit_at); //2/3/17 RBS
		$this->auth_key = $this->activkey;
				
		//Need to convert password from legacy acuity pw version "v2@$@" to crypt() prefix "$2Y$13$"
		//NOTE: this requires 13 rounds for legacy compatibility!!!! <--must be set in USER settings
		$this->password_hash = "$2y$13$".substr($this->getAttribute('password'),5);
		$this->setAttribute('password', $this->password_hash);

    return true;
	}

	private function checkTimeStampFromUnixTime($ts)
  {
    if ($ts < 1)
    {
      return "0000-00-00 00:00:00";
    }
    else
    {
      return strftime("%Y-%m-%d %H:%M:%S", $ts);
    }
  }

	//3/14/15 RBS
	//Convert between legacy User DB table and the Dektrium User object
	//legacy = Dektrium
	//Before Save event
	//http://www.yiiframework.com/doc-2.0/yii-db-baseactiverecord.html#beforeSave()-detail
	public function beforeSave($insert)
	{
		//Generally, you always want to fire the parent -- but not in this case. We want to 
		//overwrite the parent as we are using different db fields!
	
		$this->create_at =  $this->checkTimeStampFromUnixTime($this->created_at);
    $this->lastvisit_at =  $this->checkTimeStampFromUnixTime($this->last_login_at);

    //Will stop creation of new user if not specified.
    $this->password2 = ""; //set default for this old field
    if (empty($this->blocked_at)) {$this->blocked_at = 0;}
    $this->pinLastChange = "0000-00-00 00:00:00";

		$this->activkey = $this->auth_key;
	
		//Code below here is modifed from Dektrium/User
	    if ($insert) {
        //REMOVED 7/9/15 RBS --- $this->create_at = new Expression("NOW()"); //ADDED
        $this->create_at = new Expression("NOW()"); //added back 2/7/17
        $this->user_description = "";//ADDED, set default
        $this->salt = "";//ADDED, set default
        //REMOVED 7/9/15 RBS --- $this->password2 = ""; //ADDED, set defualt
        $this->auth_key = \Yii::$app->security->generateRandomString();//ADDED
        $this->setAttribute('activkey', $this->auth_key); //CHANGE FROM auth_key TO activkey
        if (\Yii::$app instanceof \yii\web\Application) {
            $this->setAttribute('registration_ip', \Yii::$app->request->userIP);
        }
      }

    //7/9/15 RBS force other empty times to be zeroed out.
    if (empty($this->accountExpire))  {$this->accountExpire = "0000-00-00 00:00:00";}
    if (empty($this->passwordExpire)) {$this->passwordExpire = "0000-00-00 00:00:00";}


    //--Handle password
    //$this->password is plaintext version
    //$this->password_hash is the hashed version
   // echo  "USER:381: ++}".$this->password."}++   --]".$this->password_hash."[--<br>\n";

    if (!empty($this->password))
    {
      //Get pw hash and set model variables
      //NOTE: this requires 13 rounds for legacy compatibility!!!! <--must be set in USER settings
      $myPass = Password::hash($this->password);
      $this->setAttribute('salt', substr($myPass,7,22));
      //REPLACE $2Y$##$ with "v2@$@" to match legacy.
      $this->setAttribute('password', "v2@$@".substr($myPass,7));
      $this->password_hash = $this->getAttribute('password');
    }
    elseif (!empty($this->password_hash))
    {
      //if password is empty and we already have an existing hash, use it.
      $this->setAttribute('password', $this->password_hash);
    }

  //  echo  "USER:408: ++}".$this->password."}++   --]".$this->password_hash."[--<br>\n";exit();

		//Other pieces to do:
    if (empty($this->create_at)) {$this->create_at='0000-00-00 00:00:00';}

    //If pw is changed, note it
    if ($this->isAttributeChanged('password'))
    {
      $this->lastPasswordChange = new Expression('NOW()');
    }
    //If pin is changed, note it
    if ($this->isAttributeChanged('pinSuffix'))
    {
      $this->pinLastChange = new Expression('NOW()');
    }

    //SKIP parent, call grandparent. Parent will try to hash password again!
		$grandparent = get_parent_class(get_parent_class($this));
    return $grandparent::beforeSave($insert); //parent::beforeSave($insert);
	}

  /**
   * Resets password.
   *
   * @param string $password
   *
   * @return bool
   */
  public function resetPassword($password)
  {
    $this->password = $password;
    return (bool)$this->save();
  }

}
