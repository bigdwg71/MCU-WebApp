<?php
//Refresher.php Variables:
require_once 'XML/RPC2/Client.php';
require_once 'functions.php';

//This is a dummy post that is sent by the javascript in a loop,
//we just have this here so this script can deal with other button presses too.
if (isset($_POST['action']) && $_POST['action'] == 'refresh') {
    refreshPage($mcuUsername, $mcuPassword, $_POST['type']);
} elseif (isset($_POST['action']) && $_POST['action'] == 'transfer') {

    //Passes variables scrubbedParticipantList, sourceConference, sourceType, destinationConference, destType

    $checkLoop = null;
    $checkMove = null;
    $checkDrop = null;
    $checkAdd = null;
    $checkImportant = null;

    $destConference = $_POST['destinationConference'];
    $sourceConference = $_POST['sourceConference'];
    $destType = $_POST['destType'];
    $sourceType = $_POST['sourceType'];

    $participantList = $_POST['scrubbedParticipantList'];
    
    //Performing some operations outside of the loop to reduce DB queries

    //Get conference info of the source conference
    $sourceConferenceInfo = databaseQuery('conferenceInfo', $sourceConference);

    //Collect information about the destination conference
    $destConferenceInfo = databaseQuery('conferenceInfo', $destConference);

    //Set conferenceTableId for both source and destination conferences
    $sourceConferenceTableId = $sourceConferenceInfo['id'];
    $destConferenceTableId = $destConferenceInfo['id'];

    //Get particpant info of the existing loop in the conference for use later in the function
    $existingLoop = "";
    $findLoop['displayName'] = "_";
    $findLoop['conferenceId'] = $sourceConferenceTableId;
    $existingLoop = databaseQuery('participantInfo', $findLoop);

    //If there is no loop in the conference, then add one here
    if ($existingLoop['participantName'] == "" && $sourceType != "waiting") {

        //Add loop to grid conference
        $addNewLoop = mcuCommand(
            array('prefix' => 'participant.'),
            'add',
            array('authenticationUser' => $mcuUsername,
                  'authenticationPassword' => $mcuPassword,
                  'conferenceName' => $sourceConference,
                  'participantProtocol' => 'sip',
                  'participantName' => 'WOA - Loop',
                  'participantType' => 'ad_hoc',
                  'address' => $loopURI,
                  'displayNameOverrideStatus' => true,
                  'displayNameOverrideValue' => '_',
                  'audioRxMuted' => true,
                  'addResponse' => true)
        );

        //Use the response to populate the existingLoop variable to eliminate issues with adding loop and waiting for enumerate
        $existingLoop = $addNewLoop['participant'];
    }

    //For each participant in the scrubbedParticipantList variable, we will transfer with all associated actions (one or many)
    foreach ($participantList as $participant) {

        //Set participant specific variables
        $displayName = $participant['displayName'];
        $participantName = $participant['participantName'];
        $participantProtocol = $participant['participantProtocol'];
        $participantType = $participant['participantType'];

        //Get Participant info of the moving participant to update the Pane Placement database
        $findPaticipant['displayName'] = addslashes($displayName);
        $findPaticipant['conferenceId'] = $sourceConferenceInfo['id'];
        $participantInfo = databaseQuery('participantInfo', $findPaticipant);

        //Set variables for SQL queries in following actions
        $participantTableId = $participantInfo['id'];

        $participantPaneTarget = 0;

        if ($sourceType == "grid") {

            //find if the participant has been assigned a pane in their source conference to add a loop
            $sourcePane['action'] = 'findPane';
            $sourcePane['conferenceTableId'] = $sourceConferenceTableId;
            $sourcePane['participantTableId'] = $participantTableId;
            $sourcePaneResult = mysqli_fetch_array(databaseQuery('paneUpdate', $sourcePane));

            if (isset($sourcePaneResult['pane'])) {
                $loopPaneTarget = intval($sourcePaneResult['pane']);
            }

            if (isset($loopPaneTarget)) {
                //Build information for paneUpdate in the panePlacement database
                $addLoop['action'] = "addLoop";
                $addLoop['pane'] = $loopPaneTarget;
                $addLoop['conferenceTableId'] = $sourceConferenceTableId;
                $addLoop['participantTableId'] = $participantTableId;
                $addLoop['loopParticipantId'] = $existingLoop['participantName'];
                $addLoopResult = databaseQuery('paneUpdate', $addLoop);

                //set the existing loop to take participant's old pane
                $setLoopPane = mcuCommand(
                    array('prefix' => 'conference.paneplacement.'),
                    'modify',
                    array('authenticationUser' => $mcuUsername,
                        'authenticationPassword' => $mcuPassword,
                        'conferenceName' => $sourceConference,
                        'enabled' => true,
                        'panes' => array(['index' => $loopPaneTarget,
                                          'type' => 'participant',
                                          'participantName' => $existingLoop['participantName'],
                                          'participantProtocol' => $existingLoop['participantProtocol'],
                                          'participantType' => $existingLoop['participantType']]))
                );

                if ($setLoopPane['panesModified'] != 1) {
                    $removeLoop['action'] = "removeLoop";
                    $removeLoop['pane'] = $loopPaneTarget;
                    $removeLoop['conferenceTableId'] = $sourceConferenceTableId;
                    $removeLoopResult = databaseQuery('paneUpdate', $removeLoop);
                    //$removeTest = 'I removed the bloody loop!';
                }

            }
        }

        if ($destType == "grid") {
            //check if paneplacement has entry saved
            //set variables for use in DB query
            $destPane['action'] = 'findPane';
            $destPane['conferenceTableId'] = $destConferenceTableId;
            $destPane['participantTableId'] = $participantTableId;

            //Check if the participant has an existing DB entry so we can put them back in the proper pane
            $destPaneResult = mysqli_fetch_array(databaseQuery('paneUpdate', $destPane));

            //If we receive a result from the DB, then set it as the target pane
            if (isset($destPaneResult['pane'])) {
                $participantPaneTarget = intval($destPaneResult['pane']);

                //Build information necesary to remove loopParticipantId from panePlacement Table
                $removeLoop['action'] = "removeLoop";
                $removeLoop['pane'] = $participantPaneTarget;
                $removeLoop['conferenceTableId'] = $destConferenceTableId;
                $removeLoopResult = databaseQuery('paneUpdate', $removeLoop);

            } else {
                //If there is no pane placement entry for the participant
                //in the destination conference, we will find the first available pane and use it
                $findEmpty['action'] = "findEmpty";
                $findEmpty['conferenceTableId'] = $destConferenceTableId;
                $findEmptyResult = databaseQuery('paneUpdate', $findEmpty);

                if ($findEmptyResult != 0) {
                    $participantPaneTarget = intval($findEmptyResult);

                    //Now we need to write the new pane ownership information to the panePlacement table
                    //We may want to move this until after the actual MCU commands, but in my testing, that lead to multiple assignments to the same pane in the DB
                    $assignPane['action'] = "add";
                    $assignPane['pane'] = $participantPaneTarget;
                    $assignPane['conferenceTableId'] = $destConferenceTableId;
                    $assignPane['participantTableId'] = $participantTableId;
                    $assignPaneResult = databaseQuery('paneUpdate', $assignPane);
                }
            }

            //move participant
            $checkMove = mcuCommand(
                array('prefix' => 'participant.'),
                'move',
                array('authenticationUser' => $mcuUsername,
                      'authenticationPassword' => $mcuPassword,
                      'conferenceName' => $sourceConference,
                      'participantName' => $participantName,
                      'participantProtocol' => $participantProtocol,
                      'participantType' => $participantType,
                      'newConferenceName' => $destConference)
            );

            if ($participantPaneTarget > 0 && $participantPaneTarget <= 20) {
                //Set participant to the appropriate pane
                $setParticipantPane = mcuCommand(
                    array('prefix' => 'conference.paneplacement.'),
                    'modify',
                    array('authenticationUser' => $mcuUsername,
                          'authenticationPassword' => $mcuPassword,
                          'conferenceName' => $destConference,
                          'enabled' => true,
                          'panes' => array(['index' => $participantPaneTarget,
                                            'type' => 'participant',
                                            'participantName' => $participantName,
                                            'participantProtocol' => $participantProtocol,
                                            'participantType' => $participantType]))
                );

                if ($setParticipantPane['panesModified'] != 1) {
                    $removeLoop['action'] = "removeLoop";
                    $removeLoop['pane'] = $participantPaneTarget;
                    $removeLoop['conferenceTableId'] = $destConferenceTableId;
                    $removeLoopResult = databaseQuery('paneUpdate', $removeLoop);
                }

            }

            $query['name'] = 'showIsLive';
            $showIsLive = filter_var(databaseQuery('readIntSetting', $query), FILTER_VALIDATE_BOOLEAN);

            if ($showIsLive == false) {

                $destConferenceLayout = $destConferenceInfo['layout'];

                //if the show is not taping and in pre-show, then assign the current conference layout to let pariticpants see each other
                $cpLayout = 'layout' . $destConferenceLayout;

                //Set the layout for the participant that has been moved
                $setCPLayout = mcuCommand(
                    array('prefix' => 'participant.'),
                    'modify',
                    array('authenticationUser' => $mcuUsername,
                          'authenticationPassword' => $mcuPassword,
                          'conferenceName' => $destConference,
                          'participantName' => $participantName,
                          'participantProtocol' => $participantProtocol,
                          'participantType' => $participantType,
                          'operationScope' => 'activeState',
                          'cpLayout' => 'conferenceCustom',
                          'focusType' => 'voiceActivated')
                );

            } else {

                //if the show is live/taping, then set a single pane layout for participant
                $cpLayout = 'layout1';
				$codec = codecInfo($destConference);

                //Set the layout for the participant
				$setCPLayout = mcuCommand(
                        array('prefix' => 'participant.'),
                        'modify',
                        array('authenticationUser' => $mcuUsername,
                          'authenticationPassword' => $mcuPassword,
                          'conferenceName' => $destConference,
                          'participantName' => $participantName,
                          'participantProtocol' => $participantProtocol,
                          'participantType' => $participantType,
                              'operationScope' => 'activeState',
                              'cpLayout' => $cpLayout,
                              'focusType' => 'participant',
							  'focusParticipant' =>
									array('participantName' => $codec['participantName'],
									'participantProtocol' => $codec['participantProtocol'],
									'participantType' => $codec['participantType'])
						)
					);
            }

        } elseif ($destType == "focus") {
            //move participant
            $checkMove = mcuCommand(
                array('prefix' => 'participant.'),
                'move',
                array('authenticationUser' => $mcuUsername,
                      'authenticationPassword' => $mcuPassword,
                      'conferenceName' => $sourceConference,
                      'participantName' => $participantName,
                      'participantProtocol' => $participantProtocol,
                      'participantType' => $participantType,
                      'newConferenceName' => $destConference)
            );
            //set moved participant to important
            $checkImportant = mcuCommand(
                array('prefix' => 'participant.'),
                'modify',
                array('authenticationUser' => $mcuUsername,
                      'authenticationPassword' => $mcuPassword,
                      'conferenceName' => $destConference,
                      'participantName' => $participantName,
                      'participantProtocol' => $participantProtocol,
                      'participantType' => $participantType,
                      'operationScope' => 'activeState',
                      'important' => true)
            );
        } elseif ($destType == "waiting") {
            //move participant
            $checkMove = mcuCommand(
                array('prefix' => 'participant.'),
                'move',
                array('authenticationUser' => $mcuUsername,
                      'authenticationPassword' => $mcuPassword,
                      'conferenceName' => $sourceConference,
                      'participantName' => $participantName,
                      'participantProtocol' => $participantProtocol,
                      'participantType' => $participantType,
                      'newConferenceName' => $destConference)
            );

            //when moving someone into the waiting room, set them to family4 layout
            //$cpLayout = 'family4';

            //Set the layout for the participant that has been moved
            $setCPLayout = mcuCommand(
                array('prefix' => 'participant.'),
                'modify',
                array('authenticationUser' => $mcuUsername,
                      'authenticationPassword' => $mcuPassword,
                      'conferenceName' => $destConference,
                      'participantName' => $participantName,
                      'participantProtocol' => $participantProtocol,
                      'participantType' => $participantType,
                      'operationScope' => 'activeState',
                      'cpLayout' => 'conferenceCustom',
                      'focusType' => 'voiceActivated')
            );
        }

    }

    echo json_encode(array('alert' => '', 'refresh' => false));

} elseif (isset($_POST['action']) && $_POST['action'] == 'muteCommand') {

    $conferenceName = $_POST['conferenceName'];
    $participantName = $_POST['participantName'];
    $participantProtocol = $_POST['participantProtocol'];
    $participantType = $_POST['participantType'];
    $operationScope = $_POST['operationScope'];
    
    $muteChannel = $_POST['muteChannel'];

    if ($_POST['muteAction'] == 'mute') {
        $muteAction = true;
    } elseif ($_POST['muteAction'] == 'unmute') {
        $muteAction = false;
    }

    if ($muteChannel == 'txAll') {

        $result = mcuCommand(
            array('prefix' => 'participant.'),
            'modify',
            array('authenticationUser' => $mcuUsername,
                  'authenticationPassword' => $mcuPassword,
                  'conferenceName' => $conferenceName,
                  'participantName' => $participantName,
                  'participantProtocol' => $participantProtocol,
                  'participantType' => $participantType,
                  'operationScope' => $operationScope,
                  'audioTxMuted' => $muteAction,
                  'videoTxMuted' => $muteAction)
        );

    } else {
        $result = mcuCommand(
            array('prefix' => 'participant.'),
            'modify',
            array('authenticationUser' => $mcuUsername,
                  'authenticationPassword' => $mcuPassword,
                  'conferenceName' => $conferenceName,
                  'participantName' => $participantName,
                  'participantProtocol' => $participantProtocol,
                  'participantType' => $participantType,
                  'operationScope' => $operationScope,
                  $muteChannel => $muteAction)
        );
    }

    //echo json_encode(array('alert' => $result));
    echo json_encode(array('alert' => ''));

} elseif (isset($_POST['action']) && $_POST['action'] == 'drop') {

    $conferenceName = $_POST['conferenceName'];
    $participantName = $_POST['participantName'];
    $participantProtocol = $_POST['participantProtocol'];
    $participantType = $_POST['participantType'];
    
    $result = mcuCommand(
        array('prefix' => 'participant.'),
        'remove',
        array('authenticationUser' => $mcuUsername,
              'authenticationPassword' => $mcuPassword,
              'conferenceName' => $conferenceName,
              'participantName' => $participantName,
              'participantProtocol' => $participantProtocol,
              'participantType' => $participantType)
    );
} elseif (isset($_POST['action']) && $_POST['action'] == 'changeLayout') {
    $conferenceName = $_POST['conferenceName'];
    $customLayout = intval($_POST['layoutNumber']);
	
	$query['name'] = 'showIsLive';
	$showIsLive = filter_var(databaseQuery('readIntSetting', $query), FILTER_VALIDATE_BOOLEAN);

	if ($showIsLive == true) {

		$result = mcuCommand(
			array('prefix' => 'conference.'),
			'modify',
			array('authenticationUser' => $mcuUsername,
				  'authenticationPassword' => $mcuPassword,
				  'conferenceName' => $conferenceName,
				  'customLayoutEnabled' => true,
				  'customLayout' => $customLayout)
		);
		
	} else {
		
		$result = mcuCommand(
			array('prefix' => 'conference.'),
			'modify',
			array('authenticationUser' => $mcuUsername,
				  'authenticationPassword' => $mcuPassword,
				  'conferenceName' => $conferenceName,
				  'customLayoutEnabled' => true,
				  'customLayout' => $customLayout,
				  'setAllParticipantsToCustomLayout' => true,
				  'newParticipantsCustomLayout' => true)
		);
		
	}
    
    if ($customLayout === 1) {
        $setPaneBlank = mcuCommand(
                array('prefix' => 'conference.paneplacement.'),
                'modify',
                    array('authenticationUser' => $mcuUsername,
                        'authenticationPassword' => $mcuPassword,
                        'conferenceName' => $conferenceName,
                        'enabled' => true,
                        'panes' =>
                            array(
                                ['index' => 1,
                                'type' => 'default'],
                                ['index' => 2,
                                'type' => 'default'],
                                ['index' => 3,
                                'type' => 'default'],
                                ['index' => 4,
                                'type' => 'default'],
                                ['index' => 5,
                                'type' => 'default'],
                                ['index' => 6,
                                'type' => 'default'],
                                ['index' => 7,
                                'type' => 'default'],
                                ['index' => 8,
                                'type' => 'default'],
                                ['index' => 9,
                                'type' => 'default'],
                                ['index' => 10,
                                'type' => 'default'],
                                ['index' => 11,
                                'type' => 'default'],
                                ['index' => 12,
                                'type' => 'default'],
                                ['index' => 13,
                                'type' => 'default'],
                                ['index' => 14,
                                'type' => 'default'],
                                ['index' => 15,
                                'type' => 'default'],
                                ['index' => 16,
                                'type' => 'default'],
                                ['index' => 17,
                                'type' => 'default'],
                                ['index' => 18,
                                'type' => 'default'],
                                ['index' => 19,
                                'type' => 'default'],
                                ['index' => 20,
                                'type' => 'default']
                                )
                        )
            );
        }
	
    //$panePlacement = checkPanePlacement($conferenceName, $mcuUsername, $mcuPassword);
    
    echo json_encode(array('alert' => ''));

//DialOut
} elseif (isset($_POST['action']) && $_POST['action'] == 'call') {
    $number = $_POST['callNumber'];

    $conferenceName = $_POST['conferenceName'];

    $waitingRoom = databaseQuery('readSetting', 'waitingRoom');

    $conferenceInfo = databaseQuery('conferenceInfo', $conferenceName);
    $conferenceLayout = $conferenceInfo['layout'];

    $query['name'] = 'showIsLive';
    //$checkShow = databaseQuery('readIntSetting', $query);
    $showIsLive = filter_var(databaseQuery('readIntSetting', $query), FILTER_VALIDATE_BOOLEAN);

    //Place an outbound call from the MCU to the participant you are adding
    $resultAdd = mcuCommand(
        array('prefix' => 'participant.'),
        'add',
        array('authenticationUser' => $mcuUsername,
              'authenticationPassword' => $mcuPassword,
              'conferenceName' => $conferenceName,
              'participantProtocol' => 'sip',
              'participantType' => 'ad_hoc',
              'address' => $number,
              'addResponse' => true)
    );

    //If the conference is a foucs codec and we add a participant directly to it via the DialOut function, mark that participant as important
    if ($conferenceLayout == 1 && $conferenceName != $waitingRoom) {
        $resultImportant = mcuCommand(
            array('prefix' => 'participant.'),
            'modify',
            array('authenticationUser' => $mcuUsername,
                  'authenticationPassword' => $mcuPassword,
                  'conferenceName' => $conferenceName,
                  'participantName' => $resultAdd['participant']['participantName'],
                  'participantProtocol' => $resultAdd['participant']['participantProtocol'],
                  'participantType' => $resultAdd['participant']['participantType'],
                  'operationScope' => 'activeState',
                  'important' => true)
        );
    } elseif ($conferenceName == $waitingRoom) {

        //$cpLayout = 'family4';

        //Set the layout for the participant that has been moved
        $setCPLayout = mcuCommand(
            array('prefix' => 'participant.'),
            'modify',
            array('authenticationUser' => $mcuUsername,
                  'authenticationPassword' => $mcuPassword,
                  'conferenceName' => $conferenceName,
                  'participantName' => $resultAdd['participant']['participantName'],
                  'participantProtocol' => $resultAdd['participant']['participantProtocol'],
                  'participantType' => $resultAdd['participant']['participantType'],
                  'operationScope' => 'activeState',
                  'cpLayout' => 'conferenceCustom',
                  'focusType' => 'voiceActivated')
        );

    } elseif ($showIsLive == false) {
        $result = mcuCommand(
            array('prefix' => 'participant.'),
            'modify',
            array('authenticationUser' => $mcuUsername,
                  'authenticationPassword' => $mcuPassword,
                  'conferenceName' => $conferenceName,
                  'participantName' => $resultAdd['participant']['participantName'],
                  'participantProtocol' => $resultAdd['participant']['participantProtocol'],
                  'participantType' => $resultAdd['participant']['participantType'],
                  'operationScope' => 'activeState',
                  'cpLayout' => 'conferenceCustom',
                  'focusType' => 'voiceActivated')
        );
    } elseif ($showIsLive == true) {

        $cpLayout = 'layout1';

        $result = mcuCommand(
            array('prefix' => 'participant.'),
            'modify',
            array('authenticationUser' => $mcuUsername,
                  'authenticationPassword' => $mcuPassword,
                  'conferenceName' => $conferenceName,
                  'participantName' => $resultAdd['participant']['participantName'],
                  'participantProtocol' => $resultAdd['participant']['participantProtocol'],
                  'participantType' => $resultAdd['participant']['participantType'],
                  'operationScope' => 'activeState',
                  'cpLayout' => $cpLayout,
                  'focusType' => 'voiceActivated')
        );
    }

    echo json_encode(array('alert' => ''));

//Read settings from database
} elseif (isset($_POST['action']) && $_POST['action'] == 'readAllSettings') {
    $result = databaseQuery('readAllSettings', 'blah');
    $alert = $result['alert'];
    $settings = $result['settings'];

    echo json_encode(array('alert' => $alert, 'settings' => $settings));

} elseif (isset($_POST['action']) && $_POST['action'] == 'dropAll') {
    $participantList = $_POST['participantList'];
    $conferenceName = $_POST['conferenceName'];
    //go through the participant.enumerate, if they are in this conference, remove them!
    foreach ($participantList as $row) {
        if ($row['conferenceName'] == $conferenceName  && $row['displayName'] != "_" && $row['displayName'] != "__") {
            $result = mcuCommand(
                array('prefix' => 'participant.'),
                'remove',
                array('authenticationUser' => $mcuUsername,
                      'authenticationPassword' => $mcuPassword,
                      'conferenceName' => $conferenceName,
                      'participantName' => $row['participantName'],
                      'participantProtocol' => $row['participantProtocol'],
                      'participantType' => $row['participantType'])
            );
        }
    }
    echo json_encode(array('alert' => ''));
//someone clicked a transfer all to conference x button
} elseif (isset($_POST['action']) && $_POST['action'] == 'setupAll') {

    if ($_POST['conferenceList']) {
        $conferenceList = $_POST['conferenceList'];

        if (isset($_POST['participantList'])) {
            $participantList = $_POST['participantList'];
        } else {
            $participantList = null;
        }

        //go through the conference enumerate and add a codec to each conference and a loop to all focus conferences
        foreach ($conferenceList as $conf) {

            $addCodec = true;
            $addLoop = true;
            $codecID = 'codec' . substr($conf['conferenceName'], 0, 1);
            $codecDN = databaseQuery('readSetting', $codecID);
            $confLayout = $conf['customLayout'];
            $conferenceName = $conf['conferenceName'];
			
            if ($codecDN == 0) {
                $addLoop = false;
                $addCodec = false;
            } elseif ($participantList != null) {
                //check to see if a codec or loop are already present in the current conference
                foreach ($participantList as $part) {
                    if ($part['conferenceName'] == $conferenceName &&
                        $part['displayName'] == "__") {

                        $addCodec = false;

                        continue;

                    } elseif ($part['conferenceName'] == $conferenceName &&
                        $part['displayName'] == "_") {

                        $addLoop = false;
                        continue;

                    }
                }
            }
            
            if ($confLayout == 33 || $confLayout == 23) {
                $data['action'] = 'get';
                $data['conferenceName'] = $conferenceName;

                $oldLayout = databaseQuery('conferenceSavedLayout', $data);

                $query['name'] = 'showIsLive';
                $showIsLive = filter_var(databaseQuery('readIntSetting', $query), FILTER_VALIDATE_BOOLEAN);

                if ($showIsLive == true) {

                    $result = mcuCommand(
                        array('prefix' => 'conference.'),
                        'modify',
                        array('authenticationUser' => $mcuUsername,
                              'authenticationPassword' => $mcuPassword,
                              'conferenceName' => $conferenceName,
                              'customLayoutEnabled' => true,
                              'customLayout' => $oldLayout)
                    );

                } else {

                    $result = mcuCommand(
                        array('prefix' => 'conference.'),
                        'modify',
                        array('authenticationUser' => $mcuUsername,
                              'authenticationPassword' => $mcuPassword,
                              'conferenceName' => $conferenceName,
                              'customLayoutEnabled' => true,
                              'customLayout' => $oldLayout,
                              'setAllParticipantsToCustomLayout' => true,
                              'newParticipantsCustomLayout' => true)
                    );
                }
            }

            //Add Codec
            if ($addCodec == true) {

                $resultCodec = mcuCommand(
                    array('prefix' => 'participant.'),
                    'add',
                    array('authenticationUser' => $mcuUsername,
                          'authenticationPassword' => $mcuPassword,
                          'conferenceName' => $conf['conferenceName'],
                          'participantProtocol' => 'sip',
                          'participantName' => $codecID,
                          'participantType' => 'ad_hoc',
                          'address' => $codecDN,
                          'displayNameOverrideStatus' => true,
                          'displayNameOverrideValue' => '__',
                          'addResponse' => true)
                );

                $resultImportant = mcuCommand(
                    array('prefix' => 'participant.'),
                    'modify',
                    array('authenticationUser' => $mcuUsername,
                          'authenticationPassword' => $mcuPassword,
                          'conferenceName' => $conf['conferenceName'],
                          'participantName' => $resultCodec['participant']['participantName'],
                          'participantProtocol' => $resultCodec['participant']['participantProtocol'],
                          'participantType' => $resultCodec['participant']['participantType'],
                          'operationScope' => 'activeState',
                          'cpLayout' => 'conferenceCustom')
                );
            }

            $waitingRoom = databaseQuery('readSetting', 'waitingRoom');

            if ($conf['conferenceName'] == $waitingRoom) {

                //Set the layout of the codec in the waitingRoom
                $setCPLayout = mcuCommand(
                    array('prefix' => 'participant.'),
                    'modify',
                    array('authenticationUser' => $mcuUsername,
                          'authenticationPassword' => $mcuPassword,
                          'conferenceName' => $conf['conferenceName'],
                          'participantName' => $resultCodec['participant']['participantName'],
                          'participantProtocol' => $resultCodec['participant']['participantProtocol'],
                          'participantType' => $resultCodec['participant']['participantType'],
                          'operationScope' => 'activeState',
                          'cpLayout' => 'conferenceCustom',
                          'focusType' => 'voiceActivated')
                );
            }


            //Add Loop
            if ($addLoop == true && $conf['conferenceName'] != $waitingRoom) {

                $resultLoop = mcuCommand(
                    array('prefix' => 'participant.'),
                    'add',
                    array('authenticationUser' => $mcuUsername,
                          'authenticationPassword' => $mcuPassword,
                          'conferenceName' => $conf['conferenceName'],
                          'participantProtocol' => 'sip',
                          'participantName' => 'Loop',
                          'participantType' => 'ad_hoc',
                          'address' => $loopURI,
                          'displayNameOverrideStatus' => true,
                          'displayNameOverrideValue' => '_',
                          'audioRxMuted' => true)
                );
            }
        }

    }
    
    echo json_encode(array('alert' => ''));

//Clears all pane placement entries and resets all conferences and all panes to default
} elseif (isset($_POST['action']) && $_POST['action'] == 'clearPanePlacement') {
    
    $conferenceList = $_POST['conferenceList'];
        
    foreach ($conferenceList as $conf) {
        //Reset all panes in each conference
        $setPaneBlank = mcuCommand(
            array('prefix' => 'conference.paneplacement.'),
            'modify',
                array('authenticationUser' => $mcuUsername,
                    'authenticationPassword' => $mcuPassword,
                    'conferenceName' => $conf['conferenceName'],
                    'enabled' => true,
                    'panes' =>
                        array(
                            ['index' => 1,
                            'type' => 'default'],
                            ['index' => 2,
                            'type' => 'default'],
                            ['index' => 3,
                            'type' => 'default'],
                            ['index' => 4,
                            'type' => 'default'],
                            ['index' => 5,
                            'type' => 'default'],
                            ['index' => 6,
                            'type' => 'default'],
                            ['index' => 7,
                            'type' => 'default'],
                            ['index' => 8,
                            'type' => 'default'],
                            ['index' => 9,
                            'type' => 'default'],
                            ['index' => 10,
                            'type' => 'default'],
                            ['index' => 11,
                            'type' => 'default'],
                            ['index' => 12,
                            'type' => 'default'],
                            ['index' => 13,
                            'type' => 'default'],
                            ['index' => 14,
                            'type' => 'default'],
                            ['index' => 15,
                            'type' => 'default'],
                            ['index' => 16,
                            'type' => 'default'],
                            ['index' => 17,
                            'type' => 'default'],
                            ['index' => 18,
                            'type' => 'default'],
                            ['index' => 19,
                            'type' => 'default'],
                            ['index' => 20,
                            'type' => 'default']
                            )
                )
        );
    }

    $clearAllPanes['action'] = "clearPanePlacement";
    $clearAllPanesResult = databaseQuery('paneUpdate', $clearAllPanes);
    
    echo json_encode(array('alert' => ''));
    
// Someone clicked the teardown button
} elseif (isset($_POST['action']) && $_POST['action'] == 'teardown') {
    $participantList = $_POST['participantList'];
    //go through the participant.enumerate and drop every participant
    foreach ($participantList as $row) {
        $result = mcuCommand(
            array('prefix' => 'participant.'),
            'remove',
            array('authenticationUser' => $mcuUsername,
                  'authenticationPassword' => $mcuPassword,
                  'conferenceName' => $row['conferenceName'],
                  'participantName' => $row['participantName'],
                  'participantProtocol' => $row['participantProtocol'],
                  'participantType' => $row['participantType'])
        );
    }
    
    $conferenceList = $_POST['conferenceList'];
        
    foreach ($conferenceList as $conf) {
        //Reset all panes in each conference
        $setPaneBlank = mcuCommand(
            array('prefix' => 'conference.paneplacement.'),
            'modify',
                array('authenticationUser' => $mcuUsername,
                    'authenticationPassword' => $mcuPassword,
                    'conferenceName' => $conf['conferenceName'],
                    'enabled' => true,
                    'panes' =>
                        array(
                            ['index' => 1,
                            'type' => 'default'],
                            ['index' => 2,
                            'type' => 'default'],
                            ['index' => 3,
                            'type' => 'default'],
                            ['index' => 4,
                            'type' => 'default'],
                            ['index' => 5,
                            'type' => 'default'],
                            ['index' => 6,
                            'type' => 'default'],
                            ['index' => 7,
                            'type' => 'default'],
                            ['index' => 8,
                            'type' => 'default'],
                            ['index' => 9,
                            'type' => 'default'],
                            ['index' => 10,
                            'type' => 'default'],
                            ['index' => 11,
                            'type' => 'default'],
                            ['index' => 12,
                            'type' => 'default'],
                            ['index' => 13,
                            'type' => 'default'],
                            ['index' => 14,
                            'type' => 'default'],
                            ['index' => 15,
                            'type' => 'default'],
                            ['index' => 16,
                            'type' => 'default'],
                            ['index' => 17,
                            'type' => 'default'],
                            ['index' => 18,
                            'type' => 'default'],
                            ['index' => 19,
                            'type' => 'default'],
                            ['index' => 20,
                            'type' => 'default']
                            )
                )
        );
    }

    //Set showIsLive variable in DB to FALSE
    $query['name'] = 'showIsLive';
    $query['value'] = 'false';
    $result = databaseQuery('writeIntSetting', $query);

    echo json_encode(array('alert' => ''));

// Someone clicked the preShow button
} elseif (isset($_POST['action']) && $_POST['action'] == 'preShow') {

    //Set showIsLive variable in DB to FALSE
    $query['name'] = 'showIsLive';
    $query['value'] = 'false';
    $result = databaseQuery('writeIntSetting', $query);

    $waitingRoom = databaseQuery('readSetting', 'waitingRoom');
	
    $conferenceList = $_POST['conferenceList'];
    $participantList = $_POST['participantList'];

    foreach ($conferenceList as $conf) {

        $currentConference = $conf['conferenceName'];
        $confLayout = $conf['customLayout'];
        $cpLayout = 'layout' . $confLayout;

        //if the conference is in a single pane configuration, then skip
        if ($confLayout != 1 && $currentConference != $waitingRoom) {
            foreach ($participantList as $part) {
                if ($part['displayName'] != '__' && $part['displayName'] != '_' && $part['conferenceName'] == $currentConference) {
                    $result = mcuCommand(
                        array('prefix' => 'participant.'),
                        'modify',
                        array('authenticationUser' => $mcuUsername,
                              'authenticationPassword' => $mcuPassword,
                              'conferenceName' => $part['conferenceName'],
                              'participantName' => $part['participantName'],
                              'participantProtocol' => $part['participantProtocol'],
                              'participantType' => $part['participantType'],
                              'operationScope' => 'activeState',
                              'cpLayout' => 'conferenceCustom',
                              'focusType' => 'voiceActivated'
							  )
                    );
                }
            }
        }
    }



    echo json_encode(array('alert' => ''));

// Someone clicked the Live Show! button
} elseif (isset($_POST['action']) && $_POST['action'] == 'liveShow') {

    //Set showIsLive variable in DB to TRUE
    $query['name'] = 'showIsLive';
    $query['value'] = 'true';
    $result = databaseQuery('writeIntSetting', $query);

    $waitingRoom = databaseQuery('readSetting', 'waitingRoom');

    $cpLayout = 'layout1';

    $conferenceList = $_POST['conferenceList'];
    $participantList = $_POST['participantList'];

    foreach ($conferenceList as $conf) {

        $currentConference = $conf['conferenceName'];
        $confLayout = $conf['customLayout'];
		
		$codec = codecInfo($currentConference);

        //if the conference is in a single pane configuration, then skip
        if ($confLayout != 1 && $currentConference != $waitingRoom) {
            foreach ($participantList as $part) {
                if ($part['displayName'] != '__' && $part['displayName'] != '_' && $part['conferenceName'] == $currentConference) {
                    $result = mcuCommand(
                        array('prefix' => 'participant.'),
                        'modify',
                        array('authenticationUser' => $mcuUsername,
                              'authenticationPassword' => $mcuPassword,
                              'conferenceName' => $part['conferenceName'],
                              'participantName' => $part['participantName'],
                              'participantProtocol' => $part['participantProtocol'],
                              'participantType' => $part['participantType'],
                              'operationScope' => 'activeState',
                              'cpLayout' => $cpLayout,
                              'focusType' => 'participant',
							  'focusParticipant' =>
									array('participantName' => $codec['participantName'],
									'participantProtocol' => $codec['participantProtocol'],
									'participantType' => $codec['participantType'])
						)
					);
				}
            }
        }
    }

    echo json_encode(array('alert' => ''));

//Somone opened the conference layout popup
} elseif (isset($_POST['action']) && $_POST['action'] == 'queryPanePlacement') {
    $conferenceName = $_POST['conferenceName'];
    echo json_encode(checkPanePlacement($conferenceName, $mcuUsername, $mcuPassword));

//someone changed a pane placement dropdown
} elseif (isset($_POST['action']) && $_POST['action'] == 'modifyPane') {
    $pane = intval($_POST['pane']) + 1;
    $conferenceName = $_POST['conferenceName'];
    $type = $_POST['type'];

    //Get conference info and ID
    $conferenceInfo = databaseQuery('conferenceInfo', $conferenceName);
    $conferenceTableId = $conferenceInfo['id'];

    //if they changed it to be default, blank or loudest just change that pane on the mcu
    if ($type == "default" || $type == "blank" || $type == "loudest") {
        $result = mcuCommand(
            array('prefix' => 'conference.paneplacement.'),
            'modify',
            array('authenticationUser' => $mcuUsername,
                  'authenticationPassword' => $mcuPassword,
                  'conferenceName' => $conferenceName,
                  'panes' => array(['index' => $pane,
                                    'type' => $type]))
        );

        //After we set the pane to default, blank, type, or loudest, delete the entry for that pane in the panePlacement table
        $deletePane['action'] = "delete";
        $deletePane['pane'] = $pane;
        $deletePane['conferenceTableId'] = $conferenceTableId;
        $deletePaneResult = databaseQuery('paneUpdate', $deletePane);

    //if they changed it to be a participant
    } elseif ($type == "participant") {

        //Set participant specific variables
        $displayName = $_POST['displayName'];
        $participantName = $_POST['participantName'];
        $participantProtocol = $_POST['participantProtocol'];
        $participantType = $_POST['participantType'];

        //Get Participant info of the moving participant to update the Pane Placement database
        $findPaticipant['displayName'] = addslashes($displayName);
        $findPaticipant['conferenceId'] = $conferenceTableId;
        $participantInfo = databaseQuery('participantInfo', $findPaticipant);

        //Set variables for SQL queries in following actions
        $participantTableId = $participantInfo['id'];

        $setPane = mcuCommand(
            array('prefix' => 'conference.paneplacement.'),
            'modify',
            array('authenticationUser' => $mcuUsername,
                  'authenticationPassword' => $mcuPassword,
                  'conferenceName' => $conferenceName,
                  'panes' => array(['index' => $pane,
                                    'type' => $type,
                                    'participantName' => $participantName,
                                    'participantProtocol' => $participantProtocol,
                                    'participantType' => $participantType]))
        );

        $updatePane['action'] = "update";
        $updatePane['pane'] = $pane;
        $updatePane['conferenceTableId'] = $conferenceTableId;
        $updatePane['participantTableId'] = $participantTableId;
        $updatePaneResult = databaseQuery('paneUpdate', $updatePane);

    }
    echo json_encode(array('alert' => '' ));
} elseif (isset($_POST['action']) && $_POST['action'] == 'setSpecialLayout') {
    	
	$conferenceName = $_POST['conferenceName'];
	$participantName = $_POST['participantName'];
	$participantProtocol = $_POST['participantProtocol'];
	$participantType = $_POST['participantType'];
	$scrubbedParticipantList = $_POST['scrubbedParticipantList'];
	$layoutType = $_POST['layoutType'];
    $conferenceInfo = databaseQuery('conferenceInfo', $conferenceName);
    $conferenceTableId = $conferenceInfo['id'];
    $currentLayout = filter_var($conferenceInfo['layout'], FILTER_VALIDATE_INT);

	if ($layoutType === 'focus' || $layoutType === 'transferFocus') {
		$customLayout = 33;
	} else if ($layoutType === 'important') {
		if ($currentLayout === 3 || $currentLayout === 23){
            $customLayout = 23;
        } else {
            $customLayout = 33;
        }
    }
    
    if ($currentLayout === 3 || $currentLayout === 23){
        //set pane order for assignment in special layout
        $specialPaneIndex[2] = 2;
        $specialPaneIndex[3] = 3;
        $specialPaneIndex[4] = 4;
        $specialPaneIndex[5] = 5;
        $specialPaneIndex[6] = 6;
        $specialPaneIndex[7] = 7;
        $specialPaneIndex[8] = 8;
        $specialPaneIndex[9] = 9;
    } else {
        //set pane order for assignment in special layout
        $specialPaneIndex[2] = 2;
        $specialPaneIndex[3] = 13;
        $specialPaneIndex[4] = 12;
        $specialPaneIndex[5] = 5;
        $specialPaneIndex[6] = 6;
        $specialPaneIndex[7] = 4;
        $specialPaneIndex[8] = 11;
        $specialPaneIndex[9] = 3;
    }
	

    if ($currentLayout != $customLayout) {
        $data['action'] = 'save';
        $data['conferenceName'] = $conferenceName;

        databaseQuery('conferenceSavedLayout', $data);
    }
	
	//set selected participant to important
	$checkImportant = mcuCommand(
		array('prefix' => 'participant.'),
		'modify',
		array('authenticationUser' => $mcuUsername,
			  'authenticationPassword' => $mcuPassword,
			  'conferenceName' => $conferenceName,
			  'participantName' => $participantName,
			  'participantProtocol' => $participantProtocol,
			  'participantType' => $participantType,
			  'operationScope' => 'activeState',
			  'important' => true)
	);
	
    if ($currentLayout != $customLayout) {
        $query['name'] = 'showIsLive';
        $showIsLive = filter_var(databaseQuery('readIntSetting', $query), FILTER_VALIDATE_BOOLEAN);

        if ($showIsLive == true) {

            $result = mcuCommand(
                array('prefix' => 'conference.'),
                'modify',
                array('authenticationUser' => $mcuUsername,
                      'authenticationPassword' => $mcuPassword,
                      'conferenceName' => $conferenceName,
                      'customLayoutEnabled' => true,
                      'customLayout' => $customLayout)
            );

        } else {

            $result = mcuCommand(
                array('prefix' => 'conference.'),
                'modify',
                array('authenticationUser' => $mcuUsername,
                      'authenticationPassword' => $mcuPassword,
                      'conferenceName' => $conferenceName,
                      'customLayoutEnabled' => true,
                      'customLayout' => $customLayout,
                      'setAllParticipantsToCustomLayout' => true,
                      'newParticipantsCustomLayout' => true)
            );
        }
    }
    
    //need 2 counter variables for assigning the proper panes to the proper special location
    $y = 1;
    $x = 2;
    while ($y <= 20) {
        $paneDetail[$y] = array('index' => $y,
                                'type' => 'blank');
        $y++;
    }
    
    foreach ($scrubbedParticipantList as $part) {

        if ($currentLayout != $customLayout) {
            $data['action'] = 'save';
            $data['pane'] = $part['pane'];
            $data['participantName'] = $part['participantName'];
            $data['displayName'] = $part['displayName'];
            $data['conferenceName'] = $conferenceName;
            
            $setSavedPaneResult = databaseQuery('savedPane', $data);
        }
        
        //Get Participant info of the moving participant to update the Pane Placement database
        $findPaticipant['displayName'] = addslashes($part['displayName']);
        $findPaticipant['conferenceId'] = $conferenceTableId;
        $participantInfo = databaseQuery('participantInfo', $findPaticipant);

        //Set variables for SQL queries in following actions
        $participantTableId = $participantInfo['id'];
        
        
        if ($part['participantName'] == $participantName) {
            
            $paneDetail[1]  = array('index' => 1,
                                    'type' => 'participant',
                                    'participantName' => $part['participantName'],
                                    'participantProtocol' => $part['participantProtocol'],
                                    'participantType' => $part['participantType']);
            
            $updatePane['action'] = "customLayoutPaneUpdate";
            $updatePane['pane'] = 1;
            $updatePane['conferenceTableId'] = $conferenceTableId;
            $updatePane['participantTableId'] = $participantTableId;
            $updatePaneResult = databaseQuery('paneUpdate', $updatePane);
            
        } else {
            
			$updatePane['action'] = "customLayoutPaneUpdate";
			$updatePane['pane'] = 0;
			$updatePane['conferenceTableId'] = $conferenceTableId;
			$updatePane['participantTableId'] = $participantTableId;
			
			if ($layoutType === 'important') {
				$paneDetail[$specialPaneIndex[$x]] = array('index' => $specialPaneIndex[$x],
                                            'type' => 'participant',
                                            'participantName' => $part['participantName'],
                                            'participantProtocol' => $part['participantProtocol'],
                                            'participantType' => $part['participantType']);

                $updatePane['pane'] = $specialPaneIndex[$x];
			}
			
			$updatePaneResult = databaseQuery('paneUpdate', $updatePane);
            $x++;
        }
        
    }
    
    $setCustomPanes = mcuCommand(
        array('prefix' => 'conference.paneplacement.'),
        'modify',
            array('authenticationUser' => $mcuUsername,
                'authenticationPassword' => $mcuPassword,
                'conferenceName' => $conferenceName,
                'enabled' => true,
                'panes' =>
                        array($paneDetail[1],
                        $paneDetail[2],
                        $paneDetail[3],
                        $paneDetail[4],
                        $paneDetail[5],
                        $paneDetail[6],
                        $paneDetail[7],
                        $paneDetail[8],
                        $paneDetail[9],
                        $paneDetail[10],
                        $paneDetail[11],
                        $paneDetail[12],
                        $paneDetail[13],
                        $paneDetail[14],
                        $paneDetail[15],
                        $paneDetail[16],
                        $paneDetail[17],
                        $paneDetail[18],
                        $paneDetail[19],
                        $paneDetail[20]
                        )
        )
    );
    
    /*
    //For muting participants rather than lowering their volume
    foreach ($scrubbedParticipantList as $part) {
        
		if ($part['participantName'] == $participantName) {
            
            $unmuteFocus = mcuCommand(
				array('prefix' => 'participant.'),
				'modify',
				array('authenticationUser' => $mcuUsername,
						'authenticationPassword' => $mcuPassword,
						'conferenceName' => $conferenceName,
						'participantName' => $part['participantName'],
						'participantProtocol' => $part['participantProtocol'],
						'participantType' => $part['participantType'],
						'operationScope' => 'activeState',
						'audioRxMuted' => false)
			);
			
		} else {
			
            $muteNonFocus = mcuCommand(
				array('prefix' => 'participant.'),
				'modify',
				array('authenticationUser' => $mcuUsername,
						'authenticationPassword' => $mcuPassword,
						'conferenceName' => $conferenceName,
						'participantName' => $part['participantName'],
						'participantProtocol' => $part['participantProtocol'],
						'participantType' => $part['participantType'],
						'operationScope' => 'activeState',
						'audioRxMuted' => true)
			);
		
		}
	}
    */
    
    //For lowering participants volume rather than muting them
    foreach ($scrubbedParticipantList as $part) {
        
		if ($part['participantName'] == $participantName) {
            
            $unmuteFocus = mcuCommand(
				array('prefix' => 'participant.'),
				'modify',
				array('authenticationUser' => $mcuUsername,
						'authenticationPassword' => $mcuPassword,
						'conferenceName' => $conferenceName,
						'participantName' => $part['participantName'],
						'participantProtocol' => $part['participantProtocol'],
						'participantType' => $part['participantType'],
						'operationScope' => 'activeState',
						'audioRxGainMode' => 'default')
			);
			
		} else {
			
            $muteNonFocus = mcuCommand(
				array('prefix' => 'participant.'),
				'modify',
				array('authenticationUser' => $mcuUsername,
						'authenticationPassword' => $mcuPassword,
						'conferenceName' => $conferenceName,
						'participantName' => $part['participantName'],
						'participantProtocol' => $part['participantProtocol'],
						'participantType' => $part['participantType'],
						'operationScope' => 'activeState',
						'audioRxGainMode' => 'fixed',
                        'audioRxGainMillidB' => -10000)
			);
		
		}
	}
    
    echo json_encode(array('alert' => ''));

} elseif (isset($_POST['action']) && $_POST['action'] == 'resetSpecialLayout') {
    
	$conferenceName = $_POST['conferenceName'];
	$participantName = $_POST['participantName'];
	$participantProtocol = $_POST['participantProtocol'];
	$participantType = $_POST['participantType'];
	$scrubbedParticipantList = $_POST['scrubbedParticipantList'];
    	
	$data['action'] = 'get';
	$data['conferenceName'] = $conferenceName;
	
	$oldLayout = databaseQuery('conferenceSavedLayout', $data);
    
    $conferenceInfo = databaseQuery('conferenceInfo', $conferenceName);
    $conferenceTableId = $conferenceInfo['id'];
	
	//set selected participant to important
	$checkImportant = mcuCommand(
		array('prefix' => 'participant.'),
		'modify',
		array('authenticationUser' => $mcuUsername,
			  'authenticationPassword' => $mcuPassword,
			  'conferenceName' => $conferenceName,
			  'participantName' => $participantName,
			  'participantProtocol' => $participantProtocol,
			  'participantType' => $participantType,
			  'operationScope' => 'activeState',
			  'important' => false)
	);
	
	$query['name'] = 'showIsLive';
    $showIsLive = filter_var(databaseQuery('readIntSetting', $query), FILTER_VALIDATE_BOOLEAN);

	if ($showIsLive == true) {
	
		$result = mcuCommand(
			array('prefix' => 'conference.'),
			'modify',
			array('authenticationUser' => $mcuUsername,
				  'authenticationPassword' => $mcuPassword,
				  'conferenceName' => $conferenceName,
				  'customLayoutEnabled' => true,
				  'customLayout' => $oldLayout)
		);
	
	} else {
	
		$result = mcuCommand(
			array('prefix' => 'conference.'),
			'modify',
			array('authenticationUser' => $mcuUsername,
				  'authenticationPassword' => $mcuPassword,
				  'conferenceName' => $conferenceName,
				  'customLayoutEnabled' => true,
				  'customLayout' => $oldLayout,
				  'setAllParticipantsToCustomLayout' => true,
				  'newParticipantsCustomLayout' => true)
		);
	}

    $y = 1;
    while ($y <= 20) {
        $paneDetail[$y] = array('index' => $y,
                                'type' => 'default');
        $y++;
    }    
    
    $x = 1;
    foreach ($scrubbedParticipantList as $part) {
        
        $data['action'] = 'get';
        $data['participantName'] = $part['participantName'];
        $data['displayName'] = $part['displayName'];
        $data['conferenceName'] = $conferenceName;
        
        $oldPane = databaseQuery('savedPane', $data);
            
        $paneDetail[$x]  = array('index' => $oldPane,
                             'type' => 'participant',
                             'participantName' => $part['participantName'],
                             'participantProtocol' => $part['participantProtocol'],
                             'participantType' => $part['participantType']);
        
        $x++;
        
        //Get Participant info of the moving participant to update the Pane Placement database
        $findPaticipant['displayName'] = addslashes($part['displayName']);
        $findPaticipant['conferenceId'] = $conferenceTableId;
        $participantInfo = databaseQuery('participantInfo', $findPaticipant);

        //Set variables for SQL queries in following actions
        $participantTableId = $participantInfo['id'];
                
        $updatePane['action'] = "customLayoutPaneUpdate";
        $updatePane['pane'] = $oldPane;
        $updatePane['conferenceTableId'] = $conferenceTableId;
        $updatePane['participantTableId'] = $participantTableId;
        $updatePaneResult = databaseQuery('paneUpdate', $updatePane);
    }
	
	$setPaneBlank = mcuCommand(
		array('prefix' => 'conference.paneplacement.'),
		'modify',
			array('authenticationUser' => $mcuUsername,
				'authenticationPassword' => $mcuPassword,
				'conferenceName' => $conferenceName,
				'enabled' => true,
				'panes' =>
					array($paneDetail[1],
                        $paneDetail[2],
                        $paneDetail[3],
                        $paneDetail[4],
						$paneDetail[5],
						$paneDetail[6],
						$paneDetail[7],
						$paneDetail[8],
						$paneDetail[9],
						$paneDetail[10],
						$paneDetail[11],
						$paneDetail[12],
						$paneDetail[13],
                        $paneDetail[14],
						$paneDetail[15],
						$paneDetail[16],
						$paneDetail[17],
						$paneDetail[18],
						$paneDetail[19],
						$paneDetail[20]
						)
			)
	);
	
    /*
    //For Muting and unmuting
    foreach ($scrubbedParticipantList as $part) {

        $unmuteAll = mcuCommand(
				array('prefix' => 'participant.'),
				'modify',
				array('authenticationUser' => $mcuUsername,
						'authenticationPassword' => $mcuPassword,
						'conferenceName' => $conferenceName,
						'participantName' => $part['participantName'],
						'participantProtocol' => $part['participantProtocol'],
						'participantType' => $part['participantType'],
						'operationScope' => 'activeState',
						'audioRxMuted' => false)
		);

    }
    */
    
    //For returning volume to normal
    foreach ($scrubbedParticipantList as $part) {

        $unmuteAll = mcuCommand(
				array('prefix' => 'participant.'),
				'modify',
				array('authenticationUser' => $mcuUsername,
						'authenticationPassword' => $mcuPassword,
						'conferenceName' => $conferenceName,
						'participantName' => $part['participantName'],
						'participantProtocol' => $part['participantProtocol'],
						'participantType' => $part['participantType'],
						'operationScope' => 'activeState',
						'audioRxGainMode' => 'default')
		);

    }
    
    echo json_encode(array('alert' => '' ));

}
