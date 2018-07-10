<?php

namespace overrides;

/*Extends \yii\yii2-rbac\DbManager
  This module has all the necessary db code that needs to be changed to support legacy
  Use components->authManager settings in config to set DB

  Note that this file is called by /overrides/Dektrium_Rbac/DbManger

  To be clear:
   -- /overrides/Dektrium_Rbac/DbManger clones Dektrium_Rbac which itself extends /overrides/DbManger.
   -- /overrides/DbManger (this file) extends Yii's base DbManager class

=====
auth_assignment
Dektrium		Existing
item_name		itemname
user_id			userid
created_at
			    	bizrule
			    	data

auth_itemchild
Dektrium		Existing
parent			parent
child			child

auth_item
Dektrium		   Old
name			    name
type			    type
description		description
rule_name
data			    data
created_at
updated_at
			      	bizrule

auth_rule
Dektrium		Old
name
data
created_at
updated_at
 */

//NOTE -- NOTE -- NOTE - NOTE 2/6/17 RBS
//For Dektrium_Rbac to work,
// All yauth_*_item.types 0 and 1 must be converted to 3
// All yauth_*_item.type 2 must be converted to 1
// All yauth_*_item.type 3 must be converted to 2

/*
UPDATE yauth_stormwater_item SET type = 3 WHERE type IN (0,1);
UPDATE yauth_stormwater_item SET type = 1 WHERE type IN (2);
UPDATE yauth_stormwater_item SET type = 2 WHERE type IN (3);
 */




    // NEW DEKTRIUM           OLD
    //permissions = 2         permissions = 0
    //roles = 1               groups = 1
    //not used                roles = 2

use Yii;
//use yii\caching\Cache;
//use yii\db\Connection;
use yii\db\Query;
//use yii\db\Expression;
//use yii\base\InvalidCallException;
//use yii\base\InvalidParamException;
//use yii\di\Instance;
use yii\rbac\Assignment;

class DbManager extends \yii\rbac\DbManager
{

  //!!!Do not extend checkAccess -- will cause checks to fail!

  //Only extend tables that need to be extended due to strcuture differences
  //As it turns out, the primary change was
  //  auth_assignemnt "item_name" (new) to "itemname" (old)
  //  auth_assignemtn "user_id" (new) to "userid" (old)

  // Also need to 'fix' the DbManager under /common/dektrium/yii2-rbac/components

  //Other settings --- to be passed in the config/main
  public $portalList; //where is the portal list
  public $portalAccess; //what is the name of the $portal_access table?

  //Store the actual location of the user Tables
  public $userTables = ['db'=>'',   //what is the user table?
                        'users_table'=>''
                       ];

  //Store any backend only settings. This enables the backend portal to access the admin table
  public $adminBackendOnly = [
              'db'                => '', // 'db_yii',
              'userPortalAccess'  => '', //'yuser_admin_portal_access',
              'userProfiles'      => '', //'yuser_admin_profiles',
              'userProfileFields' => '', //'yuser_admin_profiles_fields',
              'userUsers'         => '', //'yuser_admin_users',
    ];

  //Store the location and yii2-rbac table prefix settings.
  //Will automatically add _assignment, _item, _itemchild, and _rule where needed
  public $adminBackendOnlyRbac = [
                     'db'                =>'', // 'db_yii',
                     'portals'           => [
                        //    'Stormwater'    => 'yauth_stormwater',
                        //    'OWEN'          => 'yauth_owen',
                       //Alternate format not yet testet
                         //   'NvMet'         => ['db'=>'db_nvment', 'tablePrefix'=>'yauth_nvmet'],
                      //      'Sephas'        => ['db'=>'db_sephas', 'tablePrefix'=>'yauth_sephas'],
                     ],
                ];

  /**
   * @inheritdoc
   */
  //7/6/15 fix for field names of legacy tables
  protected function removeItem($item)
  {
    if (!$this->supportsCascadeUpdate())
    {
      $this->db->createCommand()
        ->delete($this->itemChildTable, ['or', '[[parent]]=:name', '[[child]]=:name'], [':name' => $item->name])
        ->execute();
      $this->db->createCommand()
        ->delete($this->assignmentTable, ['itemname' => $item->name])//was $row['item_name']
        ->execute();
    }

    $this->db->createCommand()
      ->delete($this->itemTable, ['name' => $item->name])
      ->execute();

    $this->invalidateCache();

    return true;
  }

  /**
   * @inheritdoc
   */
  //7/6/15 fix for field names of legacy tables
  protected function updateItem($name, $item)
  {
    if ($item->name !== $name && !$this->supportsCascadeUpdate())
    {
      $this->db->createCommand()
        ->update($this->itemChildTable, ['parent' => $item->name], ['parent' => $name])
        ->execute();
      $this->db->createCommand()
        ->update($this->itemChildTable, ['child' => $item->name], ['child' => $name])
        ->execute();
      $this->db->createCommand()
        ->update($this->assignmentTable, ['itemname' => $item->name], ['itemname' => $name])//was 'item_name'
        ->execute();
    }

    $item->updatedAt = time();

    $this->db->createCommand()
      ->update($this->itemTable, [
        'name' => $item->name,
        'description' => $item->description,
        'rule_name' => $item->ruleName,
        'data' => $item->data === null ? null : serialize($item->data),
        'updated_at' => $item->updatedAt,
      ], [
        'name' => $name,
      ])->execute();

    $this->invalidateCache();

    return true;
  }


  /**
   * @inheritdoc
   */
  //7/6/15 fix for field names of legacy tables
  public function getRolesByUser($userId)
  {
    if (empty($userId))
    {
      return [];
    }

    $query = (new Query)->select('b.*')
      ->from(['a' => $this->assignmentTable, 'b' => $this->itemTable])
      ->where('{{a}}.[[itemname]]={{b}}.[[name]]')//was 'item_name'
      ->andWhere(['a.userid' => (string)$userId])//was 'user_id'
      ->andWhere(['b.type' => Item::TYPE_ROLE]);

    $roles = [];
    foreach ($query->all($this->db) as $row)
    {
      $roles[$row['name']] = $this->populateItem($row);
    }
    return $roles;
  }


  /**
   * @inheritdoc
   */
  //7/6/15 fix for field names of legacy tables
  public function getPermissionsByUser($userId)
  {
    if (empty($userId))
    {
      return [];
    }

    $query = (new Query)->select('itemname')//was 'item_name'
    ->from($this->assignmentTable)
      ->where(['userid' => (string)$userId]); //was 'user_id'

    $childrenList = $this->getChildrenList();
    $result = [];
    foreach ($query->column($this->db) as $roleName)
    {
      $this->getChildrenRecursive($roleName, $childrenList, $result);
    }

    if (empty($result))
    {
      return [];
    }

    $query = (new Query)->from($this->itemTable)->where([
      'type' => Item::TYPE_PERMISSION,
      'name' => array_keys($result),
    ]);
    $permissions = [];
    foreach ($query->all($this->db) as $row)
    {
      $permissions[$row['name']] = $this->populateItem($row);
    }
    return $permissions;
  }

  /**
   * @inheritdoc
   */
  //7/6/15 fix for field names of legacy tables
  public function getAssignment($roleName, $userId)
  {
    if (empty($userId))
    {
      return null;
    }

    $row = (new Query)->from($this->assignmentTable)
      ->where(['userid' => (string)$userId, 'itemname' => $roleName])//was 'user_id', 'item_name'
      ->one($this->db);

    if ($row === false)
    {
      return null;
    }

    return new Assignment([
      'userId' => $row['userid'], //was 'user_id'
      'roleName' => $row['itemname'], //was 'item_name'
      'createdAt' => $row['created_at'],
    ]);
  }

  /**
   * @inheritdoc
   */
  //7/6/15 fix for field names of legacy tables
  public function getAssignments($userId)
  {
    if (empty($userId))
    {
      return [];
    }

    $query = (new Query)
      ->from($this->assignmentTable)
      ->where(['userid' => (string)$userId]); //was $row['user_id']

    $assignments = [];

    foreach ($query->all($this->db) as $row)
    {
      $assignments[$row['itemname']] = new Assignment([    //was $row['item_name']
        'userId' => $row['userid'], //was $row['user_id']
        'roleName' => $row['itemname'], //was $row['item_name']
        'createdAt' => $row['created_at'],
      ]);
    }

    return $assignments;
  }

  /**
   * @inheritdoc
   */
  //7/6/15 fix for field names of legacy tables
  public function assign($role, $userId)
  {
    $assignment = new Assignment([
      'userId' => $userId,
      'roleName' => $role->name,
      'createdAt' => time(),
    ]);

    $this->db->createCommand()
      ->insert($this->assignmentTable, [
        'userid' => $assignment->userId, //was 'user_id'
        'itemname' => $assignment->roleName, //was 'item_name'
        'created_at' => $assignment->createdAt,
      ])->execute();

    return $assignment;
  }

  /**
   * @inheritdoc
   */
  //7/6/15 fix for field names of legacy tables
  public function revoke($role, $userId)
  {
    if (empty($userId))
    {
      return false;
    }

    return $this->db->createCommand()
      ->delete($this->assignmentTable, ['userid' => (string)$userId, 'itemname' => $role->name]) //was 'user_id', 'item_name'
      ->execute() > 0;
  }

  /**
   * @inheritdoc
   */
  //7/6/15 fix for field names of legacy tables
  public function revokeAll($userId)
  {
    if (empty($userId))
    {
      return false;
    }

    return $this->db->createCommand()
      ->delete($this->assignmentTable, ['userid' => (string)$userId]) //was 'user_id'
      ->execute() > 0;
  }

  /**
      * Removes all auth items of the specified type.
      * @param integer $type the auth item type (either Item::TYPE_PERMISSION or Item::TYPE_ROLE)
      */
    //7/6/15 fix for field names of legacy tables

     protected function removeAllItems($type)
     {
         if (!$this->supportsCascadeUpdate()) {
             $names = (new Query)
                 ->select(['name'])
                 ->from($this->itemTable)
                 ->where(['type' => $type])
                 ->column($this->db);
             if (empty($names)) {
                 return;
             }
             $key = $type == Item::TYPE_PERMISSION ? 'child' : 'parent';
             $this->db->createCommand()
                 ->delete($this->itemChildTable, [$key => $names])
                 ->execute();
             $this->db->createCommand()
                 ->delete($this->assignmentTable, ['itemname' => $names]) //was 'item_name'
                 ->execute();
         }
         $this->db->createCommand()
             ->delete($this->itemTable, ['type' => $type])
             ->execute();

         $this->invalidateCache();
     }

}