<?php
include ("../../../inc/includes.php");

//Session::checkRight("notification", READ);

Html::header(PluginMreportingNotification::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 
	"config", "notification", "notification");

Search::show('PluginMreportingNotification');

Html::footer();