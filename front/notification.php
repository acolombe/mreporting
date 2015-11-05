<?php
include ("../../../inc/includes.php");

Session::checkRight("notification", READ);

$title = PluginMreportingNotification::getTypeName(Session::getPluralNumber());
Html::header($title, $_SERVER["PHP_SELF"], "plugins", "pluginmreportingmenu", "notification");

Search::show('PluginMreportingNotification');

Html::footer();