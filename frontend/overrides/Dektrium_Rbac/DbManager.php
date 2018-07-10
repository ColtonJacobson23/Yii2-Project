<?php

/*RBS 7/6/15
  Must actually use a clone of Dektrium's file in order to force it to use the proper DbManager.
    (you can not just extend it)
  Only changed DB manager from Yii's version (yii\yii2-rbac\DbManager) to our version (overrides\DbManager)
  Also added namespace and BaseManagerInterface definition
  Lastly, needed to change db table field names to matach legacy
    --as noted below. Note: this was also done in /overrides/DbManger

To be clear:
 -- /overrides/Dektrium_Rbac/DbManger (this file) clones Dektrium_Rbac which itself extends /overrides/DbManger.
 -- /overrides/DbManger extends Yii's base DbManager class
 */


/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace overrides\Dektrium_Rbac;
//namespace dektrium\yii2-rbac\components;

use yii\db\Query;
//use yii\yii2-rbac\DbManager as BaseDbManager;
use overrides\DbManager as BaseDbManager;
use dektrium\rbac\components\ManagerInterface as BaseManagerInterface;

/**
 * This Auth manager changes visibility and signature of some methods from \yii\yii2-rbac\DbManager.
 *
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
class DbManager extends BaseDbManager implements BaseManagerInterface
{

    //RBS Added 8/31/15 -- new function
    public function getUsersByItem($item)
    {
        if (empty($item)) {
            return [];
        }

        $userIds = [];
        $result = [];
        //get a list of yii2-rbac items (keys) and their parents (array)
       //include the self level
        $result[$item] = true;

        $parentList = $this->getParentList($item);
        //recursively go through them to exand the list to parents and grand parents
        foreach ($parentList as $roleName) {
            $this->getParentsRecursive($roleName, $parentList, $result);
        }

        //Now, get a list of all users who are assinged to these roles
        $query = (new Query)->select('userid')
                 ->from($this->assignmentTable)
                 ->where("itemname IN ('".implode("','", array_keys($result))."')");
        foreach ($query->all($this->db) as $row) {
            $userIds[] = $row['userid'];
        }

        return $userIds;
    }

    //RBS Added 8/31/15 -- new function
    //Given an item, get a list of each yii2-rbac (key) and each of its parents
    public function getParentList($item)
    {
        $query = (new Query)->from($this->itemChildTable);
        $parents = [];
        foreach ($query->all($this->db) as $row) {
            $parents[$row['child']][] = $row['parent'];
        }
        return (!empty($parents[$item]) ? $parents[$item] : []);
    }

    /**
     * //RBS Added 8/31/15 -- new function
     * Recursively finds all parents and grand parets of the specified item.
     * @param string $name the name of the item whose parents are to be looked for.
     * @param array $parentList the child list built via [[getParentList()]]
     * @param array $result the parent and grand parents (in array keys)
     */
    protected function getParentsRecursive($name, $parentList, &$result)
    {
        if (isset($parentList[$name])) {
            foreach ($parentList[$name] as $parent) {
                $result[$parent] = true;
                $this->getChildrenRecursive($parent, $parentList, $result);
            }
        }
    }



    /**
     * @param  int|null $type         If null will return all auth items.
     * @param  array    $excludeItems Items that should be excluded from result array.
     * @return array
     */
    public function getItems($type = null, $excludeItems = [])
    {

        $query = (new Query())
            ->from($this->itemTable);

        if ($type !== null) {
            $query->where(['type' => $type]);
        } else {
            $query->orderBy('type');
        }

        foreach ($excludeItems as $name) {
            $query->andWhere('name != :item', ['item' => $name]);
        }

        $items = [];

        foreach ($query->all($this->db) as $row) {
            $items[$row['name']] = $this->populateItem($row);
        }

        return $items;
    }

    /**
     * Returns both roles and permissions assigned to user.
     *
     * @param  integer $userId
     * @return array
     */
    public function getItemsByUser($userId)
    {
        if (empty($userId)) {
            return [];
        }

        $query = (new Query)->select('b.*')
            ->from(['a' => $this->assignmentTable, 'b' => $this->itemTable])
            ->where('{{a}}.[[itemname]]={{b}}.[[name]]')    //was [[item_name]]
            ->andWhere(['a.userid' => (string) $userId]);  //was a.user_id

        $roles = [];
        foreach ($query->all($this->db) as $row) {
            $roles[$row['name']] = $this->populateItem($row);
        }
        return $roles;
    }

    /** @inheritdoc */
    public function getItem($name)
    {
        return parent::getItem($name);
    }
}