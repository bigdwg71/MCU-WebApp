<?php
//Ellen Settings
$db_hostname = 'localhost';
$db_username = 'root';
$db_password = '3LL3n$h0w';
$db_database = 'mcuDB50';

//Chris Settings
//$db_hostname = 'localhost';
//$db_username = 'root';
//$db_password = 'C1sc0123';
//$db_database = 'mcuDB4085';

//Peter Settings
//$db_hostname = 'localhost';
//$db_username = 'root';
//$db_password = 'root';
//$db_database = 'kimmel99';

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
