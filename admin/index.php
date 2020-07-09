<?php
require_once '../functions.php';

if (isset($_POST['submit'])) {

    foreach ($_POST as $name => $value) {

        $array = array();

        if ($name != 'submit') {
            $array[$name]['name'] = $name;
            $array[$name]['value'] = $value;
            $response = databaseQuery('updateSettings', $array);
            //echo json_encode($array);
        }
    }

}

$savedsetting = databaseQuery('readAllSettings', 'blah');

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

                        ?>

                        <tr>
                            <td colspan="2">
                                <input type="submit" name="submit" value="Submit" class="button">
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
