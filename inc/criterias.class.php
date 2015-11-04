<?php

class PluginMreportingCriterias extends PluginMreportingCommon {

	static function getTypeName($nb=0) {
      return _n("Criterion", "Criteria", $nb);
   }

	static function install(Migration $migration) {
		global $DB;

		$table = getTableForItemType(__CLASS__);

		//TODO : Need more fields in database
		$query = "CREATE TABLE `$table` (
						`id` INT(11) NOT NULL AUTO_INCREMENT,
						`selectors` VARCHAR(255) NOT NULL DEFAULT '',
						PRIMARY KEY (`id`)
					)
					COLLATE='latin1_swedish_ci'
					ENGINE=InnoDB";
		$DB->query($query);
	}

	static function uninstall(Migration $migration) {
		$migration->dropTable(getTableForItemType(__CLASS__));
	}


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
   	global $CFG_GLPI;

      //if ($item->getType() == 'PluginMreportingNotification') { //OK

         // == ACTIONS ==
         //Note : not work -> move to /front/dashboard.php (sauf 'reset')
         /*
         if (isset($_REQUEST['submit'])) {
            self::saveSelectors($_REQUEST['f_name'], $config); //IMPORTANT
	      } //else if (isset($_REQUEST['reset'])) {
	      //   self::resetSelectorsForReport($_REQUEST['f_name']);
	      //}
	      */

			//Show date selector
         echo "<div class='graph_navigation'>";

      		//'%2Fglpi-090-git%2Fglpi%2Fplugins%2Fmreporting%2Ffront%2Fdashboard.form.php';
      	$_REQUEST['target'] 				= $CFG_GLPI['root_doc']."/plugins/mreporting/front/dashboard.form.php";
      	$_REQUEST['f_name'] 				= 'reportGlineBacklogs';
      	$_REQUEST['short_classname']	= 'Helpdeskplus';
      	$_REQUEST['gtype']				= 'gline';

      	//quick and dirty 'target'
			PluginMreportingDashboard::getConfig('saveCriterias');
         echo "</div>";
      //}
      return true;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (Notification::canView() && $item->getType() == 'PluginMreportingNotification') { //"Security"

         if ($_SESSION['glpishow_count_on_tabs']) {
         	//Note : can remplace '2' by Session::
         	return self::createTabEntry(self::getTypeName(2));

         	//Note : Possible to have best code ?
            //return self::createTabEntry(self::getTypeName(Session::getPluralNumber()),
            //                            countElementsInTable($this->getTable())); //, "notifications_id = '".$item->getID()."'"
         }
         return self::getTypeName(Session::getPluralNumber());
      }
      return '';
   }

}