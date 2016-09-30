<?php
include ("../../../inc/includes.php");

Session::checkLoginUser();

Session::checkRight("notification", READ);

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

$notification = new PluginMreportingNotification();
if (isset($_POST["add"])) {
   $notification->check(-1, CREATE,$_POST);

   $newID = $notification->add($_POST);
   if ($newID) {
      Event::log($newID, "pluginmreportingnotifications", 4, "notification",
              sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));

      Html::redirect($_SERVER['PHP_SELF']."?id=$newID");
   } else {
      Html::back();
   }

} else if (isset($_POST["purge"])) {
   $notification->check($_POST["id"], PURGE);
   $notification->delete($_POST, 1);

   Event::log($_POST["id"], "pluginmreportingnotifications", 4, "notification",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $notification->redirectToList();

} else if (isset($_POST["update"])) {
   $notification->check($_POST["id"], UPDATE);

   if (isset($_POST['lastrun'])) {

      $lastrun_tmstmp    = strtotime($_POST['lastrun']);
      $nextrun_tmstmp    = $lastrun_tmstmp+$_POST['frequency'];

      if ($_POST['frequency'] == 2592000 && $nextrun_tmstmp > strtotime('last day of next month')) {
        $nextrun_tmstmp = strtotime('last day of next month');
      }

      $_POST['nextrun']  = date('Y-m-d ', $nextrun_tmstmp);
      $_POST['nextrun'] .= $_POST['sending_hour'];

   }

   if (!isset($_POST['sending_day'])) {
    $_POST['sending_day'] = 'NULL';
   }

   if ($notification->update($_POST)) {
      Event::log($_POST["id"], "pluginmreportingnotifications", 4, "notification",
                 //TRANS: %s is the user login
                 sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   }
   Html::back();

} else if (isset($_POST['resetRuns'])) {
  $notification->resetRuns($_POST['id']);
  Html::back();
} else {
   $title = PluginMreportingNotification::getTypeName(Session::getPluralNumber());
   Html::header($title, '' ,'tools', 'PluginMreportingCommon', 'notification');

   $notification->display(array('id' => $_GET["id"]));
   Html::footer();
}
