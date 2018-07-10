<?php

//See link to modify profile fields
//https://github.com/dektrium/yii2-user/blob/master/docs/adding-profile-fields-to-registration-form.md

//Override Class so that Dektrium/Profile can use legacy Yii1 user tables from Acuity
//Requires add the following fields to the legacy table:
//		XXXXXX
//
/*
   SQL XXXXXX
*/
// Events below (afterFind, beforeSave) are intercepted to place information already existing in the 
//		legacy table into the Dektrium/User object. This includes:
//		Dektrium				    Legacy

//	Following fields are the same in both tables.




namespace overrides\Dektrium_User\models;

use yii\db\Expression;
use dektrium\user\helpers\Password;
use dektrium\user\models\Profile as BaseProfile;

class Profile extends BaseProfile
{

    public $gravatar_id   = null; //compatibility with old system. Placeholder awaiting new DB structure.
    public $name          = ''; //compatibility with old system. Placeholder awaiting new DB structure.
    public $public_email  = ''; //compatibility with old system. Placeholder awaiting new DB structure.
    public $website       = ''; //compatibility with old system. Placeholder awaiting new DB structure.
    public $location      = ''; //compatibility with old system. Placeholder awaiting new DB structure.


   //Note ORDektriumUser_User.php is currently hardcoded to use the following db:
    //a. Use the database defined as db_yii (see static::getDb())
    //b. Use yuser_remas_profile table if Yii::$app->params['systemName']=="REMAS"
    //          else use yuser_acuity_profile

   //override the DB
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
   
    //override table name
    public static function tableName()
    {
      $aManager = \Yii::$app->getAuthManager();
      //If in backend portal, choose backend table. Needed to be able to login to the backend portal
      if (!empty($aManager->adminBackendOnly['db']) && \Yii::$app->user->getIsGuest())
      {
        return $aManager->adminBackendOnly['userProfiles'];
      }
      elseif (empty($aManager))
      {
        //Trap for EventMsg called by console that does not have an authorization manager
        //This will work for all console requests to user (e.g. look up a given user)
        if (!empty(\Yii::$app->params['consoleAuth']) && !empty(\Yii::$app->params['consoleAuth']['userTables']['profiles']))
        {
          return \Yii::$app->params['consoleAuth']['userTables']['profiles'];
        }
      }
      //return default from the authManager
      return $aManager->userTables['profiles'];
    }

  //Add new fields to rules
  public function rules()
  {
      $rules = parent::rules();
      $rules[] = ['firstname', 'required'];
      $rules[] = ['firstname', 'string', 'max' => 255];
      $rules[] = ['lastname', 'required'];
      $rules[] = ['lastname', 'string', 'max' => 255];
      return $rules;
  }

  //Add new fields to scenarios
  public function scenarios()
  {
    $scenarios = parent::scenarios();
    //Create/override new scenarios
    $scenarios['create']    = ['firstname', 'lastname', 'company'];
    $scenarios['update']    = ['firstname', 'lastname', 'company'];
    $scenarios['register']  = ['firstname', 'lastname', 'company'];
    $scenarios['default']   = ['firstname', 'lastname', 'company'];

      return $scenarios;
  }

  //Add new fields to attributeLabels()

  /**
   * @inheritdoc
   */
  public function attributeLabels()
  {
      $labels = parent::attributeLabels();
      $labels['firstname'] = 'First Name';
      $labels['lastname'] = 'Last Name';
      return $labels;
  }


	//3/14/15 RBS
	//Convert between legacy User DB table and the Dektrium User object
	//Dektrium = legacy
	public function afterFind()
	{
		parent::afterFind(); //always call parent!

    /*$this->created_at = strtotime($this->create_at);
		$this->auth_key = $this->activkey;
				
		//Need to convert password from legacy acuity pw version "v2@$@" to crypt() prefix "$2Y$13$"
		//NOTE: this requires 13 rounds for legacy compatibility!!!! <--must be set in USER settings
		$this->password_hash = "$2y$13$".substr($this->getAttribute('password'),5);
		$this->setAttribute('password', $this->password_hash);
		*/
        return true;
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
	/*
		$this->create_at =  $this->created_at;//strftime("%Y-%m-%d %H:%M:%S", $this->created_at);
		
		if (!empty($this->password_hash)) {$this->password = $this->password_hash;} //not always needed, eg pw update
		$this->activkey = $this->auth_key;
	
		//Code below here is modifed from Dektrium/User
	    if ($insert) {
			$this->create_at = new Expression("NOW()"); //ADDED
			$this->user_description = "";//ADDED, set default 
			$this->salt = "";//ADDED, set default
			$this->password2 = ""; //ADDED, set defualt
			$this->auth_key = \Yii::$app->security->generateRandomString();//ADDED
            $this->setAttribute('activkey', $this->auth_key); //CHANGE FROM auth_key TO activkey
            if (\Yii::$app instanceof \yii\web\Application) {
                $this->setAttribute('registration_ip', \Yii::$app->request->userIP);
            }
      }
				
      if (!empty($this->password)) {
			//Get pw hash
			//NOTE: this requires 13 rounds for legacy compatibility!!!! <--must be set in USER settings
			$myPass = Password::hash($this->password);
					
			//WARNING--REQUIRES START OF $2y$##$
			$salt = substr($myPass,7,22);
			$this->setAttribute('salt',$salt);		 

			//REPLACE $2Y$##$ with "v2@$@" to match legacy.
			$this->setAttribute('password', "v2@$@".substr($myPass,7));			 
           // $this->setAttribute('password_hash', Password::hash($this->password_hash)); //CHANGE FROM 'password_hash' TO 'password'
      }

		//Other pieces to do:
    if (empty($this->create_at)) {$this->create_at='0000-00-00 00:00:00';}
*/


		$grandparent = get_parent_class(get_parent_class($this));


        return $grandparent::beforeSave($insert); //parent::beforeSave($insert); SKIP parent, call grandparent

		//if (parent::beforeSave($insert)) {  //check with parent first
        //return true;
		//}
    //return false;
	}
	
	
}
