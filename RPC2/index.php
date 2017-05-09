 <?php

require_once 'XML/RPC2/Server.php';

// Let's define a class with public static methods
// PHPDOC comments are really important because they are used for automatic
// signature checking

class Feedback {

    /**
     * echoes the message received
     *
     * @param string  Message
     * @return string The echo
     */
    public static function echoecho($string) {
	 $file = 'people.txt';
	 // Open the file to get existing content
	 $current = file_get_contents($file);
	 // Append a new person to the file
	 $current .= $string."\n";
	 // Write the contents back to the file
	 file_put_contents($file, $current);
        return $string;
    }

	public static function participantJoined($string) {
	 $file = 'people.txt';
	 // Open the file to get existing content
	 $current = file_get_contents($file);
	 // Append a new person to the file
	 $current .= $string."\n";
	 // Write the contents back to the file
	 file_put_contents($file, $current);
        return $string;
    }
	
	public static function participantLeft($string) {
	 $file = 'people.txt';
	 // Open the file to get existing content
	 $current = file_get_contents($file);
	 // Append a new person to the file
	 $current .= $string."\n";
	 // Write the contents back to the file
	 file_put_contents($file, $current);
        return $string;
    }

	public static function participantConnected($string) {
	 $file = 'people.txt';
	 // Open the file to get existing content
	 $current = file_get_contents($file);
	 // Append a new person to the file
	 $current .= $string."\n";
	 // Write the contents back to the file
	 file_put_contents($file, $current);
        return $string;
    }
	public static function participantDisconnected($string) {
	 $file = 'people.txt';
	 // Open the file to get existing content
	 $current = file_get_contents($file);
	 // Append a new person to the file
	 $current .= $string."\n";
	 // Write the contents back to the file
	 file_put_contents($file, $current);
        return $string;
    }
	public static function events($string) {
	 $file = 'people.txt';
	 // Open the file to get existing content
	 $current = file_get_contents($file);
	 // Append a new person to the file
	 $current .= $string."\n";
	 // Write the contents back to the file
	 file_put_contents($file, $current);
        return $string;
    }
}

$options = array(
    //'prefix' => 'events.' // we define a sort of "namespace" for the server
);

// Let's build the server object with the name of the Echo class 
$server = XML_RPC2_Server::create('Feedback', $options);
$server->handleCall();

?>