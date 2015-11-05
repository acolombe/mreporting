<?php
/**
  * @author Emmanuel Haguet
  * @since 0.90+1.2
  **/
class PluginMreportingNotificationTarget extends NotificationTarget {

   // From CommonDBChild
   static public $itemtype          = 'PluginMreportingNotification';
   static public $items_id          = 'notifications_id';
   public $table                    = 'glpi_plugin_mreporting_notificationtargets';

   // Temporary hack for this class since 0.84 (need for ->add())
   static function getTable() {
      return getTableForItemType(__CLASS__);
   }

	//From Notificationtarget
	static function getTypeName($nb=0) {
      return _n('Recipient', 'Recipients', $nb);
   }

	static function install(Migration $migration) {
		global $DB;

    $table = self::getTable();

		//From glpi_notificationtargets
		$query = "CREATE TABLE `$table` (
						`id` INT(11) NOT NULL AUTO_INCREMENT,
						`items_id` INT(11) NOT NULL DEFAULT '0',
						`type` INT(11) NOT NULL DEFAULT '0',
						`notifications_id` INT(11) NOT NULL DEFAULT '0',
						PRIMARY KEY (`id`),
						INDEX `items` (`type`, `items_id`),
						INDEX `notifications_id` (`notifications_id`)
					)
					COLLATE='utf8_unicode_ci'
					ENGINE=MyISAM";
		$DB->query($query);
	}

	static function uninstall(Migration $migration) {
	   $migration->dropTable(self::getTable());
	}

	/**
    * @param $notification Notification object
   **/
   function showForNotification(Notification $notification) {
      global $DB;

      if (!Notification::canView()) {
         return false;
      }

      if ($notification->getField('itemtype') != '') {
         $notifications_id = $notification->fields['id'];
         $canedit = $notification->can($notifications_id, UPDATE);

         if ($canedit) {
            $target = Toolbox::getItemTypeFormURL(__CLASS__); //CHANGEMENT

            echo "<form name='notificationtargets_form' id='notificationtargets_form' method='post' action='$target'>";
            echo "<input type='hidden' name='notifications_id' value='".$notification->getField('id')."'>";
            echo "<input type='hidden' name='itemtype' value='".$notification->getField('itemtype')."'>";

         }
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='4'>" . self::getTypeName(Session::getPluralNumber()) . "</th></tr>";
         echo "<tr class='tab_bg_2'>";

         //TODO : Filter $this->notification_targets

         $values = array();
         foreach ($this->notification_targets as $key => $val) {
            list($type,$id) = explode('_', $key);
            //Masque Superviseur(s) de groupe & Groupe(s) sans superviseur
            if ($type <= 3) { //Note : can use a GLPI const (?)
               $values[$key] = $this->notification_targets_labels[$type][$id];
            }

         }

         $targets = getAllDatasFromTable($this->getTable(),
                                         'notifications_id = '.$notifications_id);

         $actives = array();

         foreach ($targets as $data) {
            if ($data['type'] <= 3) { //Security
               $actives[$data['type'].'_'.$data['items_id']] = $data['type'].'_'.$data['items_id'];
            }
         }

         echo "<td>";
         Dropdown::showFromArray('_targets', $values, array('values'   => $actives,
                                                            'multiple' => true,
                                                            'readonly' => !$canedit));
         echo "</td>";
         if ($canedit) {
            echo "<td width='20%'>";
            echo "<input type='submit' class='submit' name='update' value=\""._x('button', 'Update')."\">";
            echo "</td>";

         }
         echo "</tr>";
         echo "</table>";
      }

      if ($canedit) {
         Html::closeForm();
      }
   }

   /**
    * @param $input
   **/
   static function updateTargets($input) {

      $type   = "";
      $action = "";

      //$target = self::getInstanceByType($input['itemtype']);
      $target = new PluginMreportingNotificationTarget();

      if (!isset($input['notifications_id'])) {
         return;
      }
      $targets = getAllDatasFromTable(self::getTable(),
                                      'notifications_id = '.$input['notifications_id']);
      $actives = array();
      if (count($targets)) {
         foreach ($targets as $data) {
            $actives[$data['type'].'_'.$data['items_id']] = $data['type'].'_'.$data['items_id'];
         }
      }
      // Be sure to have items once
      $actives = array_unique($actives);
      if (isset($input['_targets']) && count($input['_targets'])) {
         // Be sure to have items once
         $input['_targets'] = array_unique($input['_targets']);
         foreach ($input['_targets'] as $val) {
            // Add if not set
            if (!isset($actives[$val])) {
               list($type, $items_id)   = explode("_", $val);
               $tmp                     = array();
               $tmp['items_id']         = $items_id;
               $tmp['type']             = $type;
               $tmp['notifications_id'] = $input['notifications_id'];
               $target->add($tmp);
            }
            unset($actives[$val]);
         }
      }

      // Drop others
      if (count($actives)) {
         foreach ($actives as $val) {
            list($type, $items_id) = explode("_", $val);
            if ($target->getFromDBForTarget($input['notifications_id'], $type, $items_id)) {
               $target->delete(array('id' => $target->getID()));
            }
         }
      }
   }

   /**
    * Get a notificationtarget class by giving an itemtype
    *
    * @param $itemtype           the itemtype of the object which raises the event
    * @param $event              the event which will be used (default '')
    * @param $options   array    of options
    *
    * @return a notificationtarget class or false
   **/
   static function getInstanceByType($itemtype, $event='', $options=array()) {

      if (($itemtype != '')
          && ($item = getItemForItemtype($itemtype))) {
         return self::getInstance($item, $event, $options);
      }
      return false;
   }

   //Adapted from Notificationtarget
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == 'Group') {
         self::showForGroup($item);

      } else if ($item->getType() == 'PluginMreportingNotification') {
         //Note : no need $target (?)
         $target = self::getInstanceByType($item->getField('itemtype'),
                                           $item->getField('event'),
                                           array('entities_id' => $item->getField('entities_id')));
         if ($target) {
            $notificationtarget = new self();
            $notificationtarget->showForNotification($item);

            //$target->showForNotification($item); //PluginMreportingNotificationTargetNotification
         }
      }
      return true;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate && Notification::canView()) {
         switch ($item->getType()) {
            //Note : Group -> not tested
            case 'Group' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(Notification::getTypeName(Session::getPluralNumber()),
                                              self::countForGroup($item));
               }
               return Notification::getTypeName(Session::getPluralNumber());

            case 'PluginMreportingNotification' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(self::getTypeName(Session::getPluralNumber()),
                                              countElementsInTable($this->getTable(),
                                                                   "notifications_id
                                                                        = '".$item->getID()."'"));
               }
               return self::getTypeName(Session::getPluralNumber());
         }
      }
      return '';
   }

}