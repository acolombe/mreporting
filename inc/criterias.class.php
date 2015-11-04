<?php

class PluginMreportingCriterias extends CommonDBTM {

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

   static function saveSelectors($graphname, $config = array()) {

      //TODO : add dates to this array (here ?)
      $remove_fields = array('short_classname', 'f_name', 'gtype', 'submit');

      $values = array();

      foreach ($_REQUEST as $key => $value) {
         if (!preg_match("/^_/", $key) && !in_array($key, $remove_fields) ) {
            $values[$key] = $value;
         }
         if (empty($value)) {
            unset($_REQUEST[$key]);
         }
      }

      //clean unmodified date
      /*
      if (isset($config['randname'])) {
         if (isset($_REQUEST['date1'.$config['randname']])
            && $_REQUEST['date1'.$config['randname']]
               == $_SESSION['mreporting_values']['date1'.$config['randname']]) {
            unset($_REQUEST['date1'.$config['randname']]);
         }
         if (isset($_REQUEST['date2'.$config['randname']])
            && $_REQUEST['date2'.$config['randname']]
               == $_SESSION['mreporting_values']['date2'.$config['randname']]) {
            unset($_REQUEST['date2'.$config['randname']]);
         }
      }
      */
      $selectors = $values;

      $json = addslashes(json_encode($selectors));

      $notification_id = 1; //DEBUG

      $criteria = new self();
      if ($criteria->getFromDB(1)) {
         $criteria->update(array('notification_id' => $notification_id,
                           'selectors' => $json));
      } else {
         $criteria->add(array('notification_id' => $notification_id,
                              'selectors' => $json));
      }

      $_SESSION['mreporting_values'] = $values;
   }

   static function getCriteriaValueByNotification($notification_id) { //...ByReport ?

      $obj = new self();
      $found = $obj->find("notification_id = ".$notification_id);
      if (empty($found)) {
         return array();
      }

      foreach ($found as $criteria) {
         $sel = json_decode(stripslashes($criteria['selectors']), true);
         return $sel;
      }

   }

   static function getSelectorValuesByUser() {

      $myvalues  = isset($_SESSION['mreporting_values']) ? $_SESSION['mreporting_values'] : array();

      $selectors = PluginMreportingPreference::checkPreferenceValue('selectors', Session::getLoginUserID());
      if ($selectors) {
         $values = json_decode(stripslashes($selectors), true);
         if (isset($values[$_REQUEST['f_name']])) {
            foreach ($values[$_REQUEST['f_name']] as $key => $value) {
               $myvalues[$key] = $value;
            }
         }
      }
      $_SESSION['mreporting_values'] = $myvalues;
   }

   /**
    * Parse and include selectors functions
    */
   static function getReportSelectors() {
      ob_start();

      PluginMreportingCommon::addToSelector();

      $graphname = $_REQUEST['f_name'];

      if (!isset($_SESSION['mreporting_selector'][$graphname])
         || empty($_SESSION['mreporting_selector'][$graphname])) {
         return '';
      }

      $classname = 'PluginMreporting'.$_REQUEST['short_classname'];
      if (!class_exists($classname)) {
         return '';
      }

      $i = 1;
      foreach ($_SESSION['mreporting_selector'][$graphname] as $selector) {
         if ($i % 4 == 0) {
            echo '</tr><tr class="tab_bg_1">';
         }
         $selector = 'selector'.ucfirst($selector);
         if (method_exists('PluginMreportingCommon', $selector)) {
            $classselector = 'PluginMreportingCommon';
         } elseif (method_exists($classname, $selector)) {
            $classselector = $classname;
         } else {
            continue;
         }

         $i++;
         echo '<td>';
         $classselector::$selector();
         echo '</td>';
      }

      while ($i % 4 != 0) {
         $i++;
         echo '<td>&nbsp;</td>';
      }

      return ob_get_clean();
   }

   //Adapted from getConfig() in dashboard class
   static function getConfig() {

      //$notification_id = 1; //DEBUG
      //$criteria = self::getCriteriaValueByNotification($notification_id);

      self::getSelectorValuesByUser(); //new code
      //PluginMreportingCommon::getSelectorValuesByUser();

      //-> quasi vide mais permet d'obtenir 'reportGlineBacklogs' (nom du rapport)
      //

      //$reportSelectors = PluginMreportingCommon::getReportSelectors(true);
      $reportSelectors = self::getReportSelectors();

      if ($reportSelectors == "") {
         echo __("No configuration for this report", 'mreporting');
         return;
      }

      echo "<form method='POST' action='" . $_REQUEST['target'] . "' name='form' id='mreporting_date_selector'>";

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";
      echo $reportSelectors;
      echo "</table>";

      echo "<input type='hidden' name='short_classname' value='".$_REQUEST['short_classname']."' class='submit'>";
      echo "<input type='hidden' name='f_name' value='".$_REQUEST['f_name']."' class='submit'>";
      echo "<input type='hidden' name='gtype' value='".$_REQUEST['gtype']."' class='submit'>";
      echo "<input type='submit' class='submit' name='saveConfig' value=\"". _sx('button', 'Post') ."\">";

      Html::closeForm();
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
	      //   PluginMreportingPreference::resetSelectorsForReport($_REQUEST['f_name']);
	      //}
	      */

			//Show date selector
         echo "<div class='graph_navigation'>";

      		//'%2Fglpi-090-git%2Fglpi%2Fplugins%2Fmreporting%2Ffront%2Fdashboard.form.php';
      	$_REQUEST['target'] 				= $CFG_GLPI['root_doc']."/plugins/mreporting/front/dashboard.form.php"; //quick and dirty 'target'
      	$_REQUEST['f_name'] 				= 'reportGlineBacklogs';
      	$_REQUEST['short_classname']	= 'Helpdeskplus';
      	$_REQUEST['gtype']				= 'gline';

			self::getConfig();
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