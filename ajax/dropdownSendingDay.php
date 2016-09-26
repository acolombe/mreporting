<?php

   include ("../../../inc/includes.php");

   // Select notification's config from DB
   $notification = new PluginMreportingNotification();
   $notification->getFromDB($_POST['id']);

   // Set pre-selected value
   $value = 0;

   // If selected frequency is equal to notification's frequency config in DB, pre-set value
   if (isset($notification->fields['frequency']) &&
       $_POST['value'] == $notification->fields['frequency']) {
      $value = $notification->fields['sending_day'];
   }

   // Daily ==> no selector displayed
   if ($_POST['value'] == 86400) {
      return;
   }

   // Weekly
   else if ($_POST['value'] == 604800) {
      Dropdown::showFromArray('sending_day',
                              array(1=>__('Sunday'),
                                    2=>__('Monday'),
                                    3=>__('Tuesday'),
                                    4=>__('Wednesday'),
                                    5=>__('Thursday'),
                                    6=>__('Friday'),
                                    7=>__('Saturday')),
                              array('value'=>$value));
   }

   // Monthly
   else if ($_POST['value'] == 2592000) {
      Dropdown::showNumber('sending_day', array('min'=>1, 'max'=>31, 'value'=>$value));
   }

?>