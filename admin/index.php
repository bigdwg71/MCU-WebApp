<?php
require_once '../functions.php';

$allConferences = databaseQuery('allConferences', 'blah');

if (isset($_POST['submit'])) {
	//error_log("POST: " . json_encode($_POST));

	$settingsArray = array();

	foreach ($_POST as $name => $value) {
		
		if ($name != 'submit' && $name != 'autoExpandConference' && $name != 'autoMuteConference' && $name != 'codecDN') {
			//error_log($name . ": " . $value);
			$settingsArray[$name]['name'] = $name;
            $settingsArray[$name]['value'] = $value;
        }
    }
	//error_log("Settings Array: " . json_encode($settingsArray));
	$settingsResponse = databaseQuery('updateSettings', $settingsArray);
	
	if(!empty($_POST['autoExpandConference'])){
		//error_log("POST of autoExpandConference: " . json_encode($_POST['autoExpandConference']));
		$conferenceArray = array();
		
		foreach($allConferences as $conference) {
			$conferenceName = $conference['conferenceName'];
			//error_log("Conference Name: " . $conferenceName);
			
			$conferenceArray[$conferenceName]['setting'] = 'autoExpand';
			$conferenceArray[$conferenceName]['name'] = $conferenceName;
			
			if (in_array($conferenceName, $_POST['autoExpandConference'])) {
				//error_log("Conference Name Matched: " . $conferenceName);
				$conferenceArray[$conferenceName]['value'] = 1;
			} else {
				$conferenceArray[$conferenceName]['value'] = 0;
			}
		}
	} else {
		
		foreach($allConferences as $conference) {
			$conferenceName = $conference['conferenceName'];
			//error_log("Conference Name: " . $conferenceName);
			
			$conferenceArray[$conferenceName]['setting'] = 'autoExpand';
			$conferenceArray[$conferenceName]['name'] = $conferenceName;
			$conferenceArray[$conferenceName]['value'] = 0;
			
		}
		
	}
	
	$conferenceResponse = databaseQuery('updateConferenceSetting', $conferenceArray);
	
	if(!empty($_POST['autoMuteConference'])){
		//error_log("POST of autoExpandConference: " . json_encode($_POST['autoExpandConference']));
		$conferenceArray = array();
		
		foreach($allConferences as $conference) {
			$conferenceName = $conference['conferenceName'];
			//error_log("Conference Name: " . $conferenceName);
			
			$conferenceArray[$conferenceName]['setting'] = 'autoMute';
			$conferenceArray[$conferenceName]['name'] = $conferenceName;
			
			if (in_array($conferenceName, $_POST['autoMuteConference'])) {
				//error_log("Conference Name Matched: " . $conferenceName);
				$conferenceArray[$conferenceName]['value'] = 1;
			} else {
				$conferenceArray[$conferenceName]['value'] = 0;
			}
		}
	} else {
		
		foreach($allConferences as $conference) {
			$conferenceName = $conference['conferenceName'];
			//error_log("Conference Name: " . $conferenceName);
			
			$conferenceArray[$conferenceName]['setting'] = 'autoMute';
			$conferenceArray[$conferenceName]['name'] = $conferenceName;
			$conferenceArray[$conferenceName]['value'] = 0;
		}
	}
	
	$conferenceResponse = databaseQuery('updateConferenceSetting', $conferenceArray);
		
	if(!empty($_POST['codecDN'])){
		//error_log("POST of codecDN: " . json_encode($_POST));
		$conferenceArray = array();
		
		foreach($allConferences as $index => $conference) {
			$conferenceName = $conference['conferenceName'];
			//error_log("Conference Name: " . $conferenceName);
			
			$conferenceArray[$conferenceName]['setting'] = 'codecDN';
			$conferenceArray[$conferenceName]['name'] = $conferenceName;
			
			if($_POST['codecDN'][$index]){
				$conferenceArray[$conferenceName]['value'] = $_POST['codecDN'][$index];
			} else {
				$conferenceArray[$conferenceName]['value'] = NULL;
			}
		}
	}
	//error_log("POST of conferenceArray: " . json_encode($conferenceArray));
	$conferenceResponse = databaseQuery('updateConferenceSetting', $conferenceArray);

}

$savedsetting = databaseQuery('readAllSettings', 'blah');
$allConferences = databaseQuery('allConferences', 'blah');
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>JKL - Wall Control - Admin</title>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
        <meta http-equiv="x-ua-compatible" content="IE=8" />
        <link rel="stylesheet" type="text/css" href="../css/base.css" />
    </head>
    <body>
        <div id="settings">
            <form name="htmlform" method="post" action="index.php">
                <table id="settingsForm" class="tableStyle">
                    <thead>
                        <tr>
                            <th colspan="2">Setting</th><th colspan="2">Value</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php

							foreach ($savedsetting as $settingRow) {
								
								if (is_array($settingRow) || is_object($settingRow)) {
								
								foreach ($settingRow as $setting) {
									if ($setting['name'] == 'mcuPassword') {
										echo '
										<tr>
											<td valign="top" colspan="2">
												<label for="setting">'.$setting['displayName'].'</label>
											</td>
											<td valign="top" colspan="2">
												<input type="password" name="'.$setting['name'].'" display="'.$setting['displayName'].'" value="'.$setting['value'].'" maxlength="256">
											</td>
										</tr>';
									}
									else {
										echo '
										<tr>
											<td valign="top" colspan="2">
												<label for="setting">'.$setting['displayName'].'</label>
											</td>
											<td valign="top" colspan="2">
												<input type="text" name="'.$setting['name'].'" display="'.$setting['displayName'].'" value="'.$setting['value'].'" maxlength="256">
											</td>
										</tr>';
									}
								}
								
								}
								
							}
							
							echo '<tr><th>Conference</th><th>Auto-Expand</th><th>Auto-Mute</th><th>Codec DN</th></tr>';
							
							foreach ($allConferences as $conferenceRow) {
								
								if (is_array($conferenceRow) || is_object($conferenceRow)) {
								
									echo '<tr>';
									echo '<td valign="top">
											<label for="conference">' . $conferenceRow['conferenceName'] . '</label>
										  </td>';
										  
									echo '<td valign="top">';
									
									if ($conferenceRow['autoExpand'] == true) {
										
										echo '<input type="checkbox" name="autoExpandConference[]" value="' . $conferenceRow['conferenceName'] . '" checked>';
										
									} else {
										
										echo '<input type="checkbox" name="autoExpandConference[]" value="' . $conferenceRow['conferenceName'] . '">';
										
									}
									echo '</td>';
									
									echo '<td valign="top">';
									
									if ($conferenceRow['autoMute'] == true) {
										
										echo '<input type="checkbox" name="autoMuteConference[]" value="' . $conferenceRow['conferenceName'] . '" checked>';
										
									} else {
										
										echo '<input type="checkbox" name="autoMuteConference[]" value="' . $conferenceRow['conferenceName'] . '">';
										
									}
									echo '</td>';
									
									echo '<td valign="top">';
									
									echo '<input type="text" name="codecDN[]" value="' . $conferenceRow['codecDN'] . '" maxlength="256">';
									
									echo '</td>';
									echo '</tr>';
									
								}
							}
                        ?>

                        <tr>
                            <td colspan="4">
                                <input type="submit" name="submit" value="Submit" class="button">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
			<form action="../">
				<table id="settingsForm" class="tableStyle">
                    <tbody>
						<tr>
							<td colspan="4">
								<input type="submit" value="Back to Webapp" />
							</td>
						</tr>
					</tbody>
                </table>
			</form>
        </div>



        <div style="clear:both;">
        </div>
    </body>
</html>
