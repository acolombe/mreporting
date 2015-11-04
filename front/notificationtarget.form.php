<?php
include ("../../../inc/includes.php");

Session::checkCentralAccess();

// For don't have any PHP notices if direct access
if (!empty($_POST)) {
	// Save
	PluginMreportingNotificationTarget::updateTargets($_POST);
}

Html::back();
