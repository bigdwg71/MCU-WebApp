<?php

// in this var you will get the absolute file path of the current file
$current_file_path = dirname(__FILE__);
// with the next line we will include the 'somefile.php'
// which based in the upper directory to the current path
require_once($current_file_path . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'config.php');
//require_once($current_file_path . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'install.php');

//require_once 'admin/config.php';
//require_once 'admin/install.php';

$deviceQuery = new stdClass();

function participantNameCmp($aString, $bString)
{
    return strnatcasecmp($aString['displayName'], $bString['displayName']);
}

function mcuCommand($options, $commandSuffix, $command)
{
        global $mcuRpc;

        // We make the XML_RPC2_Client object (as the backend is not specified, XMLRPCEXT
        // will be used if available (full PHP else))
        $client = XML_RPC2_Client::create($mcuRpc, $options);

        //if (preg_match("/important/", implod("", $command))) error_log(implod("", $command));

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
              `participantTableId` int(11) NOT NULL,
              `loopParticipantId` int(11) DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8";
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
                ('codec1', 'Conference 1 Codec Number'),
                ('codec2', 'Conference 2 Codec Number'),
                ('codec3', 'Conference 3 Codec Number'),
                ('codec4', 'Conference 4 Codec Number'),
                ('codec5', 'Conference 5 Codec Number'),
                ('codec6', 'Conference 6 Codec Number'),
                ('codec7', 'Conference 7 Codec Number'),
                ('codec8', 'Conference 8 Codec Number'),
                ('codec9', 'Conference 9 Codec Number')";
            mysqli_query($connection, $sql);
        }

        $sql = "CREATE TABLE IF NOT EXISTS `conferences` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `conferenceName` varchar(31) NOT NULL,
            `conferenceId` int(31) NOT NULL,
            `layout` int(31) NOT NULL,
            `savedLayout` int(11),
            PRIMARY KEY (`id`),
            UNIQUE KEY `conferenceId` (`conferenceId`),
            UNIQUE KEY `conferenceName` (`conferenceName`))
            ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysqli_query($connection, $sql);

        $sql = "CREATE TABLE IF NOT EXISTS `participants` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `participantName` varchar(256) NOT NULL,
          `participantPreview` varchar(256) DEFAULT NULL,
          `displayName` varchar(31) NOT NULL,
          `audioRxMuted` tinyint(1) NOT NULL,
          `videoRxMuted` tinyint(1) NOT NULL,
          `audioTxMuted` tinyint(1) NOT NULL,
          `videoTxMuted` tinyint(1) NOT NULL,
          `participantProtocol` varchar(4) NOT NULL,
          `participantType` varchar(10) NOT NULL,
          `conferenceTableId` int(11) NOT NULL,
          `cpLayout` varchar(255) NOT NULL,
          `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`))
          ENGINE=InnoDB DEFAULT CHARSET=utf8";
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
                    ('showIsLive', 'false'),
                    ('waitingRoomCount', '0');";
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

    if ($action == 'findCurrentPane') {
        $result = findCurrentPane($data, $connection);
    } elseif ($action == 'updatePanePlacement') {
        $result = updatePanePlacement($data, $connection);
    } elseif ($action == 'readAllSettings') {
        $result = readAllSettings($connection);
    } elseif ($action == 'readSetting') {
        $result = readSetting($data, $connection);
    } elseif ($action == 'updateSettings') {
        $result = updateSettings($data, $connection);
    } elseif ($action == 'updateConferences') {
        $result = updateConferences($data, $connection);
    } elseif ($action == 'updateParticipants') {
        $result = updateParticipants($data, $connection);
    } elseif ($action == 'participantInfo') {
        $result = participantInfo($data, $connection);
    } elseif ($action == 'allParticipants') {
        $result = allParticipants($data, $connection);
    } elseif ($action == 'conferenceInfo') {
        $result = conferenceInfo($data, $connection);
    } elseif ($action == 'codecInfo') {
        $result = codecInfo($data, $connection);
    } elseif ($action == 'readIntSetting') {
        $result = readIntSetting($data, $connection);
    } elseif ($action == 'writeIntSetting') {
        $result = writeIntSetting($data, $connection);
    } elseif ($action == 'paneUpdate') {
        $result = paneUpdate($data, $connection);
    } elseif ($action == 'conferenceSavedLayout') {
        $result = conferenceSavedLayout($data, $connection);
    } elseif ($action == 'savedPane') {
        $result = savedPane($data, $connection);
    } else {
        $result = array('alert' => 'DB function not found!');
    }

    //mysqli_close($connection);

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

function refreshPage($mcuUsername, $mcuPassword, $type)
{
    $participantArray = array();
    $conferenceArray = array();

    //If this is the initial refresh then lets check the MCU for participant info
    //if ($type == 'first') {
        //Query MCU for participant info
        $participantArray = mcuParticipantEnumerate($mcuUsername, $mcuPassword);
   /* } else {
        $participantEnumerate = databaseQuery('allParticipants', '');
        $i = 0;
        if (isset($participantEnumerate)) {
            foreach ($participantEnumerate as $entry => $participantInstance) {
                if (is_array($participantInstance)) {
                    $participantArray[$i]['participantName'] = $participantInstance['participantName'];
                    $data['participantName'] = $participantInstance['participantName'];
                    $data['displayName'] = $participantInstance['displayName'];
                    $data['conferenceName'] = $participantInstance['conferenceName'];
                    $currentPane = databaseQuery('findCurrentPane', $data);
                    if ($currentPane['pane'] == null || $participantInstance['displayName'] == '_') {
                        $participantArray[$i]['pane'] = '';
                    } else {
                        $participantArray[$i]['pane'] = $currentPane['pane'];
                    }
                    $participantArray[$i]['participantPreview'] = $participantInstance['participantPreview'];
                    $participantArray[$i]['displayName'] = $participantInstance['displayName'];
                    $participantArray[$i]['audioRxMuted'] = $participantInstance['audioRxMuted'];
                    $participantArray[$i]['videoRxMuted'] = $participantInstance['videoRxMuted'];
                    $participantArray[$i]['audioTxMuted'] = $participantInstance['audioTxMuted'];
                    $participantArray[$i]['videoTxMuted'] = $participantInstance['videoTxMuted'];
                    $participantArray[$i]['participantProtocol'] = $participantInstance['participantProtocol'];
                    $participantArray[$i]['participantType'] = $participantInstance['participantType'];
                    $participantArray[$i]['conferenceName'] = $participantInstance['conferenceName'];
                    $participantArray[$i]['connectionUniqueId'] = $participantInstance['conferenceId'];
                    $participantArray[$i]['cpLayout'] = $participantInstance['cpLayout'];
                    $i++;
                }
            }
        }
    }
    */

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

                $j = 0;
                $currentPanePlacement = checkPanePlacement($conferenceName, $mcuUsername, $mcuPassword);
                foreach ($currentPanePlacement['panePlacement']['panes'] as $pane) {
                    $conferenceArray[$conferenceName]['panes']['pane'.$j] = $pane;
                    $j++;
                }
            }
        }
    }

    $confErrors = databaseQuery('updateConferences', $conferenceArray);

    if ($type == 'first') {
        $paneErrors = databaseQuery('updatePanePlacement', $conferenceArray);
    }

    // Sort the multidimensional array
    ksort($conferenceArray);
    usort($participantArray, "participantNameCmp");

    //$debugArray = array('conferenceArray' => $dbWork);
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
              'showIsLive' => $showIsLive,
              'debugArray' => $debugArray,
              'appVersion' => $appVersion,
              'deviceQuery' => $deviceQuery,
              'alert' =>  '')
    );
}

function findCurrentPane($data, $connection)
{
    $sql = "SELECT panePlacement.pane FROM panePlacement
    INNER JOIN conferences ON panePlacement.conferenceTableId = conferences.id
    INNER JOIN participants ON panePlacement.participantTableId = participants.id
    WHERE displayName='".$data['displayName']."'
    AND participantName='".$data['participantName']."'
    AND conferences.conferenceName='".$data['conferenceName']."'";

    $mysqlquery = mysqli_query($connection, $sql);
    //$pane = [];
    if ($mysqlquery) {
        /*
        while ($row = mysqli_fetch_array($mysqlquery)) {
            $pane += $row['pane'];
        }
        $result = $pane;
        */

        $result = mysqli_fetch_array($mysqlquery);

    } else {

        $result = array('alert' => '');

    }

    return($result);

}

function updatePanePlacement($data, $connection)
{
    $result = "";
    $paneRow = "";
    //Loop thorugh each conference in the conferences table
    foreach ($data as $conferences => $conferenceDetail) {
        $conferenceIdSQL = "SELECT id FROM conferences WHERE conferenceId = '".$conferenceDetail['uniqueId']."'";
        $conferenceIdQuery = mysqli_query($connection, $conferenceIdSQL);
        $conferenceTableId = mysqli_fetch_assoc($conferenceIdQuery);
        $conferenceTableId = $conferenceTableId['id'];

        //Get the layout of the conference
        $conferenceLayoutSQL = "SELECT layout FROM conferences WHERE conferenceId = '".$conferenceDetail['uniqueId']."'";
        $conferenceLayoutQuery = mysqli_query($connection, $conferenceLayoutSQL);
        $conferenceLayout = mysqli_fetch_assoc($conferenceLayoutQuery);
        $conferenceLayout = $conferenceLayout['layout'];

        //If the conference is not a grid, then don't record pane placement information
        //if ($conferenceLayout !== '1') {
            //Loop through each pane of the conference
            foreach ($conferenceDetail['panes'] as $paneNumber => $paneDetail) {
                //check if pane has a participant associated with it
                if (isset($paneDetail['participantName'])) {
                    //Get the ID of the participant in the pane from the participant table
                    $participantIdSQL = "SELECT id, displayName FROM participants WHERE participantName = '".$paneDetail['participantName']."'";
                    $participantIdQuery = mysqli_query($connection, $participantIdSQL);
                    $row = mysqli_fetch_assoc($participantIdQuery);
                    $participantTableId = $row['id'];

                    //Check if the participant in question is a codec or Loop, if its either, do not add it to the panePlacement table
                    if ($row['displayName'] != "_" && $row['displayName'] != "__") {
                        //Get the ID of the panePlacement entry in the database
                        $paneParticipantIdSQL = "SELECT id FROM panePlacement WHERE pane = '".$paneDetail['index']."' AND conferenceTableId = '".$conferenceTableId."'";

                        $panePlacementIdQuery = mysqli_query($connection, $paneParticipantIdSQL);
                        $paneRow = mysqli_fetch_assoc($panePlacementIdQuery);
                        $panePlacementId = $paneRow['id'];
                        //check if we have a pane in the database already, if so update it, else insert it
                        if (mysqli_num_rows($panePlacementIdQuery) > 0) {
                            $sql = "UPDATE panePlacement SET conferenceTableId='".$conferenceTableId."',
                            participantTableId='".$participantTableId."',
                            pane='".$paneDetail['index']."'
                            loopParticipantId=''
                            WHERE id='".$panePlacementId."'";
                            $mysqlquery = mysqli_query($connection, $sql);
                        } else {
                            //This SQL state inserts a row in PanePlacement ONLY if the pane and conference ID are unique. IF they aren't unique, then we don't add an entry. This prevents the loops that holding panes for participants from adding duplicate entries to the table.
                            $sql = "INSERT INTO panePlacement(pane, conferenceTableId, participantTableId)
                                    SELECT ".$paneDetail['index'].", ".$conferenceTableId.", ".$participantTableId."
                                    FROM dual
                                    WHERE NOT EXISTS (
                                        SELECT *
                                        FROM panePlacement
                                        WHERE pane = ".$paneDetail['index']." AND conferenceTableId = ".$conferenceTableId.")";
                            $mysqlquery = mysqli_query($connection, $sql);
                        }

                    }

                } else {

                        $loopParticiantSQL = "SELECT loopParticiantId FROM panePlacement WHERE pane=".$paneDetail['index']." AND conferenceTableId = '".$conferenceTableId."'";
                        $loopParticiantQuery = mysqli_query($connection, $loopParticiantSQL);
                        $loopParticiant = $loopParticiantQuery;

                    if ($loopParticiant == false) {
                        //Didn't find a participant associated with the pane, so delete the db entry
                        $sql = "DELETE FROM panePlacement WHERE pane=".$paneDetail['index']." AND conferenceTableId = '".$conferenceTableId."'";
                        $mysqlquery = mysqli_query($connection, $sql);
                    }

                }
            }
        //}
    }
    return($paneRow);
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
        $sql = "UPDATE settings SET value='".mysqli_real_escape_string($connection, $setting['value'])."'
        WHERE name='".mysqli_real_escape_string($connection, $setting['name'])."'";
        $mysqlquery = mysqli_query($connection, $sql);
        if (!$mysqlquery) {
            $result = array('alert' => 'Could not updateSettings: '.mysqli_error($connection));
        } else {
            $result = true;
        }
    }
    return($result);
}

function updateConferences($data, $connection)
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
            $result = array('alert' => 'Could not updateConferences, mySQL Query failed!');
        } else {
            $result = true;
        }

        $existingConferences .=  mysqli_real_escape_string($connection, $row['uniqueId']).",";

    }

    if ($existingConferences != "") {
        $existingConferences = rtrim($existingConferences, ",");
    }

    //If there are no conferences on the MCU, then truncate/clear the table
    if ($existingConferences === "") {
        $sql = "TRUNCATE TABLE conferences";
    } else {
        $sql = "DELETE FROM conferences WHERE conferenceId NOT IN (".$existingConferences.")";
    }

    $mysqlquery = mysqli_query($connection, $sql);
    return($result);
}

function updateParticipants($data, $connection)
{
    //$result = "";
    $existingParticipants = "";
    $updateParticipant = "";
    $conferenceTableId = "";
    $insertParticipant = "EMPTY";

    foreach ($data as $rows => $row) {
        //see if this particicpant is in the participants table
        $sql = "SELECT * FROM participants
        WHERE participantName = '".mysqli_real_escape_string($connection, $row['participantName'])."'
        AND displayName = '".mysqli_real_escape_string($connection, $row['displayName'])."'";
        $findParticipant = mysqli_query($connection, $sql);
        //grab the conferenceTableId field to insert into the participant table
        $sqlConference = "SELECT id FROM conferences WHERE conferenceName='"
            .mysqli_real_escape_string($connection, $row['conferenceName'])."'";
        $theConferenceName = $row['conferenceName'];
        $conferenceTableId = mysqli_query($connection, $sqlConference);
        if ($conferenceTableId) {
            $rowConference = mysqli_fetch_array($conferenceTableId);
            $conferenceId = $rowConference['id'];
        }

        //if this participant isn't in the participants table, insert it
//if this participant isn't in the participants table, insert it
        if ($findParticipant->num_rows == 0) {
            $sqlInsert = "INSERT INTO participants
            (`participantName`,
            `participantPreview`,
            `displayName`,
            `audioRxMuted`,
            `videoRxMuted`,
            `audioTxMuted`,
            `videoTxMuted`,
            `participantProtocol`,
            `participantType`,
            `conferenceTableId`,
            `cpLayout`)
            VALUES ('".mysqli_real_escape_string($connection, $row['participantName'])."',
            '".mysqli_real_escape_string($connection, $row['participantPreview'])."',
            '".mysqli_real_escape_string($connection, $row['displayName'])."',
            '".($row['audioRxMuted']?1:0)."',
            '".($row['videoRxMuted']?1:0)."',
            '".($row['audioTxMuted']?1:0)."',
            '".($row['videoTxMuted']?1:0)."',
            '".mysqli_real_escape_string($connection, $row['participantProtocol'])."',
            '".mysqli_real_escape_string($connection, $row['participantType'])."',
            '".mysqli_real_escape_string($connection, $conferenceId)."',
            '".mysqli_real_escape_string($connection, $row['cpLayout'])."')";
            $insertParticipant = mysqli_query($connection, $sqlInsert);
        } else {
            $sql = "UPDATE participants SET
            `participantPreview` = '".mysqli_real_escape_string($connection, $row['participantPreview'])."',
            `displayName` = '".mysqli_real_escape_string($connection, $row['displayName'])."',
            `audioRxMuted` = ".($row['audioRxMuted']?1:0).",
            `videoRxMuted` = ".($row['videoRxMuted']?1:0).",
            `audioTxMuted` = ".($row['audioTxMuted']?1:0).",
            `videoTxMuted` = ".($row['videoTxMuted']?1:0).",
            `participantProtocol` = '".mysqli_real_escape_string($connection, $row['participantProtocol'])."',
            `participantType` = '".mysqli_real_escape_string($connection, $row['participantType'])."',
            `conferenceTableId` = ".mysqli_real_escape_string($connection, $conferenceId).",
            `cpLayout` = ".mysqli_real_escape_string($connection, $row['cpLayout'])."
            WHERE `participantName` = ".mysqli_real_escape_string($connection, $row['participantName']);
            $updateParticipant = mysqli_query($connection, $sql);
        }

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

        $resetPane = [];

        //For each returned row from the SQL query, get the conference name and pane index and reset their pane
        foreach ($findPane as $paneRow) {

            $sqlConferenceId = "SELECT conferenceName FROM conferences WHERE id ='".$paneRow['conferenceTableId']."'";
            $conferenceResult = mysqli_fetch_assoc(mysqli_query($connection, $sqlConferenceId));

            $conferenceName = $conferenceResult['conferenceName'];
            $paneNumber = intval($paneRow['pane']);

            $mcuUsername = databaseQuery('readSetting', 'mcuUsername');
            $mcuPassword = databaseQuery('readSetting', 'mcuPassword');

            $resetPane = mcuCommand(
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
        $result = array('alert' => 'Could not updateParticipants: '.mysqli_error($connection).' Query '.$insertParticipant);
    } else {
        $result = '';
    }
return($result);
}

//takes a conference name and returns all information in the row about that conference
function conferenceInfo($data, $connection)
{

    $sql = "SELECT * FROM conferences
        WHERE conferenceName='" . $data . "'";

    $conference = mysqli_fetch_array(mysqli_query($connection, $sql));

    return($conference);

}

//takes displayName and conferenceID and returns all information in the row about that participant
function participantInfo($data, $connection)
{

    $sql = "SELECT * FROM participants
        WHERE displayName = '" . $data['displayName'] . "'
        AND conferenceTableId = '" . $data['conferenceId'] . "'";

    $participant = mysqli_fetch_array(mysqli_query($connection, $sql));

    return($participant);

}

//finds all participants
function allParticipants($data, $connection)
{
    $selectParticipantsSql = "SELECT * FROM participants INNER JOIN conferences ON participants.conferenceTableId = conferences.id";
    //$selectParticipantsSql = "SELECT * FROM participants";
    $participantEnumerate = mysqli_fetch_all(mysqli_query($connection, $selectParticipantsSql), MYSQLI_ASSOC);

    return($participantEnumerate);
}

//takes a conference name and returns the participantName(ID) of the codec in that conference
function codecInfo($conferenceName)
{
    $conference = databaseQuery('conferenceInfo', $conferenceName);
    $conferenceID = $conference['conferenceId'];

    $partData['conferenceId'] = $conferenceID;
    $partData['displayName'] = "__";
    $codec = databaseQuery('participantInfo', $partData);

    return($codec);

}

//reads the requested setting from the intSetting table
function readIntSetting($data, $connection)
{

    $sql = "SELECT value FROM intSettings
        WHERE name = '" . $data['name'] . "'";

    $result = mysqli_fetch_array(mysqli_query($connection, $sql));

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

function paneUpdate($data, $connection)
{

    if ($data['action'] === "addLoop") {
        //addLoop requires loopParticipantId, pane, conferenceTableId, and participantTableId and adds a Loop's participantName to the panePlacement table
        $sql = "UPDATE panePlacement SET loopParticipantId = '" . $data['loopParticipantId'] . "' WHERE pane = '" . $data['pane'] . "' AND conferenceTableId = '" . $data['conferenceTableId'] . "' AND participantTableId = '" . $data['participantTableId'] . "'";

        $result = mysqli_query($connection, $sql);

    } elseif ($data['action'] === "removeLoop") {
        //removeLoop requires pane and conferenceTableId and removes a Loop's participantName from an entry in the panePlacement table
        $sql = "UPDATE panePlacement SET loopParticipantId = NULL WHERE pane = '" . $data['pane'] . "' AND conferenceTableId = '" . $data['conferenceTableId'] . "'";

        $result = mysqli_query($connection, $sql);

    } elseif ($data['action'] === "findLoop") {
        //findLoop requires pane, conferenceTableId, and participantTableId and returns the loopParticipantId (participantName) from the panePlacement table
        $sql = "SELECT loopParticipantId FROM panePlacement WHERE pane = '" . $data['pane'] . "' AND conferenceTableId = '" . $data['conferenceTableId'] . "' AND participantTableId = '" . $data['participantTableId'] . "'";

        $result = mysqli_query($connection, $sql);

    }  elseif ($data['action'] === "findPane") {
        //findPane takes participantTableId and conferenceTableId returns the pane number
        $sql = "SELECT pane FROM panePlacement WHERE conferenceTableId = '" . $data['conferenceTableId'] . "' AND participantTableId = '" . $data['participantTableId'] . "'";

        $result = mysqli_query($connection, $sql);

    } elseif ($data['action'] === "add") {
        //add requires pane, conferenceTableId, and participantTableId and adds an entire entry into the panePlacementTable when a pane is assigned to a participant
        $sql = "INSERT INTO panePlacement (`pane`, `conferenceTableId`, `participantTableId`) VALUES ('" . $data['pane'] . "','" . $data['conferenceTableId'] . "','" . $data['participantTableId'] . "')";

        $result = mysqli_query($connection, $sql);

    } elseif ($data['action'] === "update") {
        //update requires pane, conferenceTableId, and participantTableId and modifies an entry into the panePlacementTable when a participant is moved and pane assigned

        //Check if the moving participant belongs to a "hidden" pane outside the scope of the current layout
        $findCurrentPane['action'] = 'findPane';
        $findCurrentPane['conferenceTableId'] = $data['conferenceTableId'];
        $findCurrentPane['participantTableId'] = $data['participantTableId'];
        $findCurrentPaneResult = databaseQuery('paneUpdate', $findCurrentPane);

        //Check if the SQL query returned any entries for the moving participant that need to be deleted
        
        if (mysqli_num_rows($findCurrentPaneResult) > 0) {

            $currentPanes = mysqli_fetch_all($findCurrentPaneResult, MYSQLI_ASSOC);

            foreach($currentPanes as $paneInstance) {
                $deletePane['action'] = "delete";
                $deletePane['pane'] = $paneInstance['pane'];
                $deletePane['conferenceTableId'] = $data['conferenceTableId'];
                $deletePaneResult = databaseQuery('paneUpdate', $deletePane);
            }
        }

        //Build and run a SQL query to see if there is already a participant in the pane you are moving the new participant
        $overwritePaneSQL = "SELECT id FROM panePlacement WHERE conferenceTableId = '" . $data['conferenceTableId'] . "' AND pane = '" . $data['pane'] . "'";
        $overwritePaneResults = mysqli_query($connection, $overwritePaneSQL);

        //Check if the SQL query returned a current pane to overwrite
        if (mysqli_num_rows($overwritePaneResults) == 0) {
            //If the pane is NOT assigned to a participant then insert a new record
            $sql = "INSERT INTO panePlacement (`pane`, `conferenceTableId`, `participantTableId`) VALUES ('" . $data['pane'] . "','" . $data['conferenceTableId'] . "','" . $data['participantTableId'] . "')";
        } else {
            $overwritePaneId = mysqli_fetch_array($overwritePaneResults);

            //If an entry is found, then update it
            $sql = "UPDATE panePlacement SET participantTableId = '" . $data['participantTableId'] . "', loopParticipantId = NULL WHERE id = '" . $overwritePaneId['id'] . "'";
        }

        $result = mysqli_query($connection, $sql);

    } elseif ($data['action'] === "customLayoutPaneUpdate") {
        
        //If an entry is found, then update it
        $sql = "UPDATE panePlacement SET pane ='" . $data['pane'] . "' WHERE conferenceTableId = '" . $data['conferenceTableId'] . "' AND participantTableId = '" . $data['participantTableId'] . "'";

        $result = mysqli_query($connection, $sql);

    } elseif ($data['action'] === "delete") {
        //add requires pane and conferenceTableId and removes an entire entry from the panePlacementTable
        $sql = "DELETE FROM panePlacement WHERE conferenceTableId = '" . $data['conferenceTableId'] . "' AND pane = '" . $data['pane'] . "'";

        $result = mysqli_query($connection, $sql);

    } elseif ($data['action'] === "clearPanePlacement") {
        //deletes all Pane Placement entries
        $sqlPane = "TRUNCATE TABLE panePlacement";
        $result = mysqli_query($connection, $sqlPane);

    } elseif ($data['action'] === "findEmpty") {
        //findEmpty requries conferenceTableId and returns the lowest available pane in that cofnerence
        $sql = "SELECT pane FROM panePlacement WHERE conferenceTableId = '" . $data['conferenceTableId'] . "' ORDER BY pane ASC";

        $queryResult = mysqli_fetch_all(mysqli_query($connection, $sql), MYSQLI_ASSOC);
        //$queryResult = mysqli_query($connection, $sql);
        $paneCounter = 1;

        //If we return any results from the SQL query, then go through all the results to find the lowest free pane
        if (count($queryResult) > 0) {

            //Go through all the returned panes from the query above
            foreach ($queryResult as $paneNumber) {
                //If the first pass of the foreach returns something other than 1, then assume pane 1 is available.
                if (intval($paneNumber['pane']) !== $paneCounter) {
                    $result = $paneCounter;
                    break;
                }

                // increment the paneCounter
                $paneCounter++;

            }

            //if we went through all the returned panes and didn't set a result set it to the next available pane
            if (isset($result) === false && $paneCounter <= 20) {
                $result = $paneCounter;
            } else if (isset($result)) {
                $result = $result;
            } else {
                $result = 0;
            }

        } else {
            //If no panes were returned from the query, we can assume the conference has no assigned panes and use the first pane
            $result = $paneCounter;
        }

    }

    return($result);

}

function conferenceSavedLayout($data, $connection)
{
	$conferenceName = $data['conferenceName'];
	$conferenceDetail = databaseQuery('conferenceInfo', $conferenceName);
	
    if ($data['action'] === "get") {
        //Record the current layout of the conference before changing it to a custom view
        $conferenceSavedLayoutSQL = "SELECT * FROM conferences WHERE conferenceId = '" . $conferenceDetail['id'] . "'";
        $conferenceSavedLayout = mysqli_fetch_array(mysqli_query($connection, $conferenceSavedLayoutSQL));
                		
		$result = intval($conferenceSavedLayout['savedLayout']);
		
    } elseif ($data['action'] === "save") {		
		
        //Retrieve the saved conference layout to reset the conference to the previously known layout
        $conferenceSavedLayoutSQL = "UPDATE conferences SET savedLayout = '" . $conferenceDetail['layout'] . "' WHERE conferenceId = '" . $conferenceDetail['id'] . "'";
        $conferenceSavedLayoutQuery = mysqli_query($connection, $conferenceSavedLayoutSQL);
                		
		$result = true;
    }
	
	return($result);
}

function savedPane($data, $connection)
{
    
    if ($data['action'] === "get") {
        //Record the current layout of the conference before changing it to a custom view
        $savedPaneSQL = "SELECT panePlacement.savedPane FROM panePlacement
            INNER JOIN conferences ON panePlacement.conferenceTableId = conferences.id
            INNER JOIN participants ON panePlacement.participantTableId = participants.id
            WHERE displayName='".$data['displayName']."'
            AND participantName='".$data['participantName']."'
            AND conferences.conferenceName='".$data['conferenceName']."'";
        $savedPane = mysqli_fetch_array(mysqli_query($connection, $savedPaneSQL));
                		
		$result = intval($savedPane['savedPane']);
		
    } elseif ($data['action'] === "save") {		
		
        //Retrieve the saved conference layout to reset the conference to the previously known layout
        
        $savePaneSQL = "UPDATE panePlacement
            INNER JOIN conferences ON panePlacement.conferenceTableId = conferences.id
            INNER JOIN participants ON panePlacement.participantTableId = participants.id
            SET panePlacement.savedPane='".$data['pane']."'
            WHERE displayName='".$data['displayName']."'
            AND participantName='".$data['participantName']."'
            AND conferences.conferenceName='".$data['conferenceName']."'";
        $savePane = mysqli_query($connection, $savePaneSQL);
                		
		$result = $savePane;
    }
    
    return($result);

}


function mcuParticipantEnumerate($mcuUsername, $mcuPassword)
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
    $waitingRoomCount = 0;
    $waitingRoom = databaseQuery('readSetting', 'waitingRoom');

	if (isset($participantEnumerate['participants'])) {
        foreach ($participantEnumerate['participants'] as $entry => $participantInstance) {
            if (is_array($participantInstance)) {
                $participantArray[$i]['participantName'] = $participantInstance['participantName'];
                $data['participantName'] = $participantInstance['participantName'];
                $data['displayName'] = addslashes($participantInstance['currentState']['displayName']);
                $data['conferenceName'] = $participantInstance['conferenceName'];
                $currentPane = databaseQuery('findCurrentPane', $data);
                if ($currentPane['pane'] == null || $participantInstance['currentState']['displayName'] == '_') {
                    $participantArray[$i]['pane'] = '';
                } else {
                    $participantArray[$i]['pane'] = $currentPane['pane'];
                }
                                
                $participantArray[$i]['participantPreview'] = $participantInstance['currentState']['previewURL'];
                $participantArray[$i]['displayName'] = $participantInstance['currentState']['displayName'];
                $participantArray[$i]['audioRxMuted'] = $participantInstance['currentState']['audioRxMuted'];
                $participantArray[$i]['videoRxMuted'] = $participantInstance['currentState']['videoRxMuted'];
                $participantArray[$i]['audioTxMuted'] = $participantInstance['currentState']['audioTxMuted'];
                $participantArray[$i]['videoTxMuted'] = $participantInstance['currentState']['videoTxMuted'];
                $participantArray[$i]['participantProtocol'] = $participantInstance['participantProtocol'];
                $participantArray[$i]['participantType'] = $participantInstance['participantType'];
                $participantArray[$i]['conferenceName'] = $participantInstance['conferenceName'];
                $participantArray[$i]['connectionUniqueId'] = $participantInstance['connectionUniqueId'];
                
                
                if (array_key_exists('currentLayout',$participantInstance['currentState'])) {
                    $participantArray[$i]['cpLayout'] = $participantInstance['currentState']['currentLayout'];
                } else {
                    $participantArray[$i]['cpLayout'] = '';
                }
                
                $participantArray[$i]['important'] = $participantInstance['currentState']['important'];
                $i++;

                //track the number of participants in the waiting room to set the appropriate layout of the waiting room
                //if ($participantInstance['conferenceName'] == $waitingRoom && $participantInstance['currentState']['displayName'] != '__') {
                if ($participantInstance['conferenceName'] == $waitingRoom) {
                    $waitingRoomCount++;
                }
            }
        }
    }

    //Get current waitingRoomCount from DB
    $queryRead['name'] = 'waitingRoomCount';
    $dbWaitingRoomCount = filter_var(databaseQuery('readIntSetting', $queryRead), FILTER_VALIDATE_INT);

    if ($waitingRoomCount != $dbWaitingRoomCount) {

        //write waitingRoomCount to DB for retrival later
        $queryWrite['name'] = 'waitingRoomCount';
        $queryWrite['value'] = $waitingRoomCount;
        $result = databaseQuery('writeIntSetting', $queryWrite);

        //since they don't match, now we need to determine if we should change the layout
        if ($waitingRoomCount >= 0 && $waitingRoomCount <= 2) {
            $newWaitingRoomLayout = 1;
        } elseif ($waitingRoomCount >= 3 && $waitingRoomCount <= 5) {
            $newWaitingRoomLayout = 2;
        } elseif ($waitingRoomCount == 6 || $waitingRoomCount == 7) {
            $newWaitingRoomLayout = 8;
        } elseif ($waitingRoomCount == 8 || $waitingRoomCount == 9) {
            $newWaitingRoomLayout = 53;
        } elseif ($waitingRoomCount == 10) {
            $newWaitingRoomLayout = 3;
        } elseif ($waitingRoomCount >= 11 && $waitingRoomCount <= 13) {
            $newWaitingRoomLayout = 9;
        } elseif ($waitingRoomCount >= 14 && $waitingRoomCount <= 17) {
            $newWaitingRoomLayout = 4;
        } elseif ($waitingRoomCount >= 18) {
            $newWaitingRoomLayout = 43;
        }

        $result = mcuCommand(
            array('prefix' => 'conference.'),
            'modify',
            array('authenticationUser' => $mcuUsername,
                  'authenticationPassword' => $mcuPassword,
                  'conferenceName' => $waitingRoom,
                  'customLayoutEnabled' => true,
                  'customLayout' => $newWaitingRoomLayout,
                  'newParticipantsCustomLayout' => true,
                  'setAllParticipantsToCustomLayout' => true)
        );
    }
	
    //Write participant info to database
    databaseQuery('updateParticipants', $participantArray);
    return($participantArray);

}
