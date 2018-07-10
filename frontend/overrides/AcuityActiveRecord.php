<?php

namespace overrides;
use common\components\acuityDb;
use common\components\acuityModule;


/**

 */
class AcuityActiveRecord extends \yii\db\ActiveRecord
{

    //Call the child classes version of this function
    public static function partialTableName()
    {
        return static::partialTableName();
    }


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
      $tablePrefix = self::getTablePrefix();
      //----STEP 1. If called from within module that supports this functionality by checking for 'createTableName' function
      if (!empty($tablePrefix) && \method_exists(\Yii::$app->controller->module, 'createTableName'))
      {
        \Yii::$app->controller->module->project_prefix = $tablePrefix;
        //Use Module Version, not the local version
        return  \Yii::$app->controller->module->createTableName(self::partialTableName());
      }

     //----STEP 2. If get here, you aren't inside acuity3 or ybackend modules,
      // so then check GET and POST for id or check the global variable 'project_dbPrefix'
     if (!empty($tablePrefix))
     {
         return self::createTableName(self::partialTableName(), $tablePrefix);
     }

     //else, error.
      \Yii::$app->acuityLog->addEntry(89, 'Unable to get table name. Not parsing Project Id ok', '', '','', 'error','');
    }

    /**
     * If you have to pull an id from $_GET or $_POST, it'll use the acuity component's definded db
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        //Is it defined in the parent module (e.g. acuity module or ybackend module)?
        if (isset(\Yii::$app->controller->module->dbSource))
        {
            return \Yii::$app->get(\Yii::$app->controller->module->dbSource);
        }

        //Otherwise, just use that from the acuity module settings
        if (!empty(\Yii::$app->getModule('acuity')->dbSource))
        {
            //print_r(acuityDb::getDbConnection(\Yii::$app->getModule('acuity')->dbSource));exit();
            return acuityDb::getDbConnection(\Yii::$app->getModule('acuity')->dbSource);
        }

        //Lastly, try a global parameter
        if (isset(\Yii::$app->params['project_db']) && \Yii::$app->params['project_db'] != false)
        {
          return acuityDb::getDbConnection(\Yii::$app->params['project_db']);
        }


    }

    //Helper function to get the current table prefix
  /**
   * Requires the Acuity Module to be loaded to work properly.
   * @return bool
   * @author Rick Susfalk <rick.susfalk@dri.edu>
   */
    public static function getTablePrefix()
    {
        //4/29/17 RBS, if you already have it, use it.
       //If called from within module that supports this functionality by checking for 'createTableName' function
       if (\method_exists(\Yii::$app->controller->module, 'createTableName'))
       {
         if (!empty(\Yii::$app->controller->module->project_prefix))
         {
           return \Yii::$app->controller->module->project_prefix;
         }
       }

       //Otherwise, let's look for it
        //Look at the GET and POST for $id. If not there, $id = false
        $id = self::getProjectId();

        //If this is a console application, you must get it from parameters (generally set dynamically)
        if (is_a(\Yii::$app,'yii\console\Application') &&  isset(\Yii::$app->params['project_dbPrefix']) && \Yii::$app->params['project_dbPrefix'] != false)
        {
          return \Yii::$app->params['project_dbPrefix'];
        }

        //If you need to, then look for the global variable 'project_dbPrefix'. This is set by
        //  DataviewBaseClass->requestData() specifically to handle requests that do not come through
        //  the acuity3 module.
        if ($id === false &&  isset(\Yii::$app->params['project_dbPrefix']) && \Yii::$app->params['project_dbPrefix'] != false) {
          return \Yii::$app->params['project_dbPrefix'];
        }
        $info = acuityModule::getDbFromId(['id'=>$id], \Yii::$app->getModule('acuity')->dbSource, false);
        if (!empty($info['prefix'])) {return $info['prefix'];}
        return false;
    }


    //Helper function to check GET or POST for project ID
    //Will typically only be called if there is an internal request for data.
    //Console safe.
    public static function getProjectId()
    {
        $isConsole = is_a(\Yii::$app,'yii\console\Application');

        $id=null;
        if (!$isConsole)
        {
          //If acuity module already has it, use it 4/29/17
          if (!empty(\Yii::$app->getModule('acuity')->project_id))
          {
            return (\Yii::$app->getModule('acuity')->project_id);
          }

          $request = \Yii::$app->request;
          //Is there an 'id' passed in the GET?
          $id = $request->get('id');
          if (!empty($id))
          {
            return $id;
          }

          //If not, is there an 'project_id' passed in the post?
          $id = $request->post('project_id');
          if (!empty($id))
          {
            return $id;
          }
        }

        //Lastly, check the global space for it. This is set by
        //  DataviewBaseClass->requestData() specifically to handle requests that do not come through
        //  the acuity3 module.
        if (!empty(\Yii::$app->params['project_id']))
        {
            return \Yii::$app->params['project_id'];
        }

        return false;
    }


    //Create Table Name
  /**
   * Create a properly formatted DB data table name. If $id is passed in, then the result with be for a data table.
   * If $id is not passed in, then it is for an Acuity3 table.
   * This is related to the same functions located in Acuity and yBackend Modules
   * @param $name String Name of the DB data table
   * @param $project_prefix String Prefix for the table
   * @param $id Optional. Integer ID of the DB data table
   * @return string
   * @author Rick Susfalk <rick.susfalk@dri.edu>
   */
    public static function createTableName($name, $project_prefix, $id=null)
    {
        //If project prefix is not present add it.
        if (substr($name, 0, strlen($project_prefix)) !== $project_prefix)
        {
            //Also make sure that the prefix ends in an underscore
            $prefix = (substr($project_prefix,-1,1) == '_' ? $project_prefix : $project_prefix.'_');
            $name = $prefix.$name;
        }


        if (!empty($id))
        {
            return $name.'_'.sprintf('%04d', $id);
        }

        return $name;
    }

    public static function convertDateTime($dateStr)
    {
        return strftime('%Y-%m-%d %H:%M:%S', strtotime($dateStr));
    }


  /**
    * @inheritdoc
    */
   public function beforeValidate()
   {
     return parent::beforeValidate();
   }

   /**
    * @inheritdoc
    */
   public function afterFind()
   {
       parent::afterFind();
   }



}
