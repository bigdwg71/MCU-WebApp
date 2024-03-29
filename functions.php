<?php

// in this var you will get the absolute file path of the current file
$current_file_path = dirname(__FILE__);
require_once($current_file_path . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'config.php');

$deviceQuery = new stdClass();

function participantNameCmp($aString, $bString){
	
	global $sortField;
	
	if (strtolower($sortField) == 'pane') {
		
		return strnatcasecmp($aString['pane'], $bString['pane']);
		
	} else {
	
		return strnatcasecmp($aString['displayName'], $bString['displayName']);
		
	}
}

function mcuCommand($options, $commandSuffix, $command)
{
        global $mcuRpc;

        // We make the XML_RPC2_Client object (as the backend is not specified, XMLRPCEXT
        // will be used if available (full PHP else))
        $client = XML_RPC2_Client::create($mcuRpc, $options);

    try {

        // Because of the prefix specified in the $options array, indeed,  we will call
        // the approprite method with a struct containing authenitcation info and commands
        $result = $client->$commandSuffix($command);
    } catch (XML_RPC2_FaultException $e) {

        // The XMLRPC server returns a XMLRPC error
        die('Exception #' . $e->getFaultCode() . ' : ' . $e->getFaultString());

    } catch (Exception $e) {

        // Other errors (HTTP or networking problems...)
        die('Exception : ' . $e->getMessage());
    }

    return $result;
}

function databaseConnect()
{
    global $db_hostname;
    global $db_username;
    global $db_password;
    global $db_database;
    global $connection;
    $connection = mysqli_connect($db_hostname, $db_username, $db_password);
    $selection = mysqli_select_db($connection, $db_database);

    if (!$connection) {
        $result = '<p>Database connection failed!: '.mysqli_connect_error().'</p>';
    } elseif ($connection && !$selection) {
        $sql = "CREATE DATABASE IF NOT EXISTS $db_database DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
        $result = mysqli_query($connection, $sql);
        mysqli_select_db($connection, $db_database);
        echo $result;
        $sql = "CREATE TABLE IF NOT EXISTS `panePlacement` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `pane` int(11) NOT NULL,
              `conferenceTableId` int(11) NOT NULL,
              `participantTableId` int(11) DEFAULT NULL,
              `loopParticipantName` int(11) DEFAULT NULL,
              `savedPane` int(11) DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=MEMORY AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";

        mysqli_query($connection, $sql);

        $sql = "CREATE TABLE IF NOT EXISTS `panes` (
              `id` bigint(255) NOT NULL AUTO_INCREMENT,
              `conferenceTableId` int(11) NOT NULL,
              `pane` int(11) NOT NULL,
              `type` varchar(256) NOT NULL,
              `participantName` varchar(256) DEFAULT NULL,
              `participantProtocol` varchar(256) DEFAULT NULL,
              `participantType` varchar(256) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `pane` (`pane`,`conferenceTableId`)
            ) ENGINE=MEMORY AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";

        mysqli_query($connection, $sql);

        $sql = "CREATE TABLE IF NOT EXISTS `settings` (
                `name` varchar(256) NOT NULL,
                `displayName` varchar(256) NOT NULL,
                `value` varchar(256) DEFAULT NULL,
                `id` int(11) NOT NULL AUTO_INCREMENT,
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        $createSettings = mysqli_query($connection, $sql);

        if ($createSettings) {
        $sql = "INSERT INTO `settings` (`name`, `displayName`) VALUES
        ('mcuIP', 'MCU IP Address'),
        ('mcuUsername', 'MCU API Username'),
        ('mcuPassword', 'MCU API Password'),
        ('domainName', 'Domain Name'),
        ('waitingRoom', 'Waiting Room Conference'),
        ('loopDN', 'Loop Number'),
		('timerParticipantsDB', 'MCU Participant Refresh Timer (ms)'),
		('timerConferencesDB', 'MCU Conference Refresh Timer (ms)'),
		('timerPanePlacementDB', 'MCU Pane Placement Refresh Timer (ms)'),
		('timerWebRefresh', 'Web Interface Refresh Timer (ms)'),
		('sortField', 'Sort by Field (name or pane)'),
		('hostID', 'Host Caller ID'),
		('guest1ID', 'Guest 1 Caller ID'),
		('guest2ID', 'Guest 2 Caller ID'),
		('recorderPrefix', 'Recorder Prefix');";
        mysqli_query($connection, $sql);

        $sql = "INSERT INTO `settings` (`name`, `displayName`, `value`) VALUES
        ('timerParticipantsDB', 'MCU Participant Refresh Timer (ms)', '1000'),
        ('timerConferencesDB', 'MCU Conference Refresh Timer (ms)', '5000'),
        ('timerPanePlacementDB', 'MCU Pane Placement Refresh Timer (ms)', '3000'),
        ('timerWebRefresh', 'Web Interface Refresh Timer (ms)', '300')";
        mysqli_query($connection, $sql);
        }

        $sql = "CREATE TABLE IF NOT EXISTS `conferences` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `conferenceName` varchar(256) NOT NULL,
            `conferenceId` int(31) NOT NULL,
            `layout` int(31) NOT NULL,
            `savedLayout` int(11) DEFAULT NULL,
			`codecDN` int(11) DEFAULT NULL,
			`autoExpand` tinyint(1) NOT NULL DEFAULT '0',
			`autoMute` tinyint(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY `conferenceId` (`conferenceId`),
            UNIQUE KEY `conferenceName` (`conferenceName`))
            ENGINE=MEMORY DEFAULT CHARSET=utf8";
        mysqli_query($connection, $sql);

        $sql = "CREATE TABLE IF NOT EXISTS `participants` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `participantName` varchar(256) NOT NULL,
          `displayName` varchar(256) NOT NULL,
          `audioRxMuted` tinyint(1) NOT NULL,
          `videoRxMuted` tinyint(1) NOT NULL,
          `audioTxMuted` tinyint(1) NOT NULL,
          `videoTxMuted` tinyint(1) NOT NULL,
		  `focusType` varchar(256) NOT NULL,
		  `focusParticipant` varchar(256) NOT NULL DEFAULT '0',
          `participantProtocol` varchar(256) NOT NULL,
          `participantType` varchar(256) NOT NULL,
          `conferenceTableId` int(11) NOT NULL,
          `cpLayout` varchar(256) NOT NULL,
          `important` tinyint(1) NOT NULL,
          `packetLossWarning` tinyint(1) NOT NULL,
          `packetLossCritical` tinyint(1) NOT NULL,
          `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`))
          ENGINE=MEMORY DEFAULT CHARSET=utf8";
        mysqli_query($connection, $sql);

        $sql = "CREATE TABLE IF NOT EXISTS `intSettings` (
          `name` varchar(256) NOT NULL,
          `value` varchar(256) NOT NULL,
          `id` int(11) NOT NULL AUTO_INCREMENT,
          PRIMARY KEY (`id`))
          ENGINE=InnoDB  DEFAULT CHARSET=utf8";
        $createIntSettings = mysqli_query($connection, $sql);

        if ($createIntSettings) {
            $sql = "INSERT INTO `intSettings` (`name`, `value`) VALUES
                    ('showIsLive', 'false');";
            mysqli_query($connection, $sql);
        }
            $result = $connection;
    } else {
            $result = $connection;
    }

    return($result);
}

function databaseQuery($action, $data)
{

    if (isset($connection) == false) {
        $connection = databaseConnect();
    }

    if ($action == 'updateParticipantsDB') {
        $result = updateParticipantsDB($data, $connection);
    } elseif ($action == 'updateConferencesDB') {
        $result = updateConferencesDB($data, $connection);
    } elseif ($action == 'updatePanesDB') {
        $result = updatePanesDB($data, $connection);
    } elseif ($action == 'readPanesDB') {
        $result = readPanesDB($data, $connection);
    } elseif ($action == 'readAllSettings') {
        $result = readAllSettings($connection);
    } elseif ($action == 'readSetting') {
        $result = readSetting($data, $connection);
    } elseif ($action == 'updateSettings') {
        $result = updateSettings($data, $connection);
    } elseif ($action == 'readIntSetting') {
        $result = readIntSetting($data, $connection);
    } elseif ($action == 'writeIntSetting') {
        $result = writeIntSetting($data, $connection);
    } elseif ($action == 'panePlacementUpdate') {
        $result = panePlacementUpdate($data, $connection);
    } elseif ($action == 'conferenceUpdate') {
        $result = conferenceUpdate($data, $connection);
    } elseif ($action == 'participantUpdate') {
        $result = participantUpdate($data, $connection);
    } elseif ($action == 'conferenceSavedLayout') {
        $result = conferenceSavedLayout($data, $connection);
    } elseif ($action == 'savedPane') {
        $result = savedPane($data, $connection);
    } elseif ($action == 'findCurrentPane') {
        $result = findCurrentPane($data, $connection);
    } elseif ($action == 'participantInfo') {
        $result = participantInfo($data, $connection);
    } elseif ($action == 'findConferenceLoop') {
        $result = findConferenceLoop($data, $connection);
    } elseif ($action == 'allParticipants') {
        $result = allParticipants($data, $connection);
    } elseif ($action == 'allConferences') {
        $result = allConferences($data, $connection);
    } elseif ($action == 'conferenceInfo') {
        $result = conferenceInfo($data, $connection);
    } elseif ($action == 'codecInfo') {
        $result = codecInfo($data, $connection);
    } elseif ($action == 'updateConferenceSetting') {
        $result = updateConferenceSetting($data, $connection);
    } elseif ($action == 'readConferenceSetting') {
        $result = readConferenceSetting($data, $connection);
    } elseif ($action == 'conferenceCount') {
        $result = conferenceCount($data, $connection);
    } elseif ($action == 'updateConferenceLayout') {
        $result = updateConferenceLayout($data, $connection);
    } else {
        $result = array('alert' => 'DB function not found!');
    }

    mysqli_close($connection);

    return($result);
}

function checkPanePlacement($conferenceName, $mcuUsername, $mcuPassword)
{
    //check the current pane placement in the conference

    $result = mcuCommand(
        array('prefix' => 'conference.paneplacement.'),
        'query',
        array('authenticationUser' => $mcuUsername,
              'authenticationPassword' => $mcuPassword,
              'conferenceName' => $conferenceName)
    );


    return array('alert' => '','panePlacement' => $result);
}

//This function reads all information from the database and presents it to the web
function refreshWeb($mcuUsername, $mcuPassword)
{

    $participantArray = [];
    $conferenceArray = [];
	$codecArray = [];

    //Build the conference array to pass back to refresher.js to present to the page
    $allConferences = databaseQuery('allConferences', 'NA');
    $j = 0;

    foreach ($allConferences as $conferenceInstance) {
        $conferenceName = $conferenceInstance['conferenceName'];
        $conferenceArray[$conferenceName]['conferenceName'] = $conferenceName;
        $conferenceArray[$conferenceName]['customLayout'] = $conferenceInstance['layout'];
        $conferenceArray[$conferenceName]['uniqueId'] = $conferenceInstance['conferenceId'];
		$conferenceArray[$conferenceName]['codecDN'] = $conferenceInstance['codecDN'];

        $data['conferenceName'] = $conferenceName;
        $readPanesFromDB = databaseQuery('readPanesDB', $data);

        foreach ($readPanesFromDB as $pane) {
            $conferenceArray[$conferenceName]['panes']['pane'.$j] = $pane;
            $j++;
        }
    }

    //Build the participant array to pass back to refresher.js
    $i = 0;
    $participantEnumerate = databaseQuery('allParticipants', 'NA');

    if ($participantEnumerate) {
        foreach ($participantEnumerate as $entry => $participantInstance) {
            if (is_array($participantInstance)) {
                $participantArray[$i]['participantName'] = $participantInstance['participantName'];
                $data['participantName'] = $participantInstance['participantName'];
                $data['displayName'] = addslashes($participantInstance['displayName']);
                $conferenceName = $participantInstance['conferenceName'];
				$data['conferenceName'] = $conferenceName;
				
				//error_log("participantInstance: " . json_encode($participantInstance));
                $currentPane = databaseQuery('findCurrentPane', $data);
                if (!isset($currentPane['pane']) || $participantInstance['displayName'] == '_') {
                    $participantArray[$i]['pane'] = '';
                } else {
                    $participantArray[$i]['pane'] = $currentPane['pane'];
                }
				
                $participantArray[$i]['displayName'] = $participantInstance['displayName'];
                $participantArray[$i]['audioRxMuted'] = filter_var($participantInstance['audioRxMuted'], FILTER_VALIDATE_BOOLEAN);
                $participantArray[$i]['videoRxMuted'] = filter_var($participantInstance['videoRxMuted'], FILTER_VALIDATE_BOOLEAN);
                $participantArray[$i]['audioTxMuted'] = filter_var($participantInstance['audioTxMuted'], FILTER_VALIDATE_BOOLEAN);
                $participantArray[$i]['videoTxMuted'] = filter_var($participantInstance['videoTxMuted'], FILTER_VALIDATE_BOOLEAN);
				$participantArray[$i]['focusType'] = $participantInstance['focusType'];
				
				//Check if its a codec to build an array of Codecs
				if ($participantInstance['displayName'] == '__') {
					$codecArray[$conferenceName]['conferenceName'] = $conferenceName;
					$codecArray[$conferenceName]['participantName'] = $participantInstance['participantName'];
					$codecArray[$conferenceName]['focusType'] = $participantInstance['focusType'];
				}
				
				//If its a codec AND its in focusType of participant, add the participant
				if ($participantInstance['focusType'] == "participant" && $participantInstance['displayName'] == '__') {
					$participantArray[$i]['focusParticipant'] = $participantInstance['focusParticipant'];
					$codecArray[$conferenceName]['focusParticipant'] = $participantInstance['focusParticipant'];
				} else {
					$participantArray[$i]['focusParticipant'] = 0;
				}
				
                $participantArray[$i]['participantProtocol'] = $participantInstance['participantProtocol'];
                $participantArray[$i]['participantType'] = $participantInstance['participantType'];
                $participantArray[$i]['conferenceName'] = $participantInstance['conferenceName'];

                if (array_key_exists('currentLayout', $participantInstance)) {
                    $participantArray[$i]['cpLayout'] = $participantInstance['currentLayout'];
                } else {
                    $participantArray[$i]['cpLayout'] = '';
                }

                $participantArray[$i]['important'] = filter_var($participantInstance['important'], FILTER_VALIDATE_BOOLEAN);
                $participantArray[$i]['packetLossWarning'] = filter_var($participantInstance['packetLossWarning'], FILTER_VALIDATE_BOOLEAN);
                $participantArray[$i]['packetLossCritical'] = filter_var($participantInstance['packetLossCritical'], FILTER_VALIDATE_BOOLEAN);
                $i++;
            }
        }
    }

    // Sort the multidimensional array
    ksort($conferenceArray);
    usort($participantArray, "participantNameCmp");

    $debugArray = '';

    //Get Version number of WebApp
    $appVersion = basename(__DIR__);

    $query['name'] = 'showIsLive';
    $showIsLive = databaseQuery('readIntSetting', $query);

    if (empty($deviceQuery)) {
        $deviceQuery = mcuCommand(
            array('prefix' => 'device.'),
            'query',
            array('authenticationUser' => $mcuUsername,
                  'authenticationPassword' => $mcuPassword)
        );
    }

    echo json_encode(
        array('participantArray' => $participantArray,
              'conferenceArray' => $conferenceArray,
			  'codecArray' => $codecArray,
              'showIsLive' => $showIsLive,
              'debugArray' => $debugArray,
              'appVersion' => $appVersion,
              'deviceQuery' => $deviceQuery,
              'alert' =>  '')
    );
}

//This function queries the MCU for all participant
//data and then writes it to the database. We are no
//longer directly updating the web interface with the enumerate
function writeParticipantEnumerate($mcuUsername, $mcuPassword)
{

    $participantArray = [];

    $participantEnumerate = mcuCommand(
        array('prefix' => 'participant.'),
        'enumerate',
        array('authenticationUser' => $mcuUsername,
        'authenticationPassword' => $mcuPassword,
        'operationScope' => array('currentState'),
        'enumerateFilter' => 'connected')
    );

    //If we find we didn't get all the results in the first call make more calls until we have everything
    while (empty($participantEnumerate['enumerateID']) == false) {
        //die("foo");
        $participantEnumerateAdd = mcuCommand(
            array('prefix' => 'participant.'),
            'enumerate',
            array('authenticationUser' => $mcuUsername,
            'authenticationPassword' => $mcuPassword,
            'operationScope' => array('currentState'),
            'enumerateFilter' => 'connected',
            'enumerateID' => $participantEnumerate['enumerateID'])
        );
        unset($participantEnumerate['enumerateID']);
        $participantEnumerate = array_merge_recursive($participantEnumerate, $participantEnumerateAdd);
    }

    $i = 0;
    $waitingRoom = databaseQuery('readSetting', 'waitingRoom');
	
	//Build list of autoMute conferences
	$querySetting['setting'] = "autoMute";
	$autoMuteConferences = databaseQuery('readConferenceSetting', $querySetting);
	$allParticipants = databaseQuery('allParticipants', 'NA');
	
	$participantConferenceCount = array();
	
	if (isset($participantEnumerate['participants'])) {
        foreach ($participantEnumerate['participants'] as $entry => $participantInstance) {
            if (is_array($participantInstance)) {
				
				//error_log(json_encode($participantInstance));
				
				if (!isset($participantConferenceCount[$participantInstance['conferenceName']])) {
					$participantConferenceCount[$participantInstance['conferenceName']] = array();
				}
				
				$participantConferenceCount[$participantInstance['conferenceName']] = intval($participantConferenceCount[$participantInstance['conferenceName']]) + 1;

                $participantArray[$i]['participantName'] = $participantInstance['participantName'];
				/*
                $data['participantName'] = $participantInstance['participantName'];
                $data['conferenceName'] = $participantInstance['conferenceName'];

                $currentPane = databaseQuery('findCurrentPane', $data);

                if ($currentPane['pane'] == null || $participantInstance['currentState']['displayName'] == '_') {
                    $participantArray[$i]['pane'] = '';
                } else {
                    $participantArray[$i]['pane'] = $currentPane['pane'];
                }
				*/
                $participantArray[$i]['displayName'] = $participantInstance['currentState']['displayName'];
                $participantArray[$i]['audioRxMuted'] = $participantInstance['currentState']['audioRxMuted'];
                $participantArray[$i]['videoRxMuted'] = $participantInstance['currentState']['videoRxMuted'];
                $participantArray[$i]['audioTxMuted'] = $participantInstance['currentState']['audioTxMuted'];
                $participantArray[$i]['videoTxMuted'] = $participantInstance['currentState']['videoTxMuted'];
				$participantArray[$i]['focusType'] = $participantInstance['currentState']['focusType'];
				
				if ($participantInstance['currentState']['focusType'] == "participant" && $participantInstance['currentState']['layoutSource'] == "conferenceCustom") {
					$participantArray[$i]['focusType'] = "voiceActivated";
					$participantArray[$i]['focusParticipant'] = 0;
				} elseif ($participantInstance['currentState']['focusType'] == "participant") {
					$participantArray[$i]['focusParticipant'] = $participantInstance['currentState']['focusParticipant']['participantName'];
				} else {
					$participantArray[$i]['focusParticipant'] = 0;
				}
				
                $participantArray[$i]['participantProtocol'] = $participantInstance['participantProtocol'];
                $participantArray[$i]['participantType'] = $participantInstance['participantType'];
                $participantArray[$i]['conferenceName'] = $participantInstance['conferenceName'];

                if (array_key_exists('currentLayout', $participantInstance['currentState'])) {
                    $participantArray[$i]['cpLayout'] = $participantInstance['currentState']['currentLayout'];
                } else {
                    $participantArray[$i]['cpLayout'] = '';
                }

                $participantArray[$i]['important'] = $participantInstance['currentState']['important'];
                $participantArray[$i]['packetLossWarning'] = $participantInstance['currentState']['packetLossWarning'];
                $participantArray[$i]['packetLossCritical'] = $participantInstance['currentState']['packetLossCritical'];
                
				//Build variables to test whether or not the user has been seen before or not.
				$conferenceSearch = array_search($participantInstance['conferenceName'], array_column($autoMuteConferences, 'conferenceName'));
				$participantSearch = array_search($participantInstance['participantName'], array_column($allParticipants, 'participantName'));
				$participantCount = count($allParticipants);
				
				//error_log("Conference Search: " . json_encode($conferenceSearch) . " | Participant Search: " . json_encode($participantSearch) . " | Participant Count: " . json_encode($participantCount));
				
				if ( $conferenceSearch !== FALSE && $participantSearch === FALSE && $participantCount > 0 ) {
					
					//Mute the participant in the MCU
					$result = mcuCommand(
						array('prefix' => 'participant.'),
						'modify',
						array('authenticationUser' => $mcuUsername,
							  'authenticationPassword' => $mcuPassword,
							  'conferenceName' => $participantInstance['conferenceName'],
							  'participantName' => $participantInstance['participantName'],
							  'participantProtocol' => $participantInstance['participantProtocol'],
							  'participantType' => $participantInstance['participantType'],
							  'operationScope' => 'activeState',
							  'audioRxMuted' => TRUE,
							  'videoRxMuted' => TRUE)
					);
					
					//Mute the participant in the database
					$participantArray[$i]['audioRxMuted'] = TRUE;
					$participantArray[$i]['videoRxMuted'] = TRUE;
				}
				
				$i++;
				
            }
        }
    }
	//handle auto-expanding conferences
	$querySetting['setting'] = "autoExpand";
	$autoExpandConferences = databaseQuery('readConferenceSetting', $querySetting);
	
	//error_log("autoExpandConferences: " . json_encode($autoExpandConferences));
	
	if (count($autoExpandConferences) > 0) {
		foreach ($autoExpandConferences as $conference) {
			
			$conferenceName = $conference['conferenceName'];
			$conferenceInfo = databaseQuery('conferenceInfo', $conferenceName);
			//$conferenceCount = databaseQuery('conferenceCount', $conferenceInfo['id']);
			$currentLayout = $conference['layout'];
			
			$oldParticipantCount = apc_fetch('oldParticipantCount',$success);
			
			if (!isset($oldParticipantCount[$conferenceName])) {
				$oldParticipantCount[$conferenceName] = 0;
			}
			
			if (!isset($participantConferenceCount[$conferenceName])) {
				$participantConferenceCount[$conferenceName] = 0;
			}
					
			if ($success === TRUE && intval($oldParticipantCount[$conferenceName]) !== intval($participantConferenceCount[$conferenceName])) {
				
				//error_log("conference Name: " . $conferenceName);
				//error_log("Conference Old Count: " . $oldParticipantCount[$conferenceName]);
				//error_log("conference Current Count: " . $participantConferenceCount[$conferenceName]);
				
				$findLoop['conferenceTableId'] = $conferenceInfo['id'];
				$conferenceLoop = databaseQuery('findConferenceLoop', $findLoop);
				$conferenceCount = $participantConferenceCount[$conferenceName];
				
				//error_log("conference loop: " . json_encode($conferenceLoop));
				
				//If there is a loop in the conference, then we want to subtract one participant
				if (isset($conferenceLoop['participantName'])) {
					//error_log("Loop Found!");
					$conferenceCount = $conferenceCount - 1;
				}
				
				//error_log("conference count: " . $conferenceCount);
				
				if ($conferenceCount >= 0 && $conferenceCount <= 2) {
					$newConferenceLayout = 1;
				} elseif ($conferenceCount >= 3 && $conferenceCount <= 5) {
					$newConferenceLayout = 2;
				} elseif ($conferenceCount == 6 || $conferenceCount == 7) {
					$newConferenceLayout = 8;
				} elseif ($conferenceCount == 8 || $conferenceCount == 9) {
					$newConferenceLayout = 53;
				} elseif ($conferenceCount == 10) {
					$newConferenceLayout = 3;
				} elseif ($conferenceCount >= 11 && $conferenceCount <= 13) {
					$newConferenceLayout = 9;
				} elseif ($conferenceCount >= 14 && $conferenceCount <= 17) {
					$newConferenceLayout = 4;
				} elseif ($conferenceCount >= 18) {
					$newConferenceLayout = 43;
				} else {
					$newConferenceLayout = 0;
				}
				
				if ($currentLayout != $newConferenceLayout && $newConferenceLayout != 0) {
					$result = mcuCommand(
						array('prefix' => 'conference.'),
						'modify',
						array('authenticationUser' => $mcuUsername,
							  'authenticationPassword' => $mcuPassword,
							  'conferenceName' => $conferenceName,
							  'customLayoutEnabled' => true,
							  'customLayout' => $newConferenceLayout,
							  'newParticipantsCustomLayout' => true,
							  'setAllParticipantsToCustomLayout' => true)
					);
					
					$conferenceInfo['layout'] = $newConferenceLayout;
					
					$changeLayout = databaseQuery('updateConferenceLayout', $conferenceInfo);
				}
			}
		}
	}
	
	apc_store('oldParticipantCount',$participantConferenceCount);
	
	//Write participant info to database
	//error_log("Participant Array: " . json_encode($participantArray));
    databaseQuery('updateParticipantsDB', $participantArray);
	
    echo json_encode(array('alert' => ''));
    return($participantArray);
}

//This function queries the MCU for all conference data and then writes it to the database
function writeConferenceEnumerate($mcuUsername, $mcuPassword)
{
    $conferenceArray = [];
	
    $conferenceEnumerate = mcuCommand(
        array('prefix' => 'conference.'),
        'enumerate',
        array('authenticationUser' => $mcuUsername,
        'authenticationPassword' => $mcuPassword,
        'enumerateFilter' => 'active')
    );
	
	
	
    while (empty($conferenceEnumerate['enumerateID']) == false) {
        $conferenceEnumerateAdd = mcuCommand(
            array('prefix' => 'conference.'),
            'enumerate',
            array('authenticationUser' => $mcuUsername,
            'authenticationPassword' => $mcuPassword,
            'enumerateFilter' => 'active',
            'enumerateID' => $conferenceEnumerate['enumerateID'])
        );

        unset($conferenceEnumerate['enumerateID']);
        unset($conferenceEnumerate['currentRevision']);
        $conferenceEnumerate = array_merge_recursive($conferenceEnumerate, $conferenceEnumerateAdd);

    }

    if (isset($conferenceEnumerate['conferences'])) {
        foreach ($conferenceEnumerate['conferences'] as $entry => $conferenceInstance) {
            if (is_array($conferenceInstance)) {
                $conferenceName = $conferenceInstance['conferenceName'];
                $conferenceArray[$conferenceName]['conferenceName'] = $conferenceName;
                $conferenceArray[$conferenceName]['customLayout'] = $conferenceInstance['customLayout'];
                $conferenceArray[$conferenceName]['uniqueId'] = $conferenceInstance['uniqueId'];
            }
        }
    }
	
	
    $conferenceWrite = databaseQuery('updateConferencesDB', $conferenceArray);
	
    return($conferenceWrite);
}

//This function reads panePlacement data from the MCU and writes it to the database
function writePanesDB($mcuUsername, $mcuPassword, $conferenceArray)
{

	if (count($conferenceArray) > 0) {
		foreach ($conferenceArray as $conference) {
			$j = 0;
			$conferenceName = $conference['conferenceName'];
			
			$currentPanePlacement = checkPanePlacement($conferenceName, $mcuUsername, $mcuPassword);
					
			foreach ($currentPanePlacement['panePlacement']['panes'] as $pane) {
				$conferenceArray[$conferenceName]['panes']['pane'.$j] = $pane;
				$j++;
			}
			//error_log(json_encode($conferenceArray[$conferenceName]));
			$updatePanesDB = databaseQuery('updatePanesDB', $conferenceArray[$conferenceName]);			
			
		}
		
		$updatePanesDB = TRUE;
		
	} else {
		
		$updatePanesDB = FALSE;
		
	}
	
    return($updatePanesDB);
}

function readPanesDB($data, $connection)
{
    $conferenceName = $data['conferenceName'];
    $conferenceInfo = databaseQuery('conferenceInfo', $conferenceName);

    $conferenceId = $conferenceInfo['id'];

    $sql = "SELECT * FROM panes
            WHERE conferenceTableId = '" . $conferenceId . "'
            ORDER BY pane ASC";

    $mysqlquery = mysqli_query($connection, $sql);

    if ($mysqlquery) {

        $paneResult = mysqli_fetch_all($mysqlquery, MYSQLI_ASSOC);
        $result = [];

        foreach ($paneResult as $pane) {
            $panes = [];

            $panes['index'] = intval($pane['pane']);
            $panes['type'] = $pane['type'];
            if ($pane['type'] === 'participant') {
                $panes['participantName'] = $pane['participantName'];
                $panes['participantProtocol'] = $pane['participantProtocol'];
                $panes['participantType'] = $pane['participantType'];
            }

            $result[] = $panes;

        }

    } else {

        $result = [];

    }

    return($result);

}

function updateConferencesDB($data, $connection)
{
    $existingConferences = "";

    foreach ($data as $rows => $row) {
        //see if this conference is in the conferences table
        $sql = "SELECT * FROM conferences
        WHERE conferenceId = '".mysqli_real_escape_string($connection, $row['uniqueId'])."'";
        $mysqlquery = mysqli_query($connection, $sql);
        //if this conference isn't in the conferences table, insert it
        if (mysqli_num_rows($mysqlquery) < 1) {
            $sql = "INSERT INTO conferences (`conferenceName`, `conferenceId`, `layout`)
            VALUES ('".mysqli_real_escape_string($connection, $row['conferenceName'])."',
            '".mysqli_real_escape_string($connection, $row['uniqueId'])."',
            '".mysqli_real_escape_string($connection, $row['customLayout'])."')";
			
			$mysqlquery = mysqli_query($connection, $sql);
			
        } else {
             $sql = "UPDATE conferences SET conferenceName ='".mysqli_real_escape_string($connection, $row['conferenceName'])."', layout = '".mysqli_real_escape_string($connection, $row['customLayout'])."'
            WHERE conferenceId = '".mysqli_real_escape_string($connection, $row['uniqueId'])."'";
             $mysqlquery = mysqli_query($connection, $sql);
        }
        if (!$mysqlquery) {
            $result = array('alert' => 'Could not updateConferencesDB, mySQL Query failed!');
        } else {
            $result = true;
        }

        $existingConferences .=  mysqli_real_escape_string($connection, $row['uniqueId']).",";

    }

    if ($existingConferences != "") {
        $existingConferences = rtrim($existingConferences, ",");
    }

    //If there are no conferences on the MCU, then truncate/clear the table
    if ($existingConferences !== "") {
        $sql = "DELETE FROM conferences WHERE conferenceId NOT IN (".$existingConferences.")";
    }

    $mysqlquery = mysqli_query($connection, $sql);
    return($result);
}

function updateParticipantsDB($data, $connection)
{
    //$result = "";
    $existingParticipants = "";
    $updateParticipant = "";
    $conferenceTableId = "";
    $insertParticipant = "EMPTY";

    foreach ($data as $rows => $row) {
        //see if this particicpant is in the participants table
        $sql = "SELECT * FROM participants WHERE participantName = '" . mysqli_real_escape_string($connection, $row['participantName']) . "'";
		$findParticipant = mysqli_fetch_array(mysqli_query($connection, $sql), MYSQLI_ASSOC);
		
		if ($row['displayName'] != "_" && $row['displayName'] != "__") {
			//error_log("find Participant: " . json_encode($findParticipant));
		}
		
        //grab the conferenceTableId field to insert into the participant table
        $sqlConference = "SELECT id FROM conferences WHERE conferenceName='" . mysqli_real_escape_string($connection, $row['conferenceName']) . "'";

        $conferenceTableId = mysqli_query($connection, $sqlConference);

        if ($conferenceTableId) {
            $rowConference = mysqli_fetch_array($conferenceTableId, MYSQLI_ASSOC);
            $conferenceId = $rowConference['id'];
        }
		
        //if this participant isn't in the participants table, insert it
        if (!isset($findParticipant['participantName'])) {
            $sql = "INSERT INTO participants
            (`participantName`,
            `displayName`,
            `audioRxMuted`,
            `videoRxMuted`,
            `audioTxMuted`,
            `videoTxMuted`,
			`focusType`,
            `participantProtocol`,
            `participantType`,
            `conferenceTableId`,
            `cpLayout`,
            `important`,
            `packetLossWarning`,
            `packetLossCritical`)
            VALUES ('".mysqli_real_escape_string($connection, $row['participantName'])."',
            '".mysqli_real_escape_string($connection, $row['displayName'])."',
            '".($row['audioRxMuted']?1:0)."',
            '".($row['videoRxMuted']?1:0)."',
            '".($row['audioTxMuted']?1:0)."',
            '".($row['videoTxMuted']?1:0)."',
            '".mysqli_real_escape_string($connection, $row['focusType'])."',
			'".mysqli_real_escape_string($connection, $row['participantProtocol'])."',
            '".mysqli_real_escape_string($connection, $row['participantType'])."',
            '".mysqli_real_escape_string($connection, $conferenceId)."',
            '".mysqli_real_escape_string($connection, $row['cpLayout'])."',
            '".($row['important']?1:0)."',
            '".($row['packetLossWarning']?1:0)."',
            '".($row['packetLossCritical']?1:0)."')";
            //$insertParticipant = mysqli_query($connection, $sqlInsert);
			
        } else {
            $sql = "UPDATE participants SET
            `displayName` = '".mysqli_real_escape_string($connection, $row['displayName'])."',
            `audioRxMuted` = ".($row['audioRxMuted']?1:0).",
            `videoRxMuted` = ".($row['videoRxMuted']?1:0).",
            `audioTxMuted` = ".($row['audioTxMuted']?1:0).",
            `videoTxMuted` = ".($row['videoTxMuted']?1:0).",
			`focusType` = '".mysqli_real_escape_string($connection, $row['focusType'])."',
			`focusParticipant` = '".mysqli_real_escape_string($connection, $row['focusParticipant'])."',
            `participantProtocol` = '".mysqli_real_escape_string($connection, $row['participantProtocol'])."',
            `participantType` = '".mysqli_real_escape_string($connection, $row['participantType'])."',
            `conferenceTableId` = ".mysqli_real_escape_string($connection, $conferenceId).",
            `cpLayout` = ".mysqli_real_escape_string($connection, $row['cpLayout']).",
            `important` = ".($row['important']?1:0).",
            `packetLossWarning` = ".($row['packetLossWarning']?1:0).",
            `packetLossCritical` = ".($row['packetLossCritical']?1:0)."
            WHERE `participantName` = ".mysqli_real_escape_string($connection, $row['participantName']);
        }
		if ($row['displayName'] != "_" && $row['displayName'] != "__") {
			//error_log("Update Participant SQL: " . $sql);
		}
		$updateParticipant = mysqli_query($connection, $sql);
		
        $existingParticipants .=  mysqli_real_escape_string($connection, $row['participantName']).",";
    }

    if ($existingParticipants !== "") {
        $existingParticipants = rtrim($existingParticipants, ",");
    }

    //If there are no participants in a conference, then truncate/clear the table
    if ($existingParticipants === "") {
        $sqlParticipant = "TRUNCATE TABLE participants";
        $sqlPane = "TRUNCATE TABLE panePlacement";
        $deleteParticipant = mysqli_query($connection, $sqlParticipant);
        $deletePane = mysqli_query($connection, $sqlPane);
    } else {

        //Delete entry from participants table that have dropped or disconnected
        $sqlParticipant = "DELETE FROM participants WHERE participantName NOT IN (".$existingParticipants.")";
        $deleteParticipant = mysqli_query($connection, $sqlParticipant);

        //build sql query to find all panes in paneplacement table that do not have entries in the participants table
        $findPaneSQL = "SELECT pane, conferenceTableId FROM panePlacement WHERE participantTableId NOT IN (SELECT p.id FROM participants p)";
        $findPane = mysqli_fetch_all(mysqli_query($connection, $findPaneSQL), MYSQLI_ASSOC);

        $readdPaneEntry = [];

        //For each returned row from the SQL query, get the conference name and pane index and reset their pane
        foreach ($findPane as $paneRow) {

            $sqlConferenceId = "SELECT conferenceName FROM conferences WHERE id ='".$paneRow['conferenceTableId']."'";
            $conferenceResult = mysqli_fetch_array(mysqli_query($connection, $sqlConferenceId), MYSQLI_ASSOC);

            $conferenceName = $conferenceResult['conferenceName'];
            $paneNumber = intval($paneRow['pane']);

            $mcuUsername = databaseQuery('readSetting', 'mcuUsername');
            $mcuPassword = databaseQuery('readSetting', 'mcuPassword');

            $readdPaneEntry = mcuCommand(
                array('prefix' => 'conference.paneplacement.'),
                'modify',
                array('authenticationUser' => $mcuUsername,
                      'authenticationPassword' => $mcuPassword,
                      'conferenceName' => $conferenceName,
                      'enabled' => true,
                      'panes' => array(['index' => $paneNumber,
                                        'type' => 'default']))
            );

        }

        //Finally, delete the pane from the paneplacement DB
        $sqlPane = "DELETE FROM panePlacement WHERE participantTableId NOT IN (SELECT p.id FROM participants p)";
        $deletePane = mysqli_query($connection, $sqlPane);
    }

    if (!$conferenceTableId) {
        $result = array('alert' => 'Could not updateParticipantsDB: '.mysqli_error($connection).' Query '.$insertParticipant);
    } else {
        $result = '';
    }
    return($result);
}

function updatePanesDB($data, $connection)
{
    $mysqlquery = null;

	//$sql = "SELECT id FROM conferences WHERE conferenceId = '".$data['uniqueId']."'";
	//$conference = mysqli_fetch_array(mysqli_query($connection, $sql), MYSQLI_ASSOC);
	$conferenceTableId = $data['uniqueId'];
	//error_log(json_encode($data));
	$numberOfPanes = count($data['panes']);
	$sql = "DELETE FROM panes WHERE conferenceTableId='" . $conferenceTableId . "' AND pane >'" . $numberOfPanes . "'";
	$mysqlquery = mysqli_query($connection, $sql);

	//Loop through each pane of the conference
	foreach ($data['panes'] as $paneNumber => $paneDetail) {

		if ($paneDetail['type'] == 'participant') {

			//insert pane or overwrite existing
			$paneSQL = "INSERT INTO panes (pane, conferenceTableId, type, participantName, participantProtocol, participantType) VALUES('" . $paneDetail['index'] . "', '" . $conferenceTableId . "', '" . $paneDetail['type'] . "', '" . $paneDetail['participantName'] . "', '" . $paneDetail['participantProtocol'] . "', '" . $paneDetail['participantType'] . "') ON DUPLICATE KEY UPDATE type='" . $paneDetail['type'] . "', participantName='" . $paneDetail['participantName'] . "', participantProtocol='" . $paneDetail['participantProtocol'] . "', participantType='" . $paneDetail['participantType'] . "'";
			
			/* BAD CODE?
			$findPaticipant['participantName'] = $paneDetail['participantName'];
			$participantInfo = databaseQuery('participantInfo', $findPaticipant);
			
			//If the type is participant, add a panePlacement DB entry
			$updatePane['action'] = "updatePane";
			$updatePane['pane'] = $paneDetail['index'];
			$updatePane['conferenceTableId'] = $conferenceTableId;
			$updatePane['participantTableId'] = $participantInfo['id'];
			$updatePaneResult = databaseQuery('panePlacementUpdate', $updatePane);
			*/
		} else {

			//insert pane or overwrite existing
			$paneSQL = "INSERT INTO panes (pane, conferenceTableId, type, participantName, participantProtocol, participantType) VALUES('" . $paneDetail['index'] . "', '" . $conferenceTableId . "', '" . $paneDetail['type'] . "', NULL, NULL, NULL) ON DUPLICATE KEY UPDATE type='" . $paneDetail['type'] . "', participantName=NULL, participantProtocol=NULL, participantType=NULL";
			
			//error_log("delete pane entry: " . $paneDetail['index'] . " from " . $conferenceTableId);
			//If not a participant, then delete any PanePlacement DB entry that might exist for that pane
			$deletePaneEntry['action'] = "deletePaneEntry";
			$deletePaneEntry['pane'] = $paneDetail['index'];
			$deletePaneEntry['conferenceTableId'] = $conferenceTableId;
			$deletePaneEntryResult = databaseQuery('panePlacementUpdate', $deletePaneEntry);
			
		}

		$paneResult = mysqli_query($connection, $paneSQL);
		
	}
		
    return($paneResult);
}

function findCurrentPane($data, $connection)
{
    /*$sql = "SELECT panePlacement.pane FROM panePlacement
    INNER JOIN conferences ON panePlacement.conferenceTableId = conferences.id
    INNER JOIN participants ON panePlacement.participantTableId = participants.id
    WHERE participantName='".$data['participantName']."'
    AND conferences.conferenceName='".$data['conferenceName']."'";
	*/
	
	$conferenceInfo = databaseQuery('conferenceInfo', $data['conferenceName']);
	$sql = "SELECT * FROM panes WHERE participantName='".$data['participantName']."' AND conferenceTableId='".$conferenceInfo['id']."'";

	//error_log("findCurrentPane SQL: " . $sql);
    $mysqlquery = mysqli_query($connection, $sql);
	
	//error_log("findCurrentPane Count: " . mysqli_num_rows($mysqlquery));

    if ($mysqlquery) {

        $result = mysqli_fetch_array($mysqlquery, MYSQLI_ASSOC);
		
    } else {

        $result = array('alert' => '');

    }

    return($result);

}

function readAllSettings($connection)
{
    $sql = "SELECT * FROM settings ORDER BY id ASC";
    $mysqlquery = mysqli_query($connection, $sql);
    if ($mysqlquery) { // Query succeed! :D
        while ($row = mysqli_fetch_array($mysqlquery)) {
            $settingArray[$row['name']]['name'] = $row['name'];
            $settingArray[$row['name']]['displayName'] = $row['displayName'];
            $settingArray[$row['name']]['value'] = $row['value'];
        }
        $result = array('settings' => $settingArray, 'alert' => '');
    } else {
        $result = array('settings' => '', 'alert' => 'Could not readAllSettings: '.mysqli_error($connection));
    }
    return($result);
}

function readSetting($data, $connection)
{
    $sql = "SELECT value FROM settings WHERE name='" . $data . "'";
    $mysqlquery = mysqli_query($connection, $sql);
    //if we are returned a row
    if (mysqli_num_rows($mysqlquery) > 0) {
        $result = mysqli_fetch_assoc($mysqlquery);
        $result = $result['value'];
    } else {
        $result = array('alert' => 'Could not readSetting: '.mysqli_error($connection));
        $result = 0;
    }
    return($result);
}

function updateSettings($data, $connection)
{
    foreach ($data as $setting) {
		
		//error_log("Setting Value: " . json_encode($setting['value']));
		
        $sql = "UPDATE settings SET value='".mysqli_real_escape_string($connection, $setting['value'])."'
        WHERE name='".mysqli_real_escape_string($connection, $setting['name'])."'";
        $mysqlquery = mysqli_query($connection, $sql);
		
		//error_log("settingupdate SQL: " . $sql);
		
        if (!$mysqlquery) {
            $result = array('alert' => 'Could not updateSettings: '.mysqli_error($connection));
        } else {
            $result = true;
        }
    }
    return(true);
}

//takes a conference name and returns all information in the row about that conference
function conferenceInfo($data, $connection)
{

    $sql = "SELECT * FROM conferences
        WHERE conferenceName='" . $data . "'";

    $conference = mysqli_fetch_array(mysqli_query($connection, $sql), MYSQLI_ASSOC);
    return($conference);

}

//takes participantName and returns all information in the row about that participant
function participantInfo($data, $connection)
{

    $sql = "SELECT * FROM participants
        WHERE participantName = '" . $data['participantName'] . "'";

    $participant = mysqli_fetch_array(mysqli_query($connection, $sql), MYSQLI_ASSOC);

    return($participant);

}

//takes conferenceTableID and returns the participant info of the loop in that conference
function findConferenceLoop($data, $connection)
{

    $sql = "SELECT * FROM participants
        WHERE displayname = '_' and conferenceTableId='" . $data['conferenceTableId'] . "'";

    $loop = mysqli_fetch_array(mysqli_query($connection, $sql), MYSQLI_ASSOC);

    return($loop);

}

//finds all participants
function allParticipants($data, $connection)
{
    $sql = "SELECT * FROM participants INNER JOIN conferences ON participants.conferenceTableId = conferences.id";
    $participantEnumerate = mysqli_fetch_all(mysqli_query($connection, $sql), MYSQLI_ASSOC);

    return($participantEnumerate);
}

function allConferences($data, $connection)
{
    $sql = "SELECT * FROM conferences ORDER BY conferenceName";
    $allConferencesQuery = mysqli_fetch_all(mysqli_query($connection, $sql), MYSQLI_ASSOC);

    return($allConferencesQuery);
}

//takes a conference name and returns the participantName(ID) of the codec in that conference
function codecInfo($conferenceName, $connection)
{
    $conference = databaseQuery('conferenceInfo', $conferenceName);
    $conferenceTableId = $conference['id'];

    $sql = "SELECT * FROM participants
        WHERE displayName = '__'
        AND conferenceTableId = '" . $conferenceTableId . "'";

    $codec = mysqli_fetch_array(mysqli_query($connection, $sql), MYSQLI_ASSOC);

    return($codec);

}

//Updates conferences with new autoExpand setting
function updateConferenceSetting($data, $connection)
{
    foreach ($data as $conference) {
		
		if($conference['setting'] == "codecDN" && $conference['value'] == NULL){
			$value = "NULL";
		} else {
			$value = $conference['value'];
		}
		
        $sql = "UPDATE conferences SET " . $conference['setting'] . " = " . $value . " WHERE conferenceName = '" . $conference['name'] . "'";
        $mysqlquery = mysqli_query($connection, $sql);
        
		//error_log("updateConferenceSetting SQL: " . $sql);
		
		if (!$mysqlquery) {
            $result = array('alert' => 'Could not updateSettings: '.mysqli_error($connection));
        } else {
            $result = true;
        }
    }
    return($result);
}

//Updates conferences with new autoExpand setting
function readConferenceSetting($data, $connection)
{
	$count = 0;
	
	$sql = "Select * FROM conferences WHERE " . $data['setting'] . "=1";
	$response = mysqli_query($connection, $sql);
	$count = mysqli_num_rows($response);
	
	//error_log("response SQL: " . json_encode(mysqli_fetch_all($response,MYSQLI_ASSOC)));
	//error_log("count SQL: " . $count);
	
	$results = mysqli_fetch_all($response,MYSQLI_ASSOC);

    return($results);
}

//Write a layout change to DB
function updateConferenceLayout($data, $connection)
{
    $sql = "UPDATE conferences SET layout = " . $data['layout'] . " WHERE conferenceName = '" . $data['conferenceName'] . "'";
    $mysqlquery = mysqli_query($connection, $sql);
        
	//error_log("updateConferenceSetting SQL: " . $sql);
		
	if (!$mysqlquery) {
		$result = array('alert' => 'Could not updateSettings: '.mysqli_error($connection));
	} else {
		$result = true;
	}

    return($result);
}

//Find out how many participants are in a conference
function conferenceCount($data, $connection)
{
	$sql = "Select * FROM participants WHERE conferenceTableId=" . $data;
	$response = mysqli_query($connection, $sql);
	$count = mysqli_num_rows($response);

    return($count);
}


//reads the requested setting from the intSetting table
function readIntSetting($data, $connection)
{
	
    $sql = "SELECT value FROM intSettings
        WHERE name = '" . $data['name'] . "'";
	
	$result = mysqli_fetch_array(mysqli_query($connection, $sql), MYSQLI_ASSOC);
	
    return($result['value']);

}

//change a setting in the intSetting table
function writeIntSetting($data, $connection)
{

    $sql = "UPDATE intSettings SET value = '" . $data['value'] . "'
        WHERE name = '" . $data['name'] . "'";

    $result = mysqli_query($connection, $sql);

    return($result);

}

//This function will handle all pane and participant moves, adds, and updates. This replaces the paneUpdate function.
function participantUpdate($data, $connection)
{
	//error_log(json_encode($data));
    //accepts the following variables to allow for operations
    if (array_key_exists('conferenceTableId', $data)) {
        $conferenceTableId = $data['conferenceTableId'];
    }

    if (array_key_exists('participantTableId', $data)) {
        $participantTableId = $data['participantTableId'];
    }

    if (array_key_exists('loopParticipantName', $data)) {
        $loopParticipantName = $data['loopParticipantName'];
    }

    if (array_key_exists('pane', $data)) {
        $pane = $data['pane'];
    }

    if (array_key_exists('type', $data)) {
        $type = $data['type'];
    }

    if (array_key_exists('muteChannel', $data)) {
        $muteChannel = $data['muteChannel'];
    }

    if (array_key_exists('muteAction', $data)) {
        if ($data['muteAction']) {
            $muteAction = 1;
        } else {
            $muteAction = 0;
        }
    }

    if (array_key_exists('importantValue', $data)) {
        if ($data['importantValue']) {
            $importantValue = 1;
        } else {
            $importantValue = 0;
        }
    }

    if ($data['action'] === "moveParticipant") {

        //When a participant is moved, reflect the change in the DB right away so we don't have to wait for an enumerate
        $sql = "UPDATE participants SET conferenceTableId ='" . $conferenceTableId . "' WHERE id='" . $participantTableId . "'";

        $result = mysqli_query($connection, $sql);

    } elseif ($data['action'] === "muteParticipant") {

        //When a participant is moved, reflect the change in the DB right away so we don't have to wait for an enumerate
        $sql = "UPDATE participants SET " . $muteChannel . " =" . $muteAction . " WHERE id='" . $participantTableId . "'";

        $result = mysqli_query($connection, $sql);

    } elseif ($data['action'] === "importantParticipant") {
        //set all important db participants to 0 then we will update the new important participant
        $unsetImportantSQL = "UPDATE participants SET important=0 WHERE conferenceTableId='" . $conferenceTableId . "'";
        $resultUnset = mysqli_query($connection, $unsetImportantSQL);
		                
        //When a participant is moved, reflect the change in the DB right away so we don't have to wait for an enumerate
        $setImportantSQL = "UPDATE participants SET important=" . $importantValue . " WHERE id='" . $participantTableId . "'";
        $result = mysqli_query($connection, $setImportantSQL);
		
    } elseif ($data['action'] === "drop") {
        //When a participant is moved, reflect the change in the DB right away so we don't have to wait for an enumerate
        $dropSQL = "DELETE FROM participants WHERE id='" . $participantTableId . "'";
        $result = mysqli_query($connection, $dropSQL);

    }

    return $result;

}

//This function will handle Conference changes in the DB before a new conference enumerate occurs to true-up the data.
function conferenceUpdate($data, $connection)
{

    //accepts the following variables to allow for operations
    if (array_key_exists('conferenceTableId', $data)) {
        $conferenceTableId = $data['conferenceTableId'];
    }

    if (array_key_exists('layout', $data)) {
        $layout = $data['layout'];
    }

    if ($data['action'] === "changeLayout") {

        //When a participant is moved, reflect the change in the DB right away so we don't have to wait for an enumerate
        $sql = "UPDATE conferences SET layout=" . $layout . " WHERE id=" . $conferenceTableId;

        $result = mysqli_query($connection, $sql);
        $result = $sql;
    }

    return $result;

}

//This function will handle all pane and participant moves, adds, and updates. This replaces the paneUpdate function.
function panePlacementUpdate($data, $connection)
{

    //accepts the following variables to allow for operations
    if (array_key_exists('conferenceTableId', $data)) {
        $conferenceTableId = $data['conferenceTableId'];
    }

    if (array_key_exists('participantTableId', $data)) {
        $participantTableId = $data['participantTableId'];
    }

    if (array_key_exists('loopParticipantName', $data)) {
        $loopParticipantName = $data['loopParticipantName'];
    }

    if (array_key_exists('pane', $data)) {
        $pane = $data['pane'];
    }

    if ($data['action'] === "currentPane") {
        //current pane takes participantTableId and conferenceTableId returns the pane number as an integer
        $sql = "SELECT pane FROM panePlacement WHERE conferenceTableId = '" . $conferenceTableId . "' AND participantTableId = '" . $participantTableId . "'";

        $paneResult = mysqli_query($connection, $sql);
        $paneResultCount = mysqli_num_rows($paneResult);

        if ($paneResultCount == 1) {
            $paneResultArray = mysqli_fetch_array($paneResult, MYSQLI_ASSOC);
            $result = intval($paneResultArray['pane']);
        } else {
            $result = 0;
        }

    } elseif ($data['action'] === "setLoop") {

        //$findLoop['conferenceTableId'] = $sourceConferenceTableId;
        //$conferenceLoop = databaseQuery('findConferenceLoop', $findLoop);

        $sql = "UPDATE panePlacement SET loopParticipantName = '" . $loopParticipantName . "' WHERE conferenceTableId = '" . $conferenceTableId . "' AND participantTableId = '" . $participantTableId . "'";

        $result = mysqli_query($connection, $sql);

    } elseif ($data['action'] === "unsetLoop") {

        $sql = "UPDATE panePlacement SET loopParticipantName = NULL WHERE conferenceTableId = '" . $conferenceTableId. "' AND participantTableId = '" . $participantTableId . "'";

        $result = mysqli_query($connection, $sql);

    } elseif ($data['action'] === "findAvailablePane") {

        //findAvailablePane returns the pane number as an integer of the lowest available pane in a conference
        $sql = "SELECT pane FROM panePlacement WHERE conferenceTableId = '" . $conferenceTableId . "' ORDER BY pane ASC";

        $paneQuery = mysqli_query($connection, $sql);
        $paneQueryCount = mysqli_num_rows($paneQuery);
        $paneResult = mysqli_fetch_all($paneQuery, MYSQLI_ASSOC);

        $paneCounter = 1;

        //If we return any results from the SQL query, then go through all the results to find the lowest free pane
        if ($paneQueryCount > 0) {

            //Go through all the returned panes from the query above
            foreach ($paneResult as $pane) {
                //If the first pass of the foreach returns something other than 1, then assume pane 1 is available.
                if (intval($pane['pane']) !== $paneCounter) {
                    $result = $paneCounter;
                    break;
                }

                // increment the paneCounter
                $paneCounter++;

            }

            //if we went through all the returned panes and didn't set a result set it to the next available pane
            if (isset($result) === false && $paneCounter <= 20) {
                $result = $paneCounter;
            } elseif (isset($result)) {
                $result = $result;
            } else {
                $result = 0;
            }

        } else {
            //If no panes were returned from the query, we can assume the conference has no assigned panes and use the first pane
            $result = $paneCounter;
        }

    } elseif ($data['action'] === "addPaneEntry") {

        $sql = "INSERT INTO panePlacement (`pane`, `conferenceTableId`, `participantTableId`) VALUES ('" . $pane . "','" . $conferenceTableId . "','" . $participantTableId . "')";
		
		$result = mysqli_query($connection, $sql);

    } elseif ($data['action'] === "deletePaneEntry") {
        //delete takes the conferenceTableId and pane number and sets participantTableId, type, and loopParticipantName to NULL
        $sql = "DELETE FROM panePlacement WHERE conferenceTableId = '" . $conferenceTableId . "' AND pane = '" . $pane . "'";

        $result = mysqli_query($connection, $sql);

    } elseif ($data['action'] === "updatePane") {
        
        //Build and run a SQL query to see if there is already a participant in the pane you are moving the new participant
        $overwritePaneSQL = "SELECT id FROM panePlacement WHERE conferenceTableId = '" . $data['conferenceTableId'] . "' AND pane = '" . $data['pane'] . "'";
        $overwritePaneResults = mysqli_query($connection, $overwritePaneSQL);
		
		//error_log("updatePane overwrite sql: " . $overwritePaneSQL);
		//error_log("updatePane overwrite result: " . $overwritePaneResults);
		
        //Check if the SQL query returned a current pane to overwrite
        if (mysqli_num_rows($overwritePaneResults) == 0) {
            //If the pane is NOT assigned to a participant then insert a new record
            $sql = "INSERT INTO panePlacement (`pane`, `conferenceTableId`, `participantTableId`) VALUES ('" . $data['pane'] . "','" . $data['conferenceTableId'] . "','" . $data['participantTableId'] . "')";
        } else {
            $overwritePaneId = mysqli_fetch_array($overwritePaneResults, MYSQLI_ASSOC);

            //If an entry is found, then update it
            $sql = "UPDATE panePlacement SET participantTableId = '" . $data['participantTableId'] . "', loopParticipantId = NULL WHERE id = '" . $overwritePaneId['id'] . "'";
        }
		
		$result = mysqli_query($connection, $sql);
		//error_log("updatePane write sql: " . $sql);
		//error_log("updatePane write result: " . json_encode($result));

    } elseif ($data['action'] === "customLayoutPaneUpdate") {

        //for Special Grid function, this sets the new pane information for the participants
        $sql = "UPDATE panePlacement SET pane ='" . $pane . "' WHERE conferenceTableId = '" . $conferenceTableId . "' AND participantTableId = '" . $participantTableId . "'";

        $result = mysqli_query($connection, $sql);

        //$result = $sql;

    } elseif ($data['action'] === "clearPanePlacement") {
        //deletes all Pane Placement entries
        $sqlPane = "TRUNCATE TABLE panePlacement";
        $result = mysqli_query($connection, $sqlPane);

    }

    return $result;
}

function conferenceSavedLayout($data, $connection)
{
    $conferenceName = $data['conferenceName'];
    $conferenceDetail = databaseQuery('conferenceInfo', $conferenceName);

    if ($data['action'] === "get") {
        //Record the current layout of the conference before changing it to a custom view
        $sql = "SELECT * FROM conferences WHERE conferenceId = '" . $conferenceDetail['id'] . "'";
        $conferenceSavedLayout = mysqli_fetch_array(mysqli_query($connection, $sql), MYSQLI_ASSOC);

        $result = intval($conferenceSavedLayout['savedLayout']);

    } elseif ($data['action'] === "save") {

        //Retrieve the saved conference layout to reset the conference to the previously known layout
        $sql = "UPDATE conferences SET savedLayout = '" . $conferenceDetail['layout'] . "' WHERE conferenceId = '" . $conferenceDetail['id'] . "'";
        $conferenceSavedLayoutQuery = mysqli_query($connection, $sql);

        $result = true;
    }

    return($result);
}

function savedPane($data, $connection)
{
    //accepts the following variables to allow for operations
    if (array_key_exists('conferenceTableId', $data)) {
        $conferenceTableId = $data['conferenceTableId'];
    }

    if (array_key_exists('participantTableId', $data)) {
        $participantTableId = $data['participantTableId'];
    }

    if (array_key_exists('pane', $data)) {
        $pane = $data['pane'];
    }

    if ($data['action'] === "get") {
        //Record the current layout of the conference before changing it to a custom view
        $sql = "SELECT panePlacement.savedPane FROM panePlacement
            WHERE participantTableId='" . $participantTableId . "'
            AND conferenceTableId='" . $conferenceTableId . "'";
        $savedPane = mysqli_fetch_array(mysqli_query($connection, $sql), MYSQLI_ASSOC);
        $result = intval($savedPane['savedPane']);

    } elseif ($data['action'] === "save") {

        //Retrieve the saved conference layout to reset the conference to the previously known layout

        $savePaneSQL = "UPDATE panePlacement
            SET savedPane='" . $pane . "'
            WHERE participantTableId='" . $participantTableId . "'
            AND conferenceTableId='" . $conferenceTableId . "'";
        $savePane = mysqli_query($connection, $savePaneSQL);

        $result = $savePane;
    }

    return($result);

}
