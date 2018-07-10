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

use yii;
use yii\web\View;

/**
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
class RbacWebModule extends \dektrium\rbac\RbacWebModule
{

  public $controllerNamespace = '\dektrium\rbac\controllers';

  public $rbacPortal = null; //storage for RbacPortalComponent class


  /**
   * For the backend portal, need to decide which RBAC portal we need to interact with.
   * That is determined by the GET 'portal' parameter. If not present, it will look at
   * the session variable 'adminRbacMode' to see if the user is already working with a portal.
   * Otherwise, it'll just pull a default
   * @author Rick Susfalk <rick.susfalk@dri.edu>
   */
  public function init()
  {
    $this->rbacPortal = new \overrides\Dektrium_Rbac\RbacPortalComponent();
    $this->rbacPortal->start();
  }
}
