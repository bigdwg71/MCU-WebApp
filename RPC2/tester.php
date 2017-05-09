<?php

require('XML/RPC2/Client.php');

$mcuURL = 'http://10.99.150.245';
$mcuRpc = $mcuURL . '/RPC2';
$mcuUsername = 'admin';
$mcuPassword = 'C1sc0123';

/*
$client = XML_RPC2_Client::create('http://localhost/woa3.0/RPC2/',
	array(
	'debug' => true,
	//'prefix' => 'events.'
	)
);
*/

function mcuCommand($options, $commandSuffix, $command) {
	global $mcuRpc;
	global $mcuUsername;
	global $mcuPassword;

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

$feedbackReceiver = mcuCommand(array('prefix' => 'feedbackReceiver.'), 'configure', array('authenticationUser' => $mcuUsername, 'authenticationPassword' => $mcuPassword, 'receiverURI' => 'http://10.99.110.101/woa3.0/RPC2/'));

//usleep(2000000);

$feedbackReceiverQuery = mcuCommand(array('prefix' => 'feedbackReceiver.'), 'query', array('authenticationUser' => $mcuUsername, 'authenticationPassword' => $mcuPassword));

//$participantEnumerate = mcuCommand(array('prefix' => 'participant.'), 'enumerate', array('authenticationUser' => $mcuUsername, 'authenticationPassword' => $mcuPassword, 'operationScope' => array('currentState'), 'enumerateFilter' => 'connected'));

$debugArray = array('feedback' => $feedbackReceiver, 'feedbackQ' => $feedbackReceiverQuery);

//$panesArray = databaseQuery('checkPanes');

echo json_encode(array('debugArray' => $debugArray));
		
/*
try {
// this call lists all the open-source licenses they accept
    $result = $client->echoecho('XML_RPC2');

    // $result is a complex PHP type (no XMLRPC decoding needed, it's already done)
    print_r($result);

}
catch (XML_RPC2_FaultException $e) {

    // The XMLRPC server returns a XMLRPC error
    die('Exception #' . $e->getFaultCode() . ' : ' . $e->getFaultString());

} catch (Exception $e) {

    // Other errors (HTTP or networking problems...)
    die('Exception : ' . $e->getMessage());

}
*/
?>