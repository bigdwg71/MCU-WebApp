<?php
require_once '../functions.php';

$allConferences = databaseQuery('allConferences', 'blah');

if (isset($_POST['submit'])) {
	//error_log("POST: " . json_encode($_POST));

	$settingsArray = array();

	foreach ($_POST as $name => $value) {
		
		if ($name != 'submit' && $name != 'autoExpandConference') {
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
			
			if (in_array($conferenceName, $_POST['autoExpandConference'])) {
				//error_log("Conference Name Matched: " . $conferenceName);
				$conferenceArray[$conferenceName]['name'] = $conferenceName;
				$conferenceArray[$conferenceName]['value'] = 1;
			} else {
				$conferenceArray[$conferenceName]['name'] = $conferenceName;
				$conferenceArray[$conferenceName]['value'] = 0;
			}
		}
		
		$conferenceResponse = databaseQuery('updateConferenceAutoExpand', $conferenceArray);
	}
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
                            <th>Setting</th><th>Value</th>
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
											<td valign="top">
												<label for="setting">'.$setting['displayName'].'</label>
											</td>
											<td valign="top">
												<input type="password" name="'.$setting['name'].'" display="'.$setting['displayName'].'" value="'.$setting['value'].'" maxlength="256">
											</td>
										</tr>';
									}
									else {
										echo '
										<tr>
											<td valign="top">
												<label for="setting">'.$setting['displayName'].'</label>
											</td>
											<td valign="top">
												<input type="text" name="'.$setting['name'].'" display="'.$setting['displayName'].'" value="'.$setting['value'].'" maxlength="256">
											</td>
										</tr>';
									}
								}
								
								}
								
							}
							
							echo '<tr><th>Conference</th><th>Auto-Expand Enabled</th></tr>';
							
							foreach ($allConferences as $conferenceRow) {
								
								if (is_array($conferenceRow) || is_object($conferenceRow)) {
								
									//foreach ($conferenceRow as $conference) {
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
										echo '</tr>';
										
									//}
								}
							}
                        ?>

                        <tr>
                            <td colspan="2">
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
							<td colspan="2">
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
