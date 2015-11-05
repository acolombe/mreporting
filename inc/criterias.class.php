<?php

class PluginMreportingCriterias extends CommonDBTM {

	static function getTypeName($nb=0) {
      return _n("Criterion", "Criteria", $nb);
   }

   static function getFormURL($full = true) {
      global $CFG_GLPI;

      //TODO : quick and dirty 'target'
      return $CFG_GLPI['root_doc'] . "/plugins/mreporting/front/dashboard.form.php";
   }

	static function install(Migration $migration) {
		global $DB;

		$query = "CREATE TABLE `{$this->getTable()}` (
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

   /*
   static function cleanUnmodifiedDate($config = array()) {
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
   }
   */

   static function cleanBeginDate($config = array()) {
      if (isset($config['randname']) && isset($_REQUEST['date1'.$config['randname']])) {
         unset($_REQUEST['date1'.$config['randname']]);
      }
   }

   static function cleanEndDate($config = array()) {
      if (isset($config['randname']) && isset($_REQUEST['date2'.$config['randname']])) {
         unset($_REQUEST['date2'.$config['randname']]);
      }
   }

   static function saveSelectors($graphname, $config = array()) {

      $notification_id = $_REQUEST['notification_id'];

      //TODO : add dates to this array (here ?)
      $remove_fields = array('short_classname', 'f_name', 'gtype', 
         'saveCriterias', '_glpi_csrf_token',
         'notification_id',
         '_date1'.$config['randname'], '_date2'.$config['randname'],
         //'submit'
      );

      $values = array();

      foreach ($_REQUEST as $key => $value) {
         if (!preg_match("/^_/", $key) && !in_array($key, $remove_fields) ) {
            $values[$key] = $value;
         }

         // Simplication of $_REQUEST
         if (empty($value)) {
            unset($_REQUEST[$key]);
         }
      }

      //TODO : Need to work on $values (only)

      //clean unmodified date
      //self::cleanUnmodifiedDate($config);

      //clean begin date
      self::cleanBeginDate($config);

      //clean end date
      self::cleanEndDate($config);

      $selectors = $values;

      $input = array('notification_id' => $notification_id,
                     'selectors'       => addslashes(json_encode($selectors)));

      $criteria = new self();
      if ($criteria->getFromDBByQuery(" WHERE notification_id = $notification_id")) {
         $input['id'] = $criteria->getID();
         $criteria->update($input);
      } else {
         $criteria->add($input);
      }
      //Note : Add that to locale plugin
      Session::addMessageAfterRedirect(__('Saved', 'mreporting'), true);

      //$_SESSION['mreporting_values'] = $values;
   }

   /**
    *
    * Get a preference for an notification_id
    * @param unknown_type preference field to get
    * @param unknown_type user ID
    * @return preference value or 0
    */
   static function checkPreferenceValue($field, $notification_id = 0) {
      $data = getAllDatasFromTable(self::getTable(), "`notification_id`='$notification_id'");
      if (empty($data)) {
         return 0;
      }

      $first = array_pop($data);

      return $first[$field];
   }

   static function getSelectorValuesByNotification_id($notification_id) {

      $myvalues  = isset($_SESSION['mreporting_values']) ? $_SESSION['mreporting_values'] : array();

      $selectors = self::checkPreferenceValue('selectors', $notification_id);
      if ($selectors) {
         $values = json_decode(stripslashes($selectors), true);

         foreach ($values as $key => $value) {
            $myvalues[$key] = $value;
         }
         /*
         if (isset($values[$_REQUEST['f_name']])) {
            foreach ($values[$_REQUEST['f_name']] as $key => $value) {
               $myvalues[$key] = $value;
            }
         }
         */
      }

      return $myvalues;
   }

   //Adapted from getReportSelectors()
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
   static function showFormCriteriasFilters($notification_id) {
      
      //Saved actual mreporting_values session
      $saved_session = isset($_SESSION['mreporting_values']) ? $_SESSION['mreporting_values'] : array();

      // Rewrite mreporting_values session (temporary)
      $_SESSION['mreporting_values'] = self::getSelectorValuesByNotification_id($notification_id);
      //var_dump($_SESSION['mreporting_values']);

      //-> quasi vide mais permet d'obtenir 'reportGlineBacklogs' (nom du rapport)
      //

      $reportSelectors = PluginMreportingCommon::getReportSelectors(true);

      // == Display filters ==

      $graphname = $_REQUEST['f_name'];

      //TODO : Need to use real values
      $_SESSION['mreporting_selector'][$graphname] =
         array('dateinterval', 'period', 'backlogstates', 'multiplegrouprequest',
               'userassign', 'category', 'multiplegroupassign');


      $reportSelectors = self::getReportSelectors();

      //Restore mreporting_values session
      $_SESSION['mreporting_values'] = $saved_session;

      if ($reportSelectors == "") {
         echo __("No configuration for this report", 'mreporting');
         echo "<br><br>";

         return;
      }

      echo "<form method='POST' action='".self::getFormURL()."' name='form'>";

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";
      echo $reportSelectors;
      echo "</table>";

      echo "<input type='hidden' name='short_classname' value='".$_REQUEST['short_classname']."'>";
      echo "<input type='hidden' name='f_name' value='".$_REQUEST['f_name']."''>";
      //echo "<input type='hidden' name='gtype' value='".$_REQUEST['gtype']."'>";
      echo "<input type='hidden' name='notification_id' value='".$notification_id."'>";

      //saveCriterias ->
      //Note : can use a GLPI function
      echo "<input type='submit' class='submit' name='saveCriterias' value='". _sx('button', 'Post') ."'>";

      Html::closeForm();
   }

   static function getReportInfosAssociatedTo($notification_id) {
      $notification = new PluginMreportingNotification();
      if ($notification->getFromDB($notification_id)) {

         $config = new PluginMreportingConfig();
         if ($config->getFromDB($notification->fields['report'])) {

            $_REQUEST['f_name']           = $config->getName(); //'reportGlineBacklogs';
            $_REQUEST['short_classname']  = str_replace('PluginMreporting', '', $config->fields["classname"]); //'Helpdeskplus';
            //$_REQUEST['gtype']            = 'gline';

            //Note : useless
            return $config->fields;
         }
      }
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
   	global $CFG_GLPI;

      if ($item->getType() == 'PluginMreportingNotification') {
         echo "<div class='graph_navigation'>";
         self::getReportInfosAssociatedTo($item->getID());

			self::showFormCriteriasFilters($item->getID());
         echo "</div>";
      }

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