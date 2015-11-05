<?php

class PluginMreportingNotification extends Notification {

   public $dohistory = true;

   /**
    * Return the localized name of the current Type (PluginMreporting)
    *
    * @see CommonGLPI::getTypeName()
    * @param string $nb
    * @return string name of the plugin
    */
   static function getTypeName($nb = 0) {
      return __("More Reporting", 'mreporting');
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

   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>" . __('Name') . "</td>";
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
      if (!Session::haveRight(static::$rightname, UPDATE)) {
         $itemtype = $this->fields['itemtype'];
         echo $itemtype::getTypeName(1);
         $rand ='';
      } else {
         $rand = Dropdown::showItemTypes('itemtype',
                                         array("PluginMreportingNotification"), //Only PluginMreportingNotification
                                         array('value' => $this->fields['itemtype']));
      }

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
      //TODO : Change string
      echo "<td>". __("Select a report to add", 'mreporting') ."</td>";
      echo "<td><span id='show_reports'>";
      echo PluginMreportingCommon::getSelectAllReports(false, true, $this->fields['report']);
      echo "</span></td></tr>";

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

         // Création de la notification
         $notification = new Notification();
         $notification_id = $notification->add(array(
            'name'                     => __('Notification for "More Reporting"', 'mreporting'),
            'comment'                  => "",
            'entities_id'              => 0,
            'is_recursive'             => 1,
            'is_active'                => 1,
            'itemtype'                 => __CLASS__,
            'notificationtemplates_id' => $template_id,
            'event'                    => 'sendReporting',
            'mode'                     => 'mail')
         );

         $DB->query('INSERT INTO glpi_notificationtargets (items_id, type, notifications_id)
              VALUES (1, 1, ' . $notification_id . ');');
      }

      //From Notification
      $query = "CREATE TABLE `{$this->getTable()}` (
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

      //useless
      return array('success' => true);
   }

   /**
    * Remove mreporting notifications from GLPI.
    *
    * @return array 'success' => true on success
    */
   static function uninstall(Migration $migration) {
      global $DB;

      //TODO : read and check this code

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

      foreach ($queries as $query) {
         $DB->query($query);
      }

      return array('success' => true);
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
