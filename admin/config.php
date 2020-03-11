<?php
//Kimmel Settings
$db_hostname = 'localhost';
$db_username = 'root';
$db_password = 'j1mmyk1mm3l';
$db_database = 'mcuDB7';

//read all settings from the setting table
$allSettings = databaseQuery('readAllSettings', 'blah');
//Set required variables
$mcuIP = $allSettings['settings']['mcuIP']['value'];
$mcuURL = 'http://' . $mcuIP;
$mcuRpc = $mcuURL . '/RPC2';
$mcuUsername = $allSettings['settings']['mcuUsername']['value'];
$mcuPassword = $allSettings['settings']['mcuPassword']['value'];
$domainName = $allSettings['settings']['domainName']['value'];
$loopURI = $allSettings['settings']['loopDN']['value'] . '@' . $domainName;
//$wallConference = $allSettings['settings']['wallConference']['value'];
$waitingRoom = $allSettings['settings']['waitingRoom']['value'];
$sortField = $allSettings['settings']['sortField']['value'];