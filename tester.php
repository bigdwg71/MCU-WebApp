<?php
    require_once 'XML/RPC2/Client.php';
    require_once 'functions.php';

    for ($x = 5011; $x <= 5019; $x++) {
        
        $testURI = $x . '@' . $domainName;
        
        $result = mcuCommand(
            array('prefix' => 'participant.'),
            'add',
            array('authenticationUser' => $mcuUsername,
                  'authenticationPassword' => $mcuPassword,
                  'conferenceName' => $waitingRoom,
                  'participantProtocol' => 'sip',
                  'participantName' => $x,
                  'participantType' => 'ad_hoc',
                  'address' => $testURI,
                  'audioRxMuted' => true)
        ); 
    }