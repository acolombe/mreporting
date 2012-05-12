<?php
/*
 * @version $Id: popup.php 17220 2012-01-26 14:25:39Z yllen $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkLoginUser();

if (isset($_GET["classname"])) {
   $_POST["classname"] = $_GET["classname"];
}
  
Html::popHeader($LANG['plugin_mreporting']["export"][0], $_SERVER['PHP_SELF']);

$common = new PluginMreportingCommon();
$common->showExportFrom($_POST);

echo "<div class='center'><br><a href='javascript:window.close()'>".$LANG['buttons'][60]."</a>";
echo "</div>";
Html::popFooter();


?>