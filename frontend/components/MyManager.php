<?php
/**
 * Created by PhpStorm.
 * User: colto
 * Date: 7/10/2018
 * Time: 9:37 AM
 */

namespace app\components;


class MyManager extends \yii\rbac\PhpManager implements \dektrium\rbac\components\ManagerInterface
{
    public function getItems($type = null, $excludeItems = [])
    {
        // you should implement this method or extend your class from \dektrium\yii2-rbac\components\DbManager
        echo "inside getItems";
    }

    public function getItem($name)
    {
        // you should implement this method or extend your class from \dektrium\yii2-rbac\components\DbManager
        echo "inside getItem";
    }

    /**
     * @param  integer $userId
     * @return mixed
     */
    public function getItemsByUser($userId)
    {
        echo "inside getItemsByUser";
    }


}