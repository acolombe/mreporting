<?php

class PluginMreportingNotification extends Notification {

   static function getTypeName($nb = 0) {
      return _n("More reporting notification", "More reporting notifications", $nb, 'mreporting');
   }

   function defineTabs($options=array()) {
      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('PluginMreportingNotificationTarget', $ong, $options);
      if ($this->fields['report'] > 0) {
         $this->addStandardTab('PluginMreportingCriterias', $ong, $options);
      }
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   static function getReportName($report_id) {
      global $LANG;

      $config = new PluginMreportingConfig();
      if ($config->getFromDB($report_id)) {
         $class = substr($config->fields['classname'], 16);

         $report_code = $config->fields['name'];

         if (isset($LANG['plugin_mreporting'][$class][$report_code]['title'])) { //"Security"
            return $LANG['plugin_mreporting'][$class][$report_code]['title'];
         }
      }

      return "";
   }

   function prepareInputForAdd($input) {

      if (empty($input['name'])) {
         Session::addMessageAfterRedirect(__('A required field is empty:', 'mreporting') . ' ' . __('Name'), false, ERROR);
         return false;
      }

      if (isset($input['report']) && $input['report'] > 0) {
         // Quick Hack
         $input["report_name"] = self::getReportName($input['report']);
      } else {
         Session::addMessageAfterRedirect(__('A required field is empty:', 'mreporting') . ' ' . __("Report", 'mreporting'), false, ERROR);
         return false;
      }
      return $input;
   }

   function prepareInputForUpdate($input) {

      if (empty($input['name'])) {
         Session::addMessageAfterRedirect(__('A required field is empty:', 'mreporting') . ' ' . __('Name'), false, ERROR);
         return false;
      }

      if (isset($input['report']) && $input['report'] > 0) {
         // Quick Hack
         $input["report_name"] = self::getReportName($input['report']);
      } else {
         Session::addMessageAfterRedirect(__('A required field is empty:', 'mreporting') . ' ' . __("Report", 'mreporting'), false, ERROR);
         return false;
      }
      return $input;
   }

   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      // Display only event(s) and templates associated to Mreporting notification
      $this->fields['itemtype'] = __CLASS__;

      // Name (mandatory field)
      echo "<tr class='tab_bg_1'><td>" . __('Name') . " <span class='red'>*</span></td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";

      echo "<td rowspan='6' class='middle right'>".__('Comments')."</td>";
      echo "<td class='center middle' rowspan='6'><textarea cols='45' rows='9' name='comment' >".
             $this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Active') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('is_active', $this->fields['is_active']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Type') . "</td>";
      echo "<td>";
      $rand = Dropdown::showItemTypes('itemtype',
                                      array($this->fields['itemtype']), //Only PluginMreportingNotification
                                      array('value' => $this->fields['itemtype']));

      $params = array('itemtype' => '__VALUE__');
      Ajax::updateItemOnSelectEvent("dropdown_itemtype$rand", "show_events",
                                    $CFG_GLPI["root_doc"]."/ajax/dropdownNotificationEvent.php",
                                    $params);
      Ajax::updateItemOnSelectEvent("dropdown_itemtype$rand", "show_templates",
                                    $CFG_GLPI["root_doc"]."/ajax/dropdownNotificationTemplate.php",
                                    $params);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Notification method') . "</td>";
      echo "<td>";
      self::dropdownMode(array('value'=>$this->fields['mode']));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . NotificationEvent::getTypeName(1) . "</td>";
      echo "<td><span id='show_events'>";
      NotificationEvent::dropdownEvents($this->fields['itemtype'],
                                        array('value'=>$this->fields['event']));
      echo "</span></td></tr>";

      echo "<tr class='tab_bg_1'><td>". NotificationTemplate::getTypeName(1)."</td>";
      echo "<td><span id='show_templates'>";
      NotificationTemplate::dropdownTemplates('notificationtemplates_id', $this->fields['itemtype'],
                                              $this->fields['notificationtemplates_id']);
      echo "</span></td></tr>";

      // Select report
      echo "<tr class='tab_bg_1'>";
      echo "<td>". __("Report", 'mreporting') ." <span class='red'>*</span></td>";
      echo "<td>";
      echo PluginMreportingCommon::getSelectAllReports(false, true, $this->fields['report'], true, false);
      echo "</td>";
      echo "</tr>";

      // Frequency
      echo "<tr class='tab_bg_1'>";
      echo "<td><label>".__('Run frequency')."</label></td>";
      echo "<td>";
      $randname = mt_rand();
      Dropdown::showFromArray('frequency',
                              array(DAY_TIMESTAMP=>__('Each day'),
                                    WEEK_TIMESTAMP=>__('Each week'),
                                    MONTH_TIMESTAMP=>__('Each month')),
                              array('value'=>$this->fields['frequency'],
                                    'rand'=>$randname));
      echo '<span id="dropdownSendingDay">';
      if (isset($this->fields['frequency'])) {
        switch ($this->fields['frequency']) {
          case 604800: Dropdown::showFromArray('sending_day',
                        array(1=>__('Sunday'),
                              2=>__('Monday'),
                              3=>__('Tuesday'),
                              4=>__('Wednesday'),
                              5=>__('Thursday'),
                              6=>__('Friday'),
                              7=>__('Saturday')),
                        array('value'=>$this->fields['sending_day']));
                        break;
          case 2592000: Dropdown::showNumber('sending_day',
                                              array('min'=>1,
                                                    'max'=>31,
                                                    'value'=>$this->fields['sending_day']));
                        break;
        }
      }
      echo '</span>';
      Ajax::updateItemOnSelectEvent("dropdown_frequency$randname",
                                    'dropdownSendingDay',
                                    '../ajax/dropdownSendingDay.php',
                                    array('value'=>'__VALUE__',
                                          'id'=>$this->fields['id']));
      Dropdown::showHours('sending_hour', array('value'=>$this->fields['sending_hour']));
      echo "</td>";
      echo "</tr>";

      if (isset($_GET['id']) && $_GET['id'] > 0) {
        if (is_null($this->fields['lastrun'])) {
          $lastrun      = __('Never');
          $nextrun      = __('As soon as possible');
          $lastrunInput = '';
        }
        else {
          $lastrun      = $this->fields['lastrun'];
          $frequency    = $this->fields['frequency'];
          $hour         = $this->fields['sending_hour'];
          $nextrun      = $this->fields['nextrun'];
          $lastrunInput = "<input type='hidden' name='lastrun' value='$lastrun' />";
        }
        echo '<tr><td>'.__('Last run')."</td><td>{$lastrunInput}$lastrun</td></tr>'";
        echo '<tr><td>'.__('Next run')."</td><td>$nextrun</td></tr></table>";
      }

      $this->showFormButtons($options);
      return true;
   }

   /**
    * Install mreporting notifications.
    *
    * @return array 'success' => true on success
    */
   static function install(Migration $migration) {
      global $DB;

      // Création du template de la notification
      $template = new NotificationTemplate();
      $found_template = $template->find("itemtype = 'PluginMreportingNotification'");
      if (empty($found_template)) {
         $template_id = $template->add(array(
            'name'                     => __('Notification for "More Reporting"', 'mreporting'),
            'comment'                  => "",
            'itemtype'                 => __CLASS__,
         ));

         $content_html = __("\n<p>Hello,</p>\n\n<p>GLPI reports are available.<br />\nYou will find attached in this email.</p>\n\n", 'mreporting');

         // Ajout d'une traduction (texte) en Français
         $translation = new NotificationTemplateTranslation();
         $translation->add(array(
         	'notificationtemplates_id' => $template_id,
            'language'                 => '',
         	'subject'                  => __("GLPI statistics reports", 'mreporting'),
         	'content_text'             => __("Hello,\n\nGLPI reports are available.\nYou will find attached in this email.\n\n", 'mreporting'),
         	'content_html'             => $content_html)
         );
      }

      $table = self::getTable();

      $query = "CREATE TABLE IF NOT EXISTS `$table` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                  `entities_id` INT(11) NOT NULL DEFAULT '0',
                  `itemtype` VARCHAR(100) NOT NULL COLLATE 'utf8_unicode_ci',
                  `event` VARCHAR(255) NOT NULL COLLATE 'utf8_unicode_ci',
                  `mode` VARCHAR(255) NOT NULL COLLATE 'utf8_unicode_ci',
                  `notificationtemplates_id` INT(11) NOT NULL DEFAULT '0',
                  `comment` TEXT NULL COLLATE 'utf8_unicode_ci',
                  `is_recursive` TINYINT(1) NOT NULL DEFAULT '0',
                  `is_active` TINYINT(1) NOT NULL DEFAULT '0',
                  `date_mod` DATETIME NULL DEFAULT NULL,
                  `sending_day` INT(11) NULL DEFAULT NULL,
                  `sending_hour` TIME NOT NULL,
                  `frequency` INT(11) NOT NULL DEFAULT 86400,
                  `lastrun` TIMESTAMP NULL DEFAULT NULL,
                  `nextrun` TIMESTAMP NULL DEFAULT NULL
                  PRIMARY KEY (`id`),
                  INDEX `name` (`name`),
                  INDEX `itemtype` (`itemtype`),
                  INDEX `entities_id` (`entities_id`),
                  INDEX `is_active` (`is_active`),
                  INDEX `date_mod` (`date_mod`),
                  INDEX `is_recursive` (`is_recursive`),
                  INDEX `notificationtemplates_id` (`notificationtemplates_id`)
               )
               COLLATE='utf8_unicode_ci'
               ENGINE=MyISAM";

      $DB->query($query);

      // == UPDATE TO 0.90+1.2

      // This new field is for save a report id
      $migration->addField($table, 'report', "INT(11) NULL DEFAULT '0'");
      $migration->addField($table, 'default_delay', "VARCHAR(10) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci'");
      $migration->addField($table, 'report_name', "VARCHAR(255) NULL DEFAULT '' COLLATE 'utf8_unicode_ci'");
      $migration->migrationOneTable($table);

      // == Delete core notification ==

      // Delete targets of core notification
      $notification = new Notification();
      foreach ($notification->find("itemtype = '".__CLASS__."'") as $notif) {
         $DB->query("DELETE FROM glpi_notificationtargets WHERE notifications_id = ".$notif['id']);
      }

      //Note : can delete Log attached to this

      // Delete core notification
      $query = "DELETE FROM glpi_notifications WHERE itemtype = '".__CLASS__."'";
      $DB->query($query);

   }

   /**
    * Remove mreporting notifications from GLPI.
    *
    * @return array 'success' => true on success
    */
   static function uninstall(Migration $migration) {
      global $DB;

      $queries = array();

      // Remove NotificationTargets and Notifications
      $notification = new Notification();
      foreach ($notification->find("itemtype = 'PluginMreportingNotification'") as $notif) {
         $queries[] = "DELETE FROM glpi_notificationtargets WHERE notifications_id = " . $notif['id'];
         $queries[] = "DELETE FROM glpi_notifications WHERE id = " . $notif['id'];
      }

      // Remove NotificationTemplateTranslations and NotificationTemplates
      $template = new NotificationTemplate();
      foreach ($template->find("itemtype = 'PluginMreportingNotification'") as $row) {
         $template_id = $row['id'];
         $queries[] = "DELETE FROM glpi_notificationtemplatetranslations
                        WHERE notificationtemplates_id = " . $template_id;
         $queries[] = "DELETE FROM glpi_notificationtemplates
                        WHERE id = " . $template_id;
      }

      // Cleanup since 0.90+1.2
      $queries[] = "DELETE FROM glpi_events WHERE type = 'pluginmreportingnotifications'";

      foreach ($queries as $query) {
         $DB->query($query);
      }

      $migration->dropTable('glpi_plugin_mreporting_notifications');
   }

   /**
    * @param $event
    * @param $itemtype
    * @param $entity
   **/
   static function getNotificationsByEventAndType($event, $itemtype, $entity) {
      global $DB;

      $entity_where = getEntitiesRestrictRequest("AND", "glpi_plugin_mreporting_notifications", 'entities_id', $entity, true);

      $query = "SELECT `glpi_plugin_mreporting_notifications`.*
                FROM `glpi_plugin_mreporting_notifications`
                LEFT JOIN `glpi_entities`
                  ON (`glpi_entities`.`id` = `glpi_plugin_mreporting_notifications`.`entities_id`)
                WHERE `glpi_plugin_mreporting_notifications`.`itemtype` = '$itemtype' 
                  AND `glpi_plugin_mreporting_notifications`.`event` = '$event' 
                  $entity_where
                  AND `glpi_plugin_mreporting_notifications`.report > 0 
                  AND `glpi_plugin_mreporting_notifications`.notificationtemplates_id != 0
                  AND `glpi_plugin_mreporting_notifications`.`is_active` = '1'
                  ORDER BY `glpi_entities`.`level` DESC";

      return $DB->request($query);
   }

   /**
     * @see parent function
     **/
   function getSearchOptions() {
      $tab = parent::getSearchOptions();

      //No need 'event' search option (because exist a only event)
      unset($tab[2]);

      //Fix a GLPI bug : Don't want to have 'contain' in search option is_active
      $tab[6]['searchtype'] = array('equals', 'notequals');

      // Report (name of)
      $tab[100] = array(
            'table'         => $this->getTable(),
            'field'         => 'report_name',
            'name'          => __("Report", 'mreporting'),
            'searchtype'    => 'contains',
            'massiveaction' => false,
      );

      // Delay
      $tab[101] = array(
            'table'         => $this->getTable(),
            'field'         => 'default_delay',
            'name'          => _n("Time", "Times", 1),
            'searchtype'    => 'contains',
            'massiveaction' => true,
      );

      return $tab;
   }

   /**
    * Give localized information about a task
    *
    * @param $name of the task
    *
    * @return array of strings
    */
   static function cronInfo($name) {
      switch ($name) {
      	case 'SendNotifications' :
      	   return array('description' => __('Notification for "More Reporting"', 'mreporting'));
      }
      return array();
   }
   
   /**
    * @param $mailing_options
   **/
   static function send($mailing_options) {
      $mail = new PluginMreportingNotificationMail();
      $mail->sendNotification($mailing_options);
   }

   /**
    * Execute 1 task manage by the plugin
    *
    * @param CronTask $task Object of CronTask class for log / stat
    *
    * @return interger
    *    >0 : done
    *    <0 : to be run again (not finished)
    *     0 : nothing to do
    */
   static function cronSendNotifications($task) {
      $task->log(__("Notification(s) sent !", 'mreporting'));
      PluginMreportingNotificationEvent::raiseEvent('sendReporting', new self(), $task->fields);
      return 1;
   }
}
