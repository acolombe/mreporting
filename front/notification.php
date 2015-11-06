<?php
include ("../../../inc/includes.php");

Session::checkRight("notification", READ);

$title = PluginMreportingNotification::getTypeName(Session::getPluralNumber());
Html::header($title, '' ,'tools', 'PluginMreportingCommon', 'notification');

Search::show('PluginMreportingNotification');

Html::footer();