<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace overrides\Dektrium_Rbac;



use common\components\acuityPortals;
use yii;
use yii\web\View;
use kartik\widgets\Select2;


/**
 * Class RbacPortalComponent
 * Helper classes. Note: technically, this file does not override any Dektrium file.
 * However, it is included here for simplicity
 * @package overrides\Dektrium_Rbac
 * @author Rick Susfalk <rick.susfalk@dri.edu>
 * Funding: Personal / Project
 */
class RbacPortalComponent
{
  //Paths where this component will be active. Only these paths.
  public $acceptedPaths = [ "user/admin/assignments", "yii2-rbac/role/index", "yii2-rbac/permission/index"];


  public $portalList = []; //List of portals
  public $defaultPortalId = ''; //id of the portal we are looking at
  public $portalSpecified = false; //if portal is specified in GET

  /**
   * For the backend portal, need to decide which RBAC portal we need to interact with.
   * That is determined by the GET 'portal' parameter. If not present, it will look at
   * the session variable 'adminRbacMode' to see if the user is already working with a portal.
   * Otherwise, it'll just pull a default.
   * Creates $this->portalList, a select2 compatiable format. However, Kartik's wrapper does not handle it.
   * So, we use JS to repopulate the dropdown later.
   *
   * @author Rick Susfalk <rick.susfalk@dri.edu>
   */
  public function start()
  {
    //For assignments, we need to override $authManager db tables and display the yii2-rbac portal selector
    if (!in_array(Yii::$app->urlManager->parseRequest(Yii::$app->request)[0], $this->acceptedPaths))
    {
      return;
    }

    //--Below code lets you go between different RBAC db settings
    //--- Change out DB tables denpding on the portal
    //TODO -- disable for anything but Admin

    //get data
   $portals = \common\components\acuityPortals::getPortalsList();
   $this->portalList = \common\components\acuityPortals::portalsListSelect2($portals);

   //if not in GET and not in SESSION
   $portalKey = \Yii::$app->request->getQueryParam('portal', false);
   $default = false; //if true, the default can be overriden by the first good portal

     //If portal is in GET and it is valid, use it
     if ($portalKey !== false && in_array($portalKey,  array_column($this->portalList, 'id')))
     {
       $defaultPortalId = $portalKey;
       Yii::$app->session->set('adminRbacMode', $portalKey);
       $myGet = true;
     }
     //otherwise if is stored in the session, use that
     elseif (Yii::$app->session->get('adminRbacMode', false) !== false)
     {
       $defaultPortalId = Yii::$app->session->get('adminRbacMode');
       $default = true;
     }

     if (empty($defaultPortalId))
     {
       //if not, then get default entry and set session to it and add to URL
         reset($this->portalList);
         $key = key($this->portalList);
         $defaultPortalId =  $this->portalList[$key]['id'];
         Yii::$app->session->set('adminRbacMode', $defaultPortalId);
         $default = true;
     }



  //override settings if currently in the adminBackendOnlyRbac.
  $authManager = \Yii::$app->getAuthManager();

  //Get the rbacTables. Note that the $authManager->db will be updated with appropriate DB.
  $rbacTables = acuityPortals::getRbacTables($authManager, $defaultPortalId);

  //Check that DB tables are actually present
  $tablesPresent = acuityPortals::areRbacTablesPresent($authManager, $rbacTables['list']);


  //alter $this->portalList based on the presence of the presence of the underlying table.
    $firstGood = "";
    foreach($this->portalList as $key=>$portal)
    {
      $id=$portal['id'];
      if (!empty($authManager->adminBackendOnlyRbac['portals'][$id]))
      {
        if (!in_array($authManager->adminBackendOnlyRbac['portals'][$id].$rbacTables['defaultList']['itemTable'], $tablesPresent['tableList']))
        {
          $this->portalList[$key]['text'] = $this->portalList[$key]['text']." (WARN - DB tables are not present)";
          $this->portalList[$key]['disabled'] = true;
        }
        else
        {
          if (empty($firstGood)) {$firstGood = $id;}
        }
      }
      else
      {
        $this->portalList[$key]['text'] = $this->portalList[$key]['text']." (WARN - Portal not set up for this site";
        $this->portalList[$key]['disabled'] = true;
      }
    }

    //used to set JS to add GET portal parameter if not already specified

      if (!empty($portalKey))
      {
        $this->portalSpecified = true;
      }

    //reset the default Portal Id if we had looked for the default
   if ($default && $firstGood != $defaultPortalId)
    {
      $defaultPortalId =  $firstGood;
      Yii::$app->session->set('adminRbacMode', $firstGood);
    }

  $this->defaultPortalId = $defaultPortalId;

  //if all 4 tables are not present, error out
  if (!$tablesPresent['present'])
  {
    throw new \yii\base\UserException( "The requested RBAC tables are not present!" );
  }


  //-------------------
  }


  public function getFirstGood()
  {
    foreach($this->portalList as $key=>$portal)
    {
      if (!empty($portal['access']))
      {
        return $portal['portal_id'];
      }
    }
    return false;
  }

  //===========================
  public function output($view)
  {
    if (empty($this->portalList))
    {
      throw new \yii\base\UserException( "ERROR: No portal request was detected." );
    }
    else
    {
      echo "
      <style>
        .col2container {
            margin-top:10px;
            background: #ffd099;
            display: table;
            width:100%;
        }
        .col2left {
            display: table-cell;
            padding: 8px 12px;
        }
        .col2right {
            display: table-cell;
            padding: 8px 12px;
            width:70%;
        }
        /*Fix for width issue*/
        .select2-container
        {
            display: block;
            width: auto !important;
        }
        /*change color*/
        #select2-portal_list-container.select2-selection__rendered #select2-portal_list-container.select2-selection__rendered.select2-search input { background-color: #ffedb1; }
        #select2-portal_list-container.select2-selection__rendered { background-color: #ffedb1; }
        .select2-results { background-color: #ffedb1; }
        
        /*.selection.ul.select2-search { background-color: #ffedb1; }*/
        .select2-search input { background-color: #ffdb75; }
        
       </style>";

      //add JS
      \common\components\acuityJsFn::addUrlParameter($view);

      if ($this->portalSpecified)
      {
        $view->registerJs("
              addUrlParameter('portal', '" . $this->defaultPortalId . "', false);
          ", View::POS_READY);
      }

///NOTE-- the db tables are overridden in /common/overrides/Dektrium_Rbax/RbacWebModule->init()

      echo "<div class='col2container'><div class='col2left'>";

      echo "RBAC for current portal: ";// <span style='font-weight:bold'>".$data[$defaultPortalId]."</span>";

      echo "</div><div class='col2right'>" . Select2::widget([
          'id' => 'portal_list',
          'name' => 'portal_list',
          //WARNING - Kartik's wrapper only supports [id]=>text format, not ['id'=>xxx, 'text'=>yyy, 'disabled'=>true]]
          //Therefore, pass in empty data, and below in registerJs send the data in through a JS command
          'data' => [],
          'options' => [
            'multiple' => false
          ],
          'pluginEvents' => [
            "select2:select" => "function(e) {addUrlParameter('portal', e.params.data.id, true); }",
          ],
        ]);

      $view->registerJs("
        $('#portal_list').select2({
            data: ".\yii\helpers\Json::encode($this->portalList)."
        });
        $('#portal_list').select2().val('" . $this->defaultPortalId . "').trigger('change');
      ", View::POS_READY);

      echo '</div></div>';
    }
  }
}
