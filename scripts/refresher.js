/*jslint browser: true, devel: true*/
/*global $, jQuery, alert*/
//Set deployment specific variables
var participantList;
var conferenceList;
var codecList;
var waitingRoom = "";
var showIsLive;
var refreshWebTimer;
var writeConferenceTimer;
var writeParticipantTimer;
var writePanesDBTimer;
var hostID = "";
var guest1ID = "";
var guest2ID = "";
var recorderPrefix = "";

//Create variables that we need to survive automatic refreshes and button clicks
var refreshWebInterval, writeConferenceInterval, writeParticipantInterval, writePanesDBInterval;
var timeout = null;
var lastRefresh = 0;
//var currentTime = new Date().getTime();
//var showSetup = false;
var conferenceTotal;
var conferenceImportant = false;
var checkContent = [];
var openModalId = "";
checkContent[0] = "";
var callField = [];
var pauseRefresh = false;
var keyPressTimeout;
var hostPane = [[], []];
var guestPane = [[], []];
var guest2Pane = [[], []];

function layoutsDialog(dialogId) {
    "use strict";
    var el = document.getElementById(dialogId);
    el.style.visibility = (el.style.visibility === "visible") ? "hidden" : "visible";
    openModalId = (el.style.visibility === "visible") ? dialogId : "";
}

//Grabs all the data from the API response and build the tables in html
function refreshWeb(refreshType) {
	//console.log('JS refreshType: ' + refreshType);
    "use strict";
    //call refresher.php poster with "action" pressed.
    $.customPOST({action: 'refreshWeb', type: refreshType, conferenceList: conferenceList}, function (r) {
		//console.log('JS R: ' + JSON.stringify(r));
        if (r.conferenceArray && r.participantArray) {
            participantList = r.participantArray;
            conferenceList = r.conferenceArray;
			codecList = r.codecArray;
			hostPane.length = 0;
			guestPane.length = 0;
			guest2Pane.length = 0;
            var deviceQuery = r.deviceQuery, content = [], currentConference, column = 1, portsUsed = participantList.length, portsTotal = deviceQuery.videoPortAllocation[0]['count'], portsAvailable = portsTotal - portsUsed, portType = '', portAlert = '';

            //set the show is live variable to see if the show is in pre-show mode or in filming mode
            if (r.showIsLive === 'true') {
                showIsLive = true;
            } else {
                showIsLive = false;
            }

            //rename the MCU returned port mode into a human readable value
            if (deviceQuery.videoPortAllocation[0]['type'].toUpperCase() === 'HD') {
                portType = '720p';
            } else if (deviceQuery.videoPortAllocation[0]['type'].toUpperCase() === 'FULLHD') {
                portType = '1080p';
            } else if (deviceQuery.videoPortAllocation[0]['type'].toUpperCase() === 'HDPLUS') {
                portType = '720p/1080p';
            }

            //calculating the percentage of ports in use to allow operator to know capacity and limit
            if (portsUsed >= (portsTotal * 0.90)) {
                portAlert = 'redAlert';
            } else if (portsUsed >= (portsTotal * 0.80)) {
                portAlert = 'yellowAlert';
            } else {
                portAlert = 'noAlert';
            }

            //Buttons for Show setup and Tear Down
            content[0] = '<table id="conferenceTable" class="tableStyle">';
            content[0] += '<thead>';
            content[0] += '<tr class="tableHeader">';
            content[0] += '<th colspan="100">Show Setup and Teardown Options</th>';
            content[0] += '</tr>';
            content[0] += '</thead>';
            content[0] += '<tr>';
            content[0] += '<td><input class="setupAll btn" type="button" value="Setup Conferences"/></td>';
            content[0] += '<td><input class="clearPanePlacement btn" type="button" value="Reset All Panes"/></td>';
            content[0] += '<td><input class="teardown btn btn--negative" type="button" value="TEARDOWN"/></td>';
            content[0] += '<td class="' + portAlert + '">' + portsAvailable + ' ' + portType + ' Ports Available' + '</td>';

            //Check if we are in pre-show or Live mode and then have the appropriate radio button already selected
            if (showIsLive === true) {
                content[0] += '<td><input id="preShowRadio" class="preShow" type="radio" name="showIsLive" value="false"><label for="preShowRadio">Pre-Show</label></td>';
                content[0] += '<td><input id="liveRadio" class="liveShow" type="radio" name="showIsLive" value="true" checked="checked"><label for="liveRadio">Live!</label></td>';
            } else {
                content[0] += '<td><input id="preShowRadio" class="preShow" type="radio" name="showIsLive" value="false" checked="checked"><label for="preShowRadio">Pre-Show</label></td>';
                content[0] += '<td><input id="liveRadio" class="liveShow" type="radio" name="showIsLive" value="true"><label for="liveRadio">Live!</label></td>';
            }

            //Close the Setup Table
            content[0] += '</tr>';
            content[0] += '</table>';
            //content[0] += '</div>';


            //Create Participant table and the headers for the table
            content[0] += '<div class="columnTable">';
            content[0] += '<table id="conferenceTable" class="tableStyle">';
            content[0] += '<thead>';
            content[0] += '<tr>';
            content[0] += '<th class="participantName hide">ID</th>';
            content[0] += '<th class="count">#</th>';
            content[0] += '<th class="displayName">Name</th>';
            content[0] += '<th class="specialGrid">Special Layouts</th>';
            content[0] += '<th class="audioRxMuted">Mute</th>';
            content[0] += '<th class="videoRxMuted hide">videoRxMuted</th>';
            content[0] += '<th class="participantProtocol hide">Protocol</th>';
            content[0] += '<th class="participantType hide">Type</th>';
            content[0] += '<th class="conferenceName hide">Conference Name</th>';

            //Creates a column for each conference for buttons to move participants from conference to conference
            conferenceTotal = 0;
            $.each(conferenceList, function (conferenceArrayInnerKey, conferenceArrayInnerValue) {
                //console.log(r.conferenceArray);
                conferenceTotal = conferenceTotal + 1;
                content[0] += '<th class="moveTo"">Move to...</th>';
            });

            content[0] += '<th class="participantDrop">Drop</th>';
            content[0] += '</tr>';
            content[0] += '</thead>';

            //Loop through each array in alphabetical order
            $.each(conferenceList, function (conferenceArrayInnerKey, conferenceArrayInnerValue) {
                currentConference = conferenceArrayInnerValue.conferenceName;
				
                var elementCounter = 0,
                    participantCounter = 0,
                    customLayout,
                    layoutID,
                    layoutName,
                    isDisabled1,
                    isDisabled16,
                    isDisabled2,
                    isDisabled8,
                    isDisabled53,
                    isDisabled3,
                    isDisabled9,
                    isDisabled4,
                    isDisabled43,
					isDisabled25,
                    isDisabled27,
					isDisabled28,
					isDisabled49,
                    modalId,
                    titlePosition,
                    time,
                    i,
                    j,
                    conferenceName = currentConference,
                    conferenceId = conferenceArrayInnerValue.uniqueId,
                    displayName,
                    paneNumber,
                    paneLabelNumber;

                conferenceImportant = false;

                content[0] += '<tr class="tableHeader" data-conf="' + currentConference + '">';
                //Set number of participants based on current layout
                customLayout = conferenceArrayInnerValue.customLayout;
				
				isDisabled1 = '';
				isDisabled16 = '';
				isDisabled2 = '';
				isDisabled8 = '';
				isDisabled53 = '';
				isDisabled3 = '';
				isDisabled9 = '';
				isDisabled4 = '';
				isDisabled43 = '';
				isDisabled25 = '';
				isDisabled27 = '';
				isDisabled28 = '';
				isDisabled49 = '';
				
                //Print the conferences. We also mark the title position so we can append a button to it later
                layoutID = parseInt(customLayout, 10) - 1;
                if (layoutID + 1 === 1) {
                    isDisabled1 = ' disabled';
                    layoutName = '1x1';
                } else if (layoutID + 1 === 16) {
                    isDisabled16 = ' disabled';
                    layoutName = '1x2';
                } else if (layoutID + 1 === 2) {
                    isDisabled2 = ' disabled';
                    layoutName = '2x2';
                } else if (layoutID + 1 === 8) {
                    isDisabled8 = ' disabled';
                    layoutName = '3x2';
                } else if (layoutID + 1 === 53) {
                    isDisabled53 = ' disabled';
                    layoutName = '4x2';
                } else if (layoutID + 1 === 3) {
                    isDisabled3 = ' disabled';
                    layoutName = '3x3';
                } else if (layoutID + 1 === 9) {
                    isDisabled9 = ' disabled';
                    layoutName = '4x3';
                } else if (layoutID + 1 === 4) {
                    isDisabled4 = ' disabled';
                    layoutName = '4x4';
                } else if (layoutID + 1 === 43) {
                    isDisabled43 = ' disabled';
                    layoutName = '5x4';
                } else if (layoutID + 1 === 25) {
                    isDisabled25 = ' disabled';
                    layoutName = '25';
                } else if (layoutID + 1 === 27) {
					isDisabled27 = ' disabled';
                    layoutName = '27';
                } else if (layoutID + 1 === 28) {
                    isDisabled28 = ' disabled';
                    layoutName = '28';
                } else if (layoutID + 1 === 49) {
					isDisabled49 = ' disabled';
                    layoutName = '49';
                } else if (layoutID + 1 === 33) {
                    layoutName = 'Important';
                    conferenceImportant = true;
                } else if (layoutID + 1 === 23) {
                    layoutName = 'Important';
                    conferenceImportant = true;
                } else {
                    layoutName = 'Custom';
                }
                modalId = "openModal" + conferenceArrayInnerValue.uniqueId;
                content[0] += '<td class="conferenceLayout" colspan="2" data-layout="' + layoutID + '">';
                if (layoutName !== 'Important') {
                    content[0] += '<a href="#' + modalId + '" class="openLayouts" data-conf="' + currentConference + '" data-confid="' + conferenceArrayInnerValue.uniqueId + '">';
                    content[0] += '<img class="currentLayoutHeader" src="css/images/layout' + layoutName + '.png" alt="currentLayout"  onclick="layoutsDialog((\'' + modalId + '\'))"/></a>';
                } else {
                    content[0] += '<img class="currentLayoutHeader"; src="css/images/layout' + layoutName + '.png" alt="currentLayout"/>';
                }
                content[0] += '<div id="' + modalId + '" class="modalDialog">';
                content[0] += '<div>';
                content[0] += '<div class="modal-header">';
                content[0] += '<a href="#close" title="Close" class="close" onclick="layoutsDialog((\'' + modalId + '\'))">X</a>';
                content[0] += '<h3>' + currentConference + ' Layout</h2>';
                content[0] += '</div>';
                content[0] += '<div class="modal-body">';
                //content[0] += '<img class="currentLayout" src="css/images/layout' + layoutName + '.png" alt="currentLayout"/>';
                content[0] += '<div class="layoutMenu">';
                content[0] += '<div><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="1" value="1x1"' + isDisabled1 + '><img src="css/images/layout1x1.png" alt="1x1"/></button></div>';
                content[0] += '<div><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="16" value="1x2"' + isDisabled16 + '><img src="css/images/layout1x2.png" alt="1x2"/></button></div>';
                content[0] += '<div><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="2" value="2x2"' + isDisabled2 + '><img src="css/images/layout2x2.png" alt="2x2"/></button></div>';
                content[0] += '<div><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="8" value="3x2"' + isDisabled8 + '><img src="css/images/layout3x2.png" alt="3x2"/></button></div>';
                content[0] += '<div><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="53" value="4x2"' + isDisabled53 + '><img src="css/images/layout4x2.png" alt="4x2"/></button></div>';
                content[0] += '<div><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="3" value="3x3"' + isDisabled3 + '><img src="css/images/layout3x3.png" alt="3x3"/></button></div>';
                content[0] += '<div><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="9" value="4x3"' + isDisabled9 + '><img src="css/images/layout4x3.png" alt="4x3"/></button></div>';
                content[0] += '<div><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="4" value="4x4"' + isDisabled4 + '><img src="css/images/layout4x4.png" alt="4x4"/></button></div>';
                content[0] += '<div><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="43" value="5x4"' + isDisabled43 + '><img src="css/images/layout5x4.png" alt="5x4"/></button></div>';
                content[0] += '<div><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="25" value="25"' + isDisabled25 + '><img src="css/images/layout25.png" alt="25"/></button></div>';
				content[0] += '<div><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="27" value="27"' + isDisabled27 + '><img src="css/images/layout27.png" alt="27"/></button></div>';
				content[0] += '<div><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="28" value="28"' + isDisabled28 + '><img src="css/images/layout28.png" alt="28"/></button></div>';
				content[0] += '<div><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="49" value="49"' + isDisabled49 + '><img src="css/images/layout49.png" alt="49"/></button></div>';
                content[0] += '</div>';
                content[0] += '<form id="panePlacement' + conferenceArrayInnerValue.uniqueId + '" class="panePlacementForm">';

				//content[0] += '<table id="paneTable" class="tableStyle">';
								
                paneNumber = 0;

                //loop through each pane in the conference array
                $.each(conferenceArrayInnerValue.panes, function (panesArrayInnerKey, panesArrayInnerValue) {
                    paneLabelNumber = paneNumber + 1;
					//if (paneNumber % 3 === 0 || paneNumber === 0) {
					//	content[0] += '<tr>';
					//}
					//content[0] += '<td>';
                    content[0] += '<div class="paneDiv" id="pane' + paneNumber + 'Div">';
                    content[0] += '<label for="pane' + paneNumber + 'Label">Pane ' + paneLabelNumber + '</label>';
                    content[0] += '<select name="pane' + paneNumber + 'Select" class="paneSelect" id="select' + conferenceArrayInnerValue.uniqueId + paneNumber + '">';
                    //for panes set to default:
                    if (panesArrayInnerValue.type === 'default') {
                        //Add the Default, Blank and Loudest options with default selected
                        content[0] += '<option value="0" data-conf="' + conferenceName + '" data-panenumber="' + paneNumber + '" data-type="default" selected="true">Default</option>';
                        content[0] += '<option value="1" data-conf="' + conferenceName + '" data-panenumber="' + paneNumber + '" data-type="blank">Blank</option>';
                        content[0] += '<option value="2" data-conf="' + conferenceName + '" data-panenumber="' + paneNumber + '" data-type="loudest">Loudest</option>';

                        i = 3;
                        //Loop through all the participants of the conference to add them to the drop down
                        $.each(participantList, function (participantListInnerKey, participantListInnerValue) {
                            if (participantListInnerValue.conferenceName === conferenceName) {
                                if (participantListInnerValue.displayName === "_") {
                                    displayName = "Loop";
                                } else if (participantListInnerValue.displayName === "__") {
                                    displayName = "Show Feed";
                                } else {
                                    displayName = participantListInnerValue.displayName;
                                }
                                //check to see if the participant we are about to add to the dropdown is already assigned a pane
                                var assignedPane = false;
                                $.each(conferenceArrayInnerValue.panes, function (panesArrayInnerKey2, panesArrayInnerValue2) {
                                    if (panesArrayInnerValue2.type === 'participant' && participantListInnerValue.participantName === panesArrayInnerValue2.participantName) {
                                        assignedPane = true;

                                    }
                                });
                                if (assignedPane === false) {
                                    content[0] += '<option value="' + i + '" data-conf="' + conferenceName + '" data-panenumber="' + paneNumber + '" data-type="participant" data-participantType="' + participantListInnerValue.participantType + '" data-participantProtocol="' + participantListInnerValue.participantProtocol + '" data-participantName="' + participantListInnerValue.participantName + '">' + displayName + '</option>';
                                    i = i + 1;
                                }
                            }
                        });
                    //for panes set to blank:
                    } else if (panesArrayInnerValue.type === 'blank') {
                        content[0] += '<option value="0" data-conf="' + conferenceName + '" data-panenumber="' + paneNumber + '" data-type="default">Default</option>';
                        content[0] += '<option value="1" data-conf="' + conferenceName + '" data-panenumber="' + paneNumber + '" data-type="blank" selected="true">Blank</option>';
                        content[0] += '<option value="2" data-conf="' + conferenceName + '" data-panenumber="' + paneNumber + '" data-type="loudest">Loudest</option>';
                        i = 3;
                        $.each(participantList, function (participantListInnerKey, participantListInnerValue) {
                            if (participantListInnerValue.conferenceName === conferenceName) {
                                if (participantListInnerValue.displayName === "_") {
                                    displayName = "Loop";
                                } else if (participantListInnerValue.displayName === "__") {
                                    displayName = "Show Feed";
                                } else {
                                    displayName = participantListInnerValue.displayName;
                                }
                                var assignedPane = false;
                                $.each(conferenceArrayInnerValue.panes, function (panesArrayInnerKey2, panesArrayInnerValue2) {
                                    if (panesArrayInnerValue2.type === 'participant' && participantListInnerValue.participantName === panesArrayInnerValue2.participantName) {
                                        assignedPane = true;

                                    }
                                });
                                if (assignedPane === false) {
                                    content[0] += '<option value="' + i + '" data-conf="' + conferenceName + '" data-panenumber="' + paneNumber + '" data-type="participant" data-participantType="' + participantListInnerValue.participantType + '" data-participantProtocol="' + participantListInnerValue.participantProtocol + '" data-participantName="' + participantListInnerValue.participantName + '">' + displayName + '</option>';
                                    i = i + 1;
                                }
                            }
                        });
                    //for panes set to loudest
                    } else if (panesArrayInnerValue.type === 'loudest') {
                        content[0] += '<option value="0" data-conf="' + conferenceName + '" data-panenumber="' + paneNumber + '" data-type="default">Default</option>';
                        content[0] += '<option value="1" data-conf="' + conferenceName + '" data-panenumber="' + paneNumber + '" data-type="blank">Blank</option>';
                        content[0] += '<option value="2" data-conf="' + conferenceName + '" data-panenumber="' + paneNumber + '" data-type="loudest" selected="true">Loudest</option>';
                        i = 3;
                        $.each(participantList, function (participantListInnerKey, participantListInnerValue) {
                            if (participantListInnerValue.conferenceName === conferenceName) {
                                if (participantListInnerValue.displayName === "_") {
                                    displayName = "Loop";
                                } else if (participantListInnerValue.displayName === "__") {
                                    displayName = "Show Feed";
                                } else {
                                    displayName = participantListInnerValue.displayName;
                                }
                                var assignedPane = false;
                                $.each(conferenceArrayInnerValue.panes, function (panesArrayInnerKey2, panesArrayInnerValue2) {
                                    if (panesArrayInnerValue2.type === 'participant' && participantListInnerValue.participantName === panesArrayInnerValue2.participantName) {
                                        assignedPane = true;

                                    }
                                });
                                if (assignedPane === false) {
                                    content[0] += '<option value="' + i + '" data-conf="' + conferenceName + '" data-panenumber="' + paneNumber + '" data-type="participant" data-participantType="' + participantListInnerValue.participantType + '" data-participantProtocol="' + participantListInnerValue.participantProtocol + '" data-participantName="' + participantListInnerValue.participantName + '">' + displayName + '</option>';
                                    i = i + 1;
                                }
                            }
                        });
                    //for panes set to a participant
                    } else if (panesArrayInnerValue.type === 'participant') {
                        $.each(participantList, function (participantListInnerKey, participantListInnerValue) {
                            if (participantListInnerValue.participantName === panesArrayInnerValue.participantName) {
                                if (participantListInnerValue.displayName === "_") {
                                    displayName = "Loop";
                                } else if (participantListInnerValue.displayName === "__") {
                                    displayName = "Show Feed";
                                } else {
                                    displayName = participantListInnerValue.displayName;
                                }
                            }
                        });
                        content[0] += '<option value="0" data-conf="' + conferenceName + '" data-panenumber="' + paneNumber + '" data-type="default">Default</option>';
                        content[0] += '<option value="1" data-conf="' + conferenceName + '" data-panenumber="' + paneNumber + '" data-type="blank">Blank</option>';
                        content[0] += '<option value="2" data-conf="' + conferenceName + '" data-panenumber="' + paneNumber + '" data-type="loudest">Loudest</option>';
                        content[0] += '<option value="3" data-conf="' + conferenceName + '" data-panenumber="' + paneNumber + '" data-type="participant" data-participantType="' + panesArrayInnerValue.participantType + '" data-participantProtocol="' + panesArrayInnerValue.participantProtocol + '" data-participantName="' + panesArrayInnerValue.participantName + '" selected="true">' + displayName + '</option>';
                        i = 4;
                        $.each(participantList, function (participantListInnerKey, participantListInnerValue) {
                            if (participantListInnerValue.conferenceName === conferenceName && participantListInnerValue.participantName !== panesArrayInnerValue.participantName) {
                                if (participantListInnerValue.displayName === "_") {
                                    displayName = "Loop";
                                } else if (participantListInnerValue.displayName === "__") {
                                    displayName = "Show Feed";
                                } else {
                                    displayName = participantListInnerValue.displayName;
                                }
                                if (participantListInnerValue.conferenceName === conferenceName) {
                                    if (participantListInnerValue.displayName === "_") {
                                        displayName = "Loop";
                                    } else if (participantListInnerValue.displayName === "__") {
                                        displayName = "Show Feed";
                                    } else {
                                        displayName = participantListInnerValue.displayName;
                                    }
                                    var assignedPane = false;
                                    $.each(conferenceArrayInnerValue.panes, function (panesArrayInnerKey2, panesArrayInnerValue2) {
                                        if (panesArrayInnerValue2.type === 'participant' && participantListInnerValue.participantName === panesArrayInnerValue2.participantName) {
                                            assignedPane = true;

                                        }
                                    });
                                    if (assignedPane === false) {
                                        content[0] += '<option value="' + i + '" data-conf="' + conferenceName + '" data-panenumber="' + paneNumber + '" data-type="participant" data-participantType="' + participantListInnerValue.participantType + '" data-participantProtocol="' + participantListInnerValue.participantProtocol + '" data-participantName="' + participantListInnerValue.participantName + '">' + displayName + '</option>';
                                        i = i + 1;
                                    }
                                }
                            }
                        });
                    }
                    content[0] += '</select>';
                    content[0] += '</div>';
					//content[0] += '</td>';
					//if (paneLabelNumber % 3 === 0) {
					//	content[0] += '</tr>';
					//}
                    paneNumber = paneNumber + 1;
                });
				//if (paneLabelNumber % 3 !== 0) {
				//		content[0] += '</tr>';
				//}
				//content[0] += '</table>';
                content[0] += '</form>';
                content[0] += '<div style="clear:both"></div>';
                content[0] += '</div>';
                content[0] += '</div>';
                content[0] += '</div>';
                content[0] += '</td>';
                content[0] += '<td class="confTitle" colspan="' + (conferenceTotal+3) + '"><span>' + currentConference + '</span>';
				
				titlePosition = content[0].length;
                //content[0] += '<form onsubmit="return false" class="viewerToggleForm" id="viewerToggle' + conferenceArrayInnerValue.uniqueId + '">';
				/*
				var viewerConferenceID = false;
				
				if (viewerConferenceID == true) {
					content[0] += '<input class="viewerConference btn btn--negative" data-confid="' + conferenceArrayInnerValue.uniqueId + '" data-conf="' + currentConference + '" type="button" value="Viewer">';
				} else { 
					content[0] += '<input class="viewerConference btn btn--white" data-confid="' + conferenceArrayInnerValue.uniqueId + '" data-conf="' + currentConference + '" type="button" value="Viewer">';
				}
				*/
				
				//content[0] += '</form>';
                //titlePosition = content[0].length;
                content[0] += '<form onsubmit="return false" class="dialOutForm" id="dialOut' + conferenceArrayInnerValue.uniqueId + '">';
				if (callField[conferenceName] == null) {
					content[0] += '<input class="dialOutInput" id="call' + conferenceArrayInnerValue.uniqueId + '" type="text" name="call">';
				} else {
					content[0] += '<input class="dialOutInput" id="call' + conferenceArrayInnerValue.uniqueId + '" type="text" name="call" value="' + callField[currentConference] + '">';
				}
                content[0] += '<input class="dialOut btn btn--white" data-confid="' + conferenceArrayInnerValue.uniqueId + '" data-conf="' + currentConference + '" type="button" value="Call">';
				//call a recorder
				content[0] += '<input class="addRecorder btn btn--negative" data-confid="' + conferenceArrayInnerValue.uniqueId + '" data-conf="' + currentConference + '" type="button" value="Add Recorder">';
				
                content[0] += '</form>';
                content[0] += '</td>';
                content[0] += '</tr>';

                //For each conference we will loop through each participant in the participant array
                $.each(participantList, function (participantArrayInnerKey, participantArrayInnerValue) {

                    if (participantArrayInnerValue.conferenceName === currentConference) {
						
						var codecFocusParticipant = 0;
						
						if (currentConference in codecList && codecList[currentConference]['focusType'] === "participant") {
							if (participantArrayInnerValue.participantName == codecList[currentConference]['focusParticipant']) {
								codecFocusParticipant = codecList[currentConference]['focusParticipant']
								//alert(participantArrayInnerValue.participantName);
								//alert(codecFocusParticipant);
							}
						}

                        //participantCounter = participantCounter + 1;

                        if (participantArrayInnerValue.displayName === "_") {
                            content[0] += '<tr class="loop">';
                        } else if (participantArrayInnerValue.displayName === "__") {
                            content[0] += '<tr class="codec">';
                        } else if (participantArrayInnerValue.displayName.startsWith(recorderPrefix)) {
                            content[0] += '<tr class="recorder">';
                        } else {
                            participantCounter = participantCounter + 1;

                            if (participantArrayInnerValue.packetLossCritical === true) {
                                content[0] += '<tr class="participantRow redAlert">';
                            } else if (participantArrayInnerValue.packetLossWarning === true) {
                                content[0] += '<tr class="participantRow yellowAlert">';
                            } else {
                                content[0] += '<tr class="participantRow">';
                            }
                        }

                        //For each participant, we will loop through each piece of data in the sub-array for that participant
                        $.each(participantArrayInnerValue, function (participantArraySubI, participantArraySubObject) {
                            elementCounter = elementCounter + 1;

                            //These fields get printed to the table, but the "hide" tag is added to each so they are not visible
                            if (participantArraySubI === 'participantName' || participantArraySubI === 'participantProtocol' || participantArraySubI === 'participantType' || participantArraySubI === 'conferenceName' || participantArraySubI === 'videoRxMuted' || participantArraySubI === 'cpLayout') {
                                content[0] += '<td class="' + participantArraySubI + ' hide">' + participantArraySubObject + '</td>';

                            } else if (participantArraySubI === 'pane') {
                                content[0] += '<td class="paneNumber">' + participantArraySubObject + '</td>';
                            } else if (participantArraySubI === 'audioRxMuted') {
								//Start TD for Icons
								content[0] += '<td class="muteimages"><div class="mutebuttons">';
								
								if (!participantArrayInnerValue.displayName.startsWith(recorderPrefix)) {								
								//Important status
									if (participantArrayInnerValue.important === false || participantArrayInnerValue.important === '0') {
										content[0] += '<span class="importantCommand icon icon-star icon-2x" value="markImportant" data-conf="' + currentConference + '"></span>';
									} else {
										//alert("Important Person!");
										content[0] += '<span class="importantCommand importantTrue icon icon-star icon-2x" value="unmarkImportant" data-conf="' + currentConference + '"></span>';
									}
									
									//Codec Fullscreen
									if (codecFocusParticipant == 0) {
										content[0] += '<span class="fullscreenCommand icon icon-fullscreen icon-2x" value="markFullscreen" data-conf="' + currentConference + '"></span>';
									} else {
										//alert(codecFocusParticipant);
										content[0] += '<span class="fullscreenCommand fullscreenTrue icon icon-fullscreen icon-2x" value="unmarkFullscreen" data-conf="' + currentConference + '"></span>';
									}
								}

                                //Set mute images appropriately based on the response from the API. This should a participants current audio and video mute status
                                if (participantArraySubObject === false || participantArraySubObject === '0') {
                                    content[0] += '<span class="muteCommand icon icon-microphone icon-2x" value="Mute" action="audioRXmute" data-conf="' + currentConference + '"></span>';
                                } else {
                                    content[0] += '<span class="muteCommand icon icon-mute icon-2x" value="Unmute" action="audioRXunmute" data-conf="' + currentConference + '"></span>';
                                }

                                if (participantArrayInnerValue.audioTxMuted === false || participantArrayInnerValue.audioTxMuted === '0') {
                                     content[0] += '<span class="muteCommand icon icon-audio icon-2x" value="Mute" action="audioTXmute" data-conf="' + currentConference + '"></span>';
                                } else {
                                    content[0] += '<span class="muteCommand icon icon-volume-cross icon-2x" value="Unmute" action="audioTXunmute" data-conf="' + currentConference + '"></span>';
                                }

                                if (participantArrayInnerValue.videoRxMuted === false || participantArrayInnerValue.videoRxMuted === '0') {
                                    content[0] += '<span class="muteCommand icon icon-video icon-2x" value="Mute" action="videoRXmute" data-conf="' + currentConference + '"></span>';
                                } else {
                                    content[0] += '<span class="muteCommand icon icon-video-cross icon-2x" value="Unmute" action="videoRXunmute" data-conf="' + currentConference + '"></span>';
                                }

                                if (participantArrayInnerValue.videoTxMuted === false || participantArrayInnerValue.videoTxMuted === '0') {
                                    content[0] += '<span class="muteCommand icon icon-view-preview-telepresence icon-2x" value="Mute" action="videoTXmute" data-conf="' + currentConference + '"></span>';
                                } else {
                                    content[0] += '<span class="muteCommand icon icon-view-preview-telepresence icon-view-preview-telepresence-muted icon-2x" value="Unmute" action="videoTXunmute" data-conf="' + currentConference + '"></span>';
                                }
								
								content[0] += '</div><div class="clearfloat"></div></td>';

                            } else if (participantArraySubI === 'displayName') {
                                //Check for special character names for Loops and Codecs to treat them properly
                                var participantNameOveride = participantArraySubObject;

                                if (participantArraySubObject === "_") {
                                    participantNameOveride = 'Loop';
                                } else if (participantArraySubObject === "__") {
                                    participantNameOveride = 'Codec';
                                } else if (participantArraySubObject.startsWith(recorderPrefix)) {
                                    participantNameOveride = 'Recorder (' + participantArraySubObject + ')';
                                } else if (participantArraySubObject.startsWith(hostID)) {
									//Match specific caller ID to know if its the Host
                                    participantNameOveride = 'Host (' + participantArraySubObject + ')';
									hostPane[conferenceArrayInnerValue.uniqueId] = [];
									hostPane[conferenceArrayInnerValue.uniqueId]["participantName"] = participantArrayInnerValue.participantName;
									hostPane[conferenceArrayInnerValue.uniqueId]["participantProtocol"] = participantArrayInnerValue.participantProtocol;
									hostPane[conferenceArrayInnerValue.uniqueId]["participantType"] = participantArrayInnerValue.participantType;
                                } else if (participantArraySubObject.startsWith(guest1ID)) {
									participantNameOveride = 'Special Guest 1 (' + participantArraySubObject + ')';
									guest2Pane[conferenceArrayInnerValue.uniqueId] = [];
									guest2Pane[conferenceArrayInnerValue.uniqueId]["participantName"] = participantArrayInnerValue.participantName;
									guest2Pane[conferenceArrayInnerValue.uniqueId]["participantProtocol"] = participantArrayInnerValue.participantProtocol;
									guest2Pane[conferenceArrayInnerValue.uniqueId]["participantType"] = participantArrayInnerValue.participantType;
                                } else if (participantArraySubObject.startsWith(guest2ID)) {
									participantNameOveride = 'Special Guest 2 (' + participantArraySubObject + ')';
									guestPane[conferenceArrayInnerValue.uniqueId] = [];
									guestPane[conferenceArrayInnerValue.uniqueId]["participantName"] = participantArrayInnerValue.participantName;
									guestPane[conferenceArrayInnerValue.uniqueId]["participantProtocol"] = participantArrayInnerValue.participantProtocol;
									guestPane[conferenceArrayInnerValue.uniqueId]["participantType"] = participantArrayInnerValue.participantType;
								} else {
                                    //For non-codec and non-loop participants
                                    //Is the current participant important?

                                    if (participantArrayInnerValue.important === true || participantArrayInnerValue.important === '1') {
                                        var participantMarked = "reset";
                                    } else {
                                        var participantMarked = "";
                                    }
                                }

                                //If the current layout is NOT 1 (single participant), then offer the focus button to make a user important
                                if (participantNameOveride != 'Loop' && participantNameOveride != 'Codec' && currentConference != waitingRoom) {
                                    if (layoutID + 1 === 2 || layoutID + 1 === 3 || layoutID + 1 === 8) {
										content[0] += '<td class="' + participantArraySubI + '" data-participantname="' + participantArrayInnerValue.participantName + '" data-panenumber="' + participantArrayInnerValue.pane + '" data-displayName="' + participantArrayInnerValue.displayName + '" data-participantprotocol="' + participantArrayInnerValue.participantProtocol + '" data-participanttype="' + participantArrayInnerValue.participantType + '" data-conferencename="' + participantArrayInnerValue.conferenceName + '">' + participantNameOveride + '</td><td><input class="setImportantParticipant btn btn--primary" type="button" value="Special Grid"/><input class="setFocusParticipant btn btn--primary" type="button" value="Single Pane"/></td>';
                                    } else if (layoutID + 1 === 33 || layoutID + 1 === 23) {
                                        if (participantMarked === "reset") {
                                            content[0] += '<td class="' + participantArraySubI + '" data-participantname="' + participantArrayInnerValue.participantName + '" data-panenumber="' + participantArrayInnerValue.pane + '" data-displayName="' + participantArrayInnerValue.displayName + '" data-participantprotocol="' + participantArrayInnerValue.participantProtocol + '" data-participanttype="' + participantArrayInnerValue.participantType + '" data-conferencename="' + participantArrayInnerValue.conferenceName + '">' + participantNameOveride + '</td><td><input class="' + participantMarked + 'specialLayout btn btn--meetings" type="button" value="Reset View"/></td>';
                                        } else {
                                            content[0] += '<td class="' + participantArraySubI + '" data-participantname="' + participantArrayInnerValue.participantName + '" data-panenumber="' + participantArrayInnerValue.pane + '" data-displayName="' + participantArrayInnerValue.displayName + '" data-participantprotocol="' + participantArrayInnerValue.participantProtocol + '" data-participanttype="' + participantArrayInnerValue.participantType + '" data-conferencename="' + participantArrayInnerValue.conferenceName + '">' + participantNameOveride + '</td><td><input class="' + participantMarked + 'specialLayout btn btn--primary" type="button" value="Switch Focus"/></td>';
                                        }
									} else if ((layoutID + 1 === 1 || layoutID + 1 === 16) && participantArrayInnerValue.displayName.startsWith(recorderPrefix)) {
										//If this is a recorder, then offer the view options
										content[0] += '<td class="' + participantArraySubI + '" data-participantname="' + participantArrayInnerValue.participantName + '" data-panenumber="' + participantArrayInnerValue.pane + '" data-displayName="' + participantArrayInnerValue.displayName + '" data-participantprotocol="' + participantArrayInnerValue.participantProtocol + '" data-participanttype="' + participantArrayInnerValue.participantType + '" data-conferencename="' + participantArrayInnerValue.conferenceName + '" data-confid="' + conferenceArrayInnerValue.uniqueId + '">' + participantNameOveride + '</td>';
										content[0] += '<td>';
										//Add buttons
										if (hostPane[conferenceArrayInnerValue.uniqueId] && guestPane[conferenceArrayInnerValue.uniqueId] && guest2Pane[conferenceArrayInnerValue.uniqueId]) {
											content[0] += '<input class="setRecordView btn btn--primary" type="button" data-action="split" value="Split"/>';
											content[0] += '<input class="setRecordView btn btn--primary" type="button" data-action="triple" value="Triple"/>';
											content[0] += '<input class="setRecordView btn btn--primary" type="button" data-action="host" value="Host"/>';
											content[0] += '<input class="setRecordView btn btn--primary" type="button" data-action="guest" value="Guest"/>';
											content[0] += '<input class="setRecordView btn btn--primary" type="button" data-action="default" value="Default"/>';
										} else if (hostPane[conferenceArrayInnerValue.uniqueId] && guestPane[conferenceArrayInnerValue.uniqueId]) {
											content[0] += '<input class="setRecordView btn btn--primary" type="button" data-action="split" value="Split"/>';
											content[0] += '<input class="setRecordView btn btn--primary" type="button" data-action="host" value="Host"/>';
											content[0] += '<input class="setRecordView btn btn--primary" type="button" data-action="guest" value="Guest"/>';
											content[0] += '<input class="setRecordView btn btn--primary" type="button" data-action="default" value="Default"/>';
										}
										content[0] += '</td>';
                                    } else if (layoutID + 1 === 1 || layoutID + 1 === 16) {
										//If single pane layout confernece, no need to offer single pane button
										content[0] += '<td class="' + participantArraySubI + '" data-participantname="' + participantArrayInnerValue.participantName + '" data-panenumber="' + participantArrayInnerValue.pane + '" data-displayName="' + participantArrayInnerValue.displayName + '" data-participantprotocol="' + participantArrayInnerValue.participantProtocol + '" data-participanttype="' + participantArrayInnerValue.participantType + '" data-conferencename="' + participantArrayInnerValue.conferenceName + '">' + participantNameOveride + '</td><td></td>';
                                    } else {
                                        content[0] += '<td class="' + participantArraySubI + '" data-participantname="' + participantArrayInnerValue.participantName + '" data-panenumber="' + participantArrayInnerValue.pane + '" data-displayName="' + participantArrayInnerValue.displayName + '" data-participantprotocol="' + participantArrayInnerValue.participantProtocol + '" data-participanttype="' + participantArrayInnerValue.participantType + '" data-conferencename="' + participantArrayInnerValue.conferenceName + '">' + participantNameOveride + '</td><td><input class="setFocusParticipant btn btn--primary" style="width: 250px" type="button" value="Single Pane"/></td>';
                                    }
                                } else {
									if (guestPane[conferenceArrayInnerValue.uniqueId] && guest2Pane[conferenceArrayInnerValue.uniqueId] && layoutID + 1 === 1 && participantNameOveride === 'Codec') {
										content[0] += '<td class="' + participantArraySubI + '" data-participantname="' + participantArrayInnerValue.participantName + '" data-panenumber="' + participantArrayInnerValue.pane + '" data-displayName="' + participantArrayInnerValue.displayName + '" data-participantprotocol="' + participantArrayInnerValue.participantProtocol + '" data-participanttype="' + participantArrayInnerValue.participantType + '" data-conferencename="' + participantArrayInnerValue.conferenceName + '" data-confid="' + conferenceArrayInnerValue.uniqueId + '">' + participantNameOveride + '</td><td><input class="setTwoGuest btn btn--primary" type="button" value="Two Guest"/></td>';
									} else {
										content[0] += '<td class="' + participantArraySubI + '" data-participantname="' + participantArrayInnerValue.participantName + '" data-panenumber="' + participantArrayInnerValue.pane + '" data-displayName="' + participantArrayInnerValue.displayName + '" data-participantprotocol="' + participantArrayInnerValue.participantProtocol + '" data-participanttype="' + participantArrayInnerValue.participantType + '" data-conferencename="' + participantArrayInnerValue.conferenceName + '">' + participantNameOveride + '</td><td></td>';
									}
                                }
                            }
                        });
                        //For each conference, we create a button to move that participant to the NOT-CURRENT conference. For the current conference, loops, and codecs, we do not offer transfer buttons.
                        $.each(r.conferenceArray, function (conferenceArrayInnerKey, conferenceArrayInnerValue) {
                            $.each(conferenceArrayInnerValue, function (conferenceArraySubI, conferenceArraySubObject) {
                                if (conferenceArraySubObject !== currentConference && conferenceArraySubI === "conferenceName") {
                                    if (participantArrayInnerValue.displayName === "_") {
                                        content[0] += '<td class="loopConference"></td>';
                                    } else if (participantArrayInnerValue.displayName === "__") {
                                        content[0] += '<td class="loopConference"></td>';
                                    } else if (participantArrayInnerValue.displayName === "Recorder") {
                                        content[0] += '<td class="loopConference"></td>';
                                    } else if (conferenceImportant === true) {
                                        content[0] += '<td class="participant"><input class="btn disabled" type="button" value="' + conferenceArraySubObject + '"/></td>';
                                    } else {
                                        content[0] += '<td class="participant"><input class="btn transfer' + conferenceArraySubI + '" type="button" value="' + conferenceArraySubObject + '"/></td>';
                                    }
                                } else if (conferenceArraySubObject === currentConference && conferenceArraySubI === "conferenceName") {
                                    content[0] += '<td class="participant"><input class="btn disabled" type="button" value="' + conferenceArraySubObject + '"/></td>';
                                }
                            });
                        });

                        //Creates a drop button for each participant including Loops and Codecs
                        content[0] += '<td class="dropCell"><span class="drop icon icon-exit-contain icon-2x"></span></td>';
                        content[0] += '</tr>';

                    }

                });
                if (participantCounter > 1) {
                    content[0] += '<tr class="allUsers">';
                    content[0] += '<td></td>';
                    content[0] += '<td>ALL PARTICIPANTS</td>';
                    content[0] += '<td></td>';
                    //TD for Mute All icons
                    content[0] += '<td class="muteimages">';
                    content[0] += '<div class="mutebuttons muteAllParticipants">';
                    content[0] += '<span class="muteAllCommand icon icon-microphone icon-2x" value="Mute" action="audioRXunmuteAll" data-conf="' + currentConference + '"></span>';

                    content[0] += '<span class="muteAllCommand icon icon-audio icon-2x" value="Mute" action="audioTXunmuteAll" data-conf="' + currentConference + '"></span>';

                    content[0] += '<span class="muteAllCommand icon icon-video icon-2x" value="Mute" action="videoRXunmuteAll" data-conf="' + currentConference + '"></span>';

                    content[0] += '<span class="muteAllCommand icon icon-view-preview-telepresence icon-2x" value="Mute" action="videoTXunmuteAll" data-conf="' + currentConference + '"></span>';

                    content[0] += '</div><div class="clearfloat"></div>';
                    content[0] += '<div class="mutebuttons">';
                    content[0] += '<span class="muteAllCommand icon icon-mute icon-2x" value="Unmute" action="audioRXmuteAll" data-conf="' + currentConference + '"></span>';

                    content[0] += '<span class="muteAllCommand icon icon-volume-cross icon-2x" value="Unmute" action="audioTXmuteAll" data-conf="' + currentConference + '"></span>';

                    content[0] += '<span class="muteAllCommand icon icon-video-cross icon-2x" value="Unmute" action="videoRXmuteAll" data-conf="' + currentConference + '"></span>';

                    content[0] += '<span class="muteAllCommand icon icon-view-preview-telepresence icon-view-preview-telepresence-muted icon-2x" value="Unmute" action="videoTXmuteAll" data-conf="' + currentConference + '"></span>';

                    content[0] += '</div><div class="clearfloat"></div></td>';
                    content[0] += '</td>';
                    //For each conference, create a Transfer ALL buttons
                    $.each(conferenceList, function (conferenceArrayInnerKey, conferenceArrayInnerValue) {
                        //$.each(conferenceArrayInnerValue, function (conferenceArraySubI, conferenceArraySubObject) {
                        if (conferenceArrayInnerKey !== currentConference) {
                            if (conferenceImportant === true) {
                                content[0] += '<td class="participant"><input class="btn disabled" type="button" data-conf="' + currentConference + '" value="' + conferenceArrayInnerValue.conferenceName + '"/></td>';
                            } else {
                                content[0] += '<td class="participant"><input class="transferAll btn" type="button" data-conf="' + currentConference + '" value="' + conferenceArrayInnerValue.conferenceName + '"/></td>';
                            }
                        } else if (conferenceArrayInnerKey === currentConference) {
                            content[0] += '<td class="currentConference"><input class="btn disabled" type="button" value="' + conferenceArrayInnerValue.conferenceName + '"/></td>';
                        }
                        //});
                    });

                    //Creates a drop button for each participant including Loops and Codecs
                    //content[0] += '<td class="dropCell"><input class="dropAll" type="button" data-conf="' + currentConference + '" value="DROP ALL"/></td>';
                    content[0] += '<td class="dropCell"><span class="dropAll icon icon-exit-contain icon-2x" data-conf="' + currentConference + '"></span></td>';
                    content[0] += '</tr>';
                }
                //conferenceCounts[currentConference] = participantCounter;

                //If there were no elements in the conference then we will print a Conference Empty statement
                if (elementCounter === 0) {
                    content[0] += '<tr class="conferenceEmpty">';
                    content[0] += '<td colspan="20">Conference empty!</td>';
                    content[0] += '</tr>';
                }

            });

            //Close out the table
            content[0] += '</table>';
            content[0] += '</div>';
            content[0] += r.appVersion;

            var refreshState = [];
            refreshState[0] = 'I did not refresh';

            //See if anything has changed. If not, don't update. If there is a change, update.
            if (checkContent[0] == '' || checkContent[0] !== content[0]) {
				if (pauseRefresh == false) {
					$('#mainSlice').empty();
					//Push the content variable which contains all the HTML to the mainslice
					$('#mainSlice').append(content[0]);
					refreshState[0] = 'I did refresh';
					refreshState[1] = checkContent[0];
					refreshState[2] = content[0];
					if (openModalId !== "") {
						layoutsDialog(openModalId);
					}
					checkContent[0] = content[0];
				}
            }
            //console.log(refreshState);
        }

        if (r.debugArray !== '') {
            $('#participantList').html('');
            $('#participantList').append(JSON.stringify(r.debugArray));
        }
        if (r.alert) {
            console.log(r.alert);
        }
		
    });
}

//New Transfer Command that takes one or more participants to move and moves them all
function transferParticipants(scrubbedParticipantList, sourceConference, sourceType, destinationConference, destType) {
    "use strict";
        //post this information to refresher.php to take action
    $.ajax({
        type: "POST",
        url: "refresher.php",
        data: {action: "transfer", scrubbedParticipantList: scrubbedParticipantList, sourceConference: sourceConference, sourceType: sourceType, destinationConference: destinationConference, destType: destType},
        dataType: "json",
        cache: false,

        success: function (r) {
            if (r.alert) {
                console.log(r.alert);
            }
        }
    });
}

//New Mute Command that takes one or more participants to mute in a single action
function muteParticipants(scrubbedParticipantList, conferenceName, muteChannel, muteAction) {
    "use strict";

    //post this information to refresher.php to take action
    $.ajax({
        type: "POST",
        url: "refresher.php",
        data: {action: "muteCommand", scrubbedParticipantList: scrubbedParticipantList, conferenceName: conferenceName, muteChannel: muteChannel, muteAction: muteAction},
        dataType: "json",
        cache: false,

        success: function (r) {
            if (r.alert) {
                console.log(r.alert);
            }
        }
    });
}

//Function set either the important or focus view
function setSpecialLayout(scrubbedParticipantList, participantName, participantProtocol, participantType, conferenceName, layoutType) {
    "use strict";

    //post this information to refresher.php to take action
    $.ajax({
        type: "POST",
        url: "refresher.php",
        data: {action: "setSpecialLayout", scrubbedParticipantList: scrubbedParticipantList, participantName: participantName, participantProtocol: participantProtocol, participantType: participantType, conferenceName: conferenceName, layoutType: layoutType},
        dataType: "json",
        cache: false,

        success: function (r) {
            if (r.alert) {
                console.log(r.alert);
            }
        }
    });
}

//Function to tell refresher.php to update the DB with all participant information
function writeParticipantEnumerate() {
    "use strict";
	
    //post this information to refresher.php to take action
    $.ajax({
        type: "POST",
        url: "refresher.php",
        data: {action: "writeParticipantEnumerate"},
        dataType: "json",
        cache: false,

        success: function (r) {
            if (r.alert) {
                console.log(r.alert);
            }
        }
    });
}

//Function to tell refresher.php to update the DB with all conference information
function writeConferenceEnumerate() {
    "use strict";

    //post this information to refresher.php to take action
    $.ajax({
        type: "POST",
        url: "refresher.php",
        data: {action: "writeConferenceEnumerate"},
        dataType: "json",
        cache: false,

        success: function (r) {
            if (r.alert) {
                console.log(r.alert);
            }
        }
    });
}

//Function to tell refresher.php to update the DB with all paneplacement information
function writePanesDB() {
    "use strict";

    //post this information to refresher.php to take action
    $.ajax({
        type: "POST",
        url: "refresher.php",
        data: {action: "writePanesDB", conferenceList: conferenceList},
        dataType: "json",
        cache: false,

        success: function (r) {
            if (r.alert) {
                console.log(r.alert);
            }
        }
    });
}

//Sends post info to refresher.php
$.customPOST = function (data, callback) {
    "use strict";
    $.post('refresher.php', data, callback, 'json');
	//console.log('data: ' + JSON.stringify(data));
};

//Catches all button clicks on the page
$(document).ready(function () {
    "use strict";
    var i;
	
    //Read DB settings and set variables here
    $.ajax({
        type: "POST",
        url: "refresher.php",
        data: {action: "readAllSettings"},
        dataType: "json",
        cache: false,

        success: function (r) {
            if (r.alert) {
                console.log(r.alert);
            } else {
				waitingRoom = r.settings.waitingRoom.value;
                refreshWebTimer = r.settings.timerWebRefresh.value;
                writeConferenceTimer = r.settings.timerConferencesDB.value;
                writeParticipantTimer = r.settings.timerParticipantsDB.value;
                writePanesDBTimer = r.settings.timerPanePlacementDB.value;
				//Get host and guest
				hostID = r.settings.hostID.value;
				guest1ID = r.settings.guest1ID.value;
				guest2ID = r.settings.guest2ID.value;
				recorderPrefix = r.settings.recorderPrefix.value;
				
                refreshWeb('first');

                //Set the interval for how often we want the page to refresh
				refreshWebInterval = setInterval(function () {
					refreshWeb('refresh');
				}, refreshWebTimer);


                writeConferenceInterval = setInterval(writeConferenceEnumerate, writeConferenceTimer);
                writeParticipantInterval = setInterval(writeParticipantEnumerate, writeParticipantTimer);
                writePanesDBInterval = setInterval(writePanesDB, writePanesDBTimer);
            }
        }
    });

    //Transfer a single participant
    $(document).on('mousedown', '.transferconferenceName', function () {
        var participantName = $(this).closest("tr").find(".displayName").data("participantname"), sourceConference = $(this).closest("tr").find(".displayName").data("conferencename"), destinationConference = $(this).attr("value"), scrubbedParticipantList = {}, sourceType, sourceLayout, destLayout, destType;

        //build a new scrubbed variable that contains only the participant information from the conference which we are moving participants
        $.each(participantList, function (participantListInnerKey, participantListInnerValue) {
            if (participantListInnerValue.participantName == participantName) {
                scrubbedParticipantList[participantListInnerValue.participantName] = {
                    'participantName' : participantListInnerValue.participantName,
                    'participantProtocol' : participantListInnerValue.participantProtocol,
                    'participantType' : participantListInnerValue.participantType,
                    'displayName' : participantListInnerValue.displayName,
                    'pane' : participantListInnerValue.pane
                };

            }
        });

        //Find the layouts of the source and destination conference and mark them appropriately
        sourceLayout = $('.tableHeader[data-conf="' + $(this).closest("tr").find(".displayName").data("conferencename") + '"]').find(".conferenceLayout").data("layout");
        destLayout = $('.tableHeader[data-conf="' + $(this).val() + '"]').find(".conferenceLayout").data("layout");

        //Create values for both sourceType and destType variables
        if (sourceConference === waitingRoom) {
            sourceType = "waiting";
        } else {
            if (sourceLayout === 0) {
                sourceType = "focus";
            } else if (sourceLayout === 22 || sourceLayout === 32) {
                sourceType = "special";
            } else if (sourceLayout === 26 || sourceLayout === 24) {
                sourceType = "eyeline";
            } else {
                sourceType = "grid";
            }
        }

        if (destinationConference === waitingRoom) {
            destType = "waiting";
        } else {
            if (destLayout === 0) {
                destType = "focus";
            } else if (destLayout === 22 || destLayout === 32) {
                destType = "special";
            } else if (destLayout === 26 || destLayout === 24) {
                destType = "eyeline";
            } else {
                destType = "grid";
            }
        }

        if (destType !== "special") {
            transferParticipants(scrubbedParticipantList, sourceConference, sourceType, destinationConference, destType);
        } else {
            alert("Can not move participants into a conference set to Special Mode. Change the destination conference to a standard layout and then try again.");
        }
        
    });

    //Transfer ALL participants in a conference
    $(document).on('mousedown', '.transferAll', function () {
        var sourceConference = $(this).data("conf"), destinationConference = $(this).attr("value"), scrubbedParticipantList = {}, sourceType, sourceLayout, destLayout, destType;

        //build a new scrubbed variable that contains only the participant information from the conference which we are moving participants
        $.each(participantList, function (participantListInnerKey, participantListInnerValue) {
            if (participantListInnerValue.conferenceName === sourceConference && participantListInnerValue.displayName !== "_" && participantListInnerValue.displayName !== "__") {
                scrubbedParticipantList[participantListInnerValue.participantName] = {
                    'participantName' : participantListInnerValue.participantName,
                    'participantProtocol' : participantListInnerValue.participantProtocol,
                    'participantType' : participantListInnerValue.participantType,
                    'displayName' : participantListInnerValue.displayName,
                    'pane' : participantListInnerValue.pane
                };
            }
        });

        //Find the layouts of the source and destination conference and mark them appropriately
        sourceLayout = $('.tableHeader[data-conf="' + $(this).closest("tr").find(".displayName").data("conferencename") + '"]').find(".conferenceLayout").data("layout");
        destLayout = $('.tableHeader[data-conf="' + $(this).val() + '"]').find(".conferenceLayout").data("layout");

        //Create values for both sourceType and destType variables
        if (sourceConference === waitingRoom) {
            sourceType = "waiting";
        } else {
            if (sourceLayout === 0) {
                sourceType = "focus";
            } else {
                sourceType = "grid";
            }
        }

        if (destinationConference === waitingRoom) {
            destType = "waiting";
        } else {
            if (destLayout === 0) {
                destType = "focus";
            } else if (destLayout === 22 || destLayout === 32) {
                destType = "special";
            } else {
                destType = "grid";
            }
        }

        //Present a warning popup and require a confirmation
        if (destType !== "special") {
            if (confirm('This will move ALL participants from "' + sourceConference + '" to "' + destinationConference + '". Are you sure?')) {
                transferParticipants(scrubbedParticipantList, sourceConference, sourceType, destinationConference, destType);
            }
        } else {
            alert("Can not move participants into a conference set to Special Mode. Change the destination conference to a standard layout and then try again.");
        }

    });

    //Drop a participant
    $(document).on('mousedown', '.drop', function () {
        //find the closest td with the info we need for a move and store in a var
        var participantName = $(this).closest("tr").find(".displayName").data("participantname"), participantProtocol = $(this).closest("tr").find(".displayName").data("participantprotocol"), participantType = $(this).closest("tr").find(".displayName").data("participanttype"), conferenceName = $(this).closest("tr").find(".displayName").data("conferencename");
		
        //post this information to refresher.php to take action
        $.ajax({
            type: "POST",
            url: "refresher.php",
            data: {action: "drop", participantName: participantName, participantProtocol: participantProtocol, participantType: participantType, conferenceName: conferenceName},
            dataType: "json",
            cache: false,

            success: function (r) {
                if (r.alert) {
                    alert(r.alert);
                }
            }
        });
    });

    //Mute command that encompasses muting, unmuting, and codec muting into one onclick function
    $(document).on('mousedown', '.muteCommand', function () {

        var participantName = $(this).closest("tr").find(".displayName").data("participantname"), conferenceName = $(this).data("conf"), muteCommand = $(this).attr("action"), muteAction, muteChannel, scrubbedParticipantList = {};

        //build a new scrubbed variable that contains only the participant information from the conference which we are moving participants
        $.each(participantList, function (participantListInnerKey, participantListInnerValue) {
            if (participantListInnerValue.participantName == participantName) {
                scrubbedParticipantList[participantListInnerValue.participantName] = {
                    'participantName' : participantListInnerValue.participantName,
                    'participantProtocol' : participantListInnerValue.participantProtocol,
                    'participantType' : participantListInnerValue.participantType,
                    'displayName' : participantListInnerValue.displayName,
                    'pane' : participantListInnerValue.pane
                };

            }
        });

        if (muteCommand === 'audioRXmute') {
            muteAction = 'mute';
            muteChannel = 'audioRxMuted';
        } else if (muteCommand === 'audioRXunmute') {
            muteAction = 'unmute';
            muteChannel = 'audioRxMuted';
        } else if (muteCommand === 'audioTXmute') {
            muteAction = 'mute';
            muteChannel = 'audioTxMuted';
        } else if (muteCommand === 'audioTXunmute') {
            muteAction = 'unmute';
            muteChannel = 'audioTxMuted';
        } else if (muteCommand === 'videoRXmute') {
            muteAction = 'mute';
            muteChannel = 'videoRxMuted';
        } else if (muteCommand === 'videoRXunmute') {
            muteAction = 'unmute';
            muteChannel = 'videoRxMuted';
        } else if (muteCommand === 'videoTXmute') {
            muteAction = 'mute';
            muteChannel = 'txAll';
        } else if (muteCommand === 'videoTXunmute') {
            muteAction = 'unmute';
            muteChannel = 'txAll';
        }

        muteParticipants(scrubbedParticipantList, conferenceName, muteChannel, muteAction);
    });

    $(document).on('mousedown', '.muteAllCommand', function () {

        var conferenceName = $(this).data("conf"), muteCommand = $(this).attr("action"), muteAction, muteChannel, scrubbedParticipantList = {};

        //build a new scrubbed variable that contains only the participant information from the conference which we are moving participants
        $.each(participantList, function (participantListInnerKey, participantListInnerValue) {
            if (participantListInnerValue.conferenceName === conferenceName && participantListInnerValue.displayName !== "_" && participantListInnerValue.displayName !== "__") {
                scrubbedParticipantList[participantListInnerValue.participantName] = {
                    'participantName' : participantListInnerValue.participantName,
                    'participantProtocol' : participantListInnerValue.participantProtocol,
                    'participantType' : participantListInnerValue.participantType
                };
            }
        });

        if (muteCommand === 'audioRXmuteAll') {
            muteAction = 'mute';
            muteChannel = 'audioRxMuted';
        } else if (muteCommand === 'audioRXunmuteAll') {
            muteAction = 'unmute';
            muteChannel = 'audioRxMuted';
        } else if (muteCommand === 'audioTXmuteAll') {
            muteAction = 'mute';
            muteChannel = 'audioTxMuted';
        } else if (muteCommand === 'audioTXunmuteAll') {
            muteAction = 'unmute';
            muteChannel = 'audioTxMuted';
        } else if (muteCommand === 'videoRXmuteAll') {
            muteAction = 'mute';
            muteChannel = 'videoRxMuted';
        } else if (muteCommand === 'videoRXunmuteAll') {
            muteAction = 'unmute';
            muteChannel = 'videoRxMuted';
        } else if (muteCommand === 'videoTXmuteAll') {
            muteAction = 'mute';
            muteChannel = 'txAll';
        } else if (muteCommand === 'videoTXunmuteAll') {
            muteAction = 'unmute';
            muteChannel = 'txAll';
        }

        muteParticipants(scrubbedParticipantList, conferenceName, muteChannel, muteAction);
    });

    //Set the layout for the grid conference
    $(document).on('mousedown', '.layout', function () {
        var layoutNumber = $(this).data("layout"), conferenceName = $(this).data("conf"), modalId = $(this).parent().parent().parent().parent().attr('id'), conferenceId = $(this).parent().parent().parent().parent().parent().data("confid"), displayName;

        //post this information to refresher.php to take action
        $.ajax({
            type: "POST",
            url: "refresher.php",
            data: {action: "changeLayout", conferenceName: conferenceName, layoutNumber: layoutNumber},
            dataType: "json",
            cache: false,

            success: function (r) {
                if (r.alert) {
                    console.log(r.alert);
                }
            }
        });
    });

    //Initiate dial out
    $(document).on('mousedown', '.dialOut', function () {
		pauseRefresh = false;
        var conferenceName = $(this).data("conf"), confId = $(this).data("confid"), callNumber = $('#call' + confId).val();
		
		if (callNumber !== "") {
		
			callField = "";
		
			//post this information to refresher.php to take action
			$.ajax({
				type: "POST",
				url: "refresher.php",
				data: {action: "call", conferenceName: conferenceName, callNumber: callNumber},
				dataType: "json",
				cache: false,

				success: function (r) {
					if (r.alert) {
						console.log(r.alert);
						//console.log(callNumber);
					}
				}
			});
		}
    });

	$(document).on("focus click", '.dialOutInput', function (e) {
		pauseRefresh = true;
		
		if (keyPressTimeout) {
			clearTimeout(keyPressTimeout);
		}
		
		keyPressTimeout = setTimeout(function(){
			pauseRefresh = false;
		}, 2000);
	});

    $(document).on("keyup", '.dialOutInput', function (e) {
        /* ENTER PRESSED*/
        pauseRefresh = true;

		if (keyPressTimeout) {
			clearTimeout(keyPressTimeout);
		}
		
		keyPressTimeout = setTimeout(function(){
			pauseRefresh = false;
		}, 2000);
		
		var callNumber = $(this).val(), conferenceName = $(this).parent().find(".dialOut").data("conf");
		
		if (e.keyCode === 13) {
			if (callNumber !== "") {
				var confId = $(this).parent().find(".dialOut").data("confid");
				//reset call field value
				callField[conferenceName] = "";
				
				//post this information to refresher.php to take action
				$.ajax({
					type: "POST",
					url: "refresher.php",
					data: {action: "call", conferenceName: conferenceName, callNumber: callNumber},
					dataType: "json",
					cache: false,

					success: function (r) {
						if (r.alert) {
							console.log(r.alert);
						}
					}
				});
			}
        } else if (e.keyCode == 8 || e.keyCode == 109 || e.keyCode == 110 || (e.keyCode >= 46 || e.keyCode <= 90) || (e.keyCode >= 96 || e.keyCode <= 105)) {
			callField[conferenceName] = callNumber;
			//alert(callNumber);
		}
    });
	
	$(document).on('mousedown', '.addRecorder', function () {
		pauseRefresh = false;
        var conferenceName = $(this).data("conf"), confId = $(this).data("confid"), callNumber = $('#call' + confId).val();
		
		if (callNumber !== "") {
			callField = "";

			//post this information to refresher.php to take action
			$.ajax({
				type: "POST",
				url: "refresher.php",
				data: {action: "addRecorder", conferenceName: conferenceName, callNumber: callNumber, recorderPrefix: recorderPrefix},
				dataType: "json",
				cache: false,

				success: function (r) {
					if (r.alert) {
						console.log(r.alert);
					}
				}
			});
		}
    });
	
    //Set the recorder view to split view with Host as important
    $(document).on('mousedown', '.setRecordView', function () {
        var conferenceName = $(this).closest("tr").find(".displayName").data("conferencename"),confID = $(this).closest("tr").find(".displayName").data("confid"), recorderName = $(this).closest("tr").find(".displayName").data("participantname"), recorderType = $(this).closest("tr").find(".displayName").data("participanttype"), recorderProtocol = $(this).closest("tr").find(".displayName").data("participantprotocol"), view = $(this).data("action");
		
		if (!guest2Pane[confID]) {
			guest2Pane[confID] = [];
			guest2Pane[confID]["participantName"] = "";
			guest2Pane[confID]["participantProtocol"] = "";
			guest2Pane[confID]["participantType"] = "";
		}
		
		//post this information to refresher.php to take action
        if (hostPane[confID]["participantName"] && guestPane[confID]["participantName"]) {
            $.ajax({
                type: "POST",
                url: "refresher.php",
                data: {action: "setRecordView", view: view, conferenceName: conferenceName, hostName: hostPane[confID]["participantName"], hostType: hostPane[confID]["participantType"], hostProtocol: hostPane[confID]["participantProtocol"], guestName: guestPane[confID]["participantName"], guestType: guestPane[confID]["participantType"], guestProtocol: guestPane[confID]["participantProtocol"], guest2Name: guest2Pane[confID]["participantName"], guest2Type: guest2Pane[confID]["participantType"], guest2Protocol: guest2Pane[confID]["participantProtocol"], recorderName: recorderName, recorderType: recorderType, recorderProtocol: recorderProtocol},
                dataType: "json",
                cache: false,

                success: function (r) {
                    if (r.alert) {
                        console.log(r.alert);
                        //alert(r.alert);
                    }
                }
            });
        } else {
			alert ("Can not set recorder view. Both a host and guest are required to be in the conference.");
		}
    });
	
	//Set the recorder view to split view with Host as important
    $(document).on('mousedown', '.setTwoGuest', function () {
        var conferenceName = $(this).closest("tr").find(".displayName").data("conferencename"),confID = $(this).closest("tr").find(".displayName").data("confid");

		//post this information to refresher.php to take action
        if (guestPane[confID] && guest2Pane[confID] && guestPane[confID]["participantName"] && guest2Pane[confID]["participantName"]) {
            $.ajax({
                type: "POST",
                url: "refresher.php",
                data: {action: "setTwoGuest", conferenceName: conferenceName, guestName: guestPane[confID]["participantName"], guestType: guestPane[confID]["participantType"], guestProtocol: guestPane[confID]["participantProtocol"], guest2Name: guest2Pane[confID]["participantName"], guest2Type: guest2Pane[confID]["participantType"], guest2Protocol: guest2Pane[confID]["participantProtocol"]},
                dataType: "json",
                cache: false,

                success: function (r) {
                    if (r.alert) {
                        console.log(r.alert);
                        //alert(r.alert);
                    }
                }
            });
        } else {
			alert ("Can not set Two Guest view without two guests, duh!");
		}
    });

	//Set a participant important
    $(document).on('mousedown', '.importantCommand', function () {

		var participantName = $(this).closest("tr").find(".displayName").data("participantname"), participantProtocol = $(this).closest("tr").find(".displayName").data("participantprotocol"), participantType = $(this).closest("tr").find(".displayName").data("participanttype"), conferenceName = $(this).data("conf"), mark = $(this).attr("value"), importantBool = true;
		
		if (mark == "markImportant") {
			importantBool = true;
		} else {
			importantBool = false;
		}
		
		//alert(importantBool + " " + participantName + " " + participantProtocol + " " + participantType + " " + conferenceName);
		
		//post this information to refresher.php to take action
		$.ajax({
			type: "POST",
			url: "refresher.php",
			data: {action: "markImportant", participantName: participantName, participantProtocol: participantProtocol, participantType: participantType, conferenceName: conferenceName, importantBool: importantBool},
			dataType: "json",
			cache: false,

			success: function (r) {
				if (r.alert) {
					console.log(r.alert);
					//alert(r.alert);
				}
			}
		});
    });
	
	//Set conference codec fullscreen to a selected participant
    $(document).on('mousedown', '.fullscreenCommand', function () {

		var participantName = $(this).closest("tr").find(".displayName").data("participantname"), participantProtocol = $(this).closest("tr").find(".displayName").data("participantprotocol"), participantType = $(this).closest("tr").find(".displayName").data("participanttype"), conferenceName = $(this).data("conf"), mark = $(this).attr("value"), fullscreenBool = true;
		
		if (mark == "markFullscreen") {
			fullscreenBool = true;
		} else {
			fullscreenBool = false;
		}
		
		//post this information to refresher.php to take action
		$.ajax({
			type: "POST",
			url: "refresher.php",
			data: {action: "markFullscreen", participantName: participantName, participantProtocol: participantProtocol, participantType: participantType, conferenceName: conferenceName, fullscreenBool: fullscreenBool},
			dataType: "json",
			cache: false,

			success: function (r) {
				if (r.alert) {
					console.log(r.alert);
					//alert(r.alert);
				}
			}
		});
    });

    //Drop all participants from a conference
    $(document).on('mousedown', '.dropAll', function () {
        var conferenceName = $(this).data("conf");
        //post this information to refresher.php to take action
        if (confirm('This will DROP all participants from "' + conferenceName + '"! Are you sure?')) {
            $.ajax({
                type: "POST",
                url: "refresher.php",
                data: {action: "dropAll", conferenceName: conferenceName, participantList: participantList},
                dataType: "json",
                cache: false,

                success: function (r) {
                    if (r.alert) {
                        console.log(r.alert);
                        //alert(r.alert);
                    }
                }
            });
        }
    });

    //Teardown all participants, loops, and codecs from all conferences
    $(document).on('mousedown', '.teardown', function () {
        var conferenceName = $(this).data("conf");
        //post this information to refresher.php to take action
        if (confirm('This will DROP all participants from ALL conferences! Are you sure?')) {
            $.ajax({
                type: "POST",
                url: "refresher.php",
                data: {action: "teardown", conferenceList: conferenceList, participantList: participantList},
                dataType: "json",
                cache: false,

                success: function (r) {
                    if (r.alert) {
                        console.log(r.alert);
                    }
                }
            });
        }
    });

    //This adds all codecs and loops to the required conferences
    $(document).on('mousedown', '.setupAll', function () {
        //post this information to refresher.php to take action
        //if (confirm('This will ADD all codecs and loops to ALL conferences! Are you sure?')) {
        $.ajax({
            type: "POST",
            url: "refresher.php",
            data: {action: "setupAll", conferenceList: conferenceList, participantList: participantList},
            dataType: "json",
            cache: false,

            success: function (r) {
                if (r.alert) {
                    console.log(r.alert);
                }
            }
        });
        //}
    });

    //This resets all panes in the MCU
    $(document).on('mousedown', '.clearPanePlacement', function () {
        //post this information to refresher.php to take action
        if (confirm('This will reset all pane placement information and WILL change the video layout! Are you sure?')) {
            $.ajax({
                type: "POST",
                url: "refresher.php",
                data: {action: "clearPanePlacement", conferenceList: conferenceList},
                dataType: "json",
                cache: false,

                success: function (r) {
                    if (r.alert) {
                        console.log(r.alert);
                    }
                }
            });
        }
    });

    //This sets the default layout to allow all participants to see each other
    $(document).on('click', '.preShow', function () {
        var conferenceName = $(this).data("conf");
        //post this information to refresher.php to take action
        $.ajax({
            type: "POST",
            url: "refresher.php",
            data: {action: "preShow", conferenceList: conferenceList, participantList: participantList},
            dataType: "json",
            cache: false,

            success: function (r) {
                if (r.alert) {
                    console.log(r.alert);
                }
            }
        });
    });

    //This sets the default layout so all participants see only the Codec
    $(document).on('click', '.liveShow', function () {
        var conferenceName = $(this).data("conf");
        //post this information to refresher.php to take action
		
		//alert(JSON.stringify(conferenceList));

        $.ajax({
            type: "POST",
            url: "refresher.php",
            data: {action: "liveShow", conferenceList: conferenceList, participantList: participantList},
            dataType: "json",
            cache: false,

            success: function (r) {
                if (r.alert) {
                    console.log(r.alert);
                }
            }
        });

    });

    $(document).on('change', '.paneSelect', function () {
        var conferenceName = $(this).find('option:selected').data("conf"), pane = $(this).find('option:selected').data("panenumber"), type = $(this).find('option:selected').data("type"), participantType, participantProtocol, participantName, displayName;
        if (type === "default" || type === "blank" || type === "loudest") {
             //post this information to refresher.php to take action
            $.ajax({
                type: "POST",
                url: "refresher.php",
                data: {action: "modifyPane", conferenceName: conferenceName, pane: pane, type: type},
                dataType: "json",
                cache: false,

                success: function (r) {
                    if (r.alert) {
                        console.log(r.alert);
                    }
                }
            });
        } else if (type === "participant") {
            participantType = $(this).find('option:selected').data("participanttype");
            participantProtocol = $(this).find('option:selected').data("participantprotocol");
            participantName = $(this).find('option:selected').data("participantname");
            displayName = $(this).find('option:selected').text();
            //post this information to refresher.php to take action
            $.ajax({
                type: "POST",
                url: "refresher.php",
                data: {action: "modifyPane", conferenceName: conferenceName, pane: pane, type: type, participantType: participantType, participantProtocol: participantProtocol, participantName: participantName, displayName: displayName},
                dataType: "json",
                cache: false,

                success: function (r) {
                    if (r.alert) {
                        console.log(r.alert);
                    }
                }
            });
        }

    });

    //Switch to important view within a codec
    $(document).on('mousedown', '.setImportantParticipant', function () {
        var participantName = $(this).closest("tr").find(".displayName").data("participantname"), participantProtocol = $(this).closest("tr").find(".displayName").data("participantprotocol"), participantType = $(this).closest("tr").find(".displayName").data("participanttype"), conferenceName = $(this).closest("tr").find(".displayName").data("conferencename"), scrubbedParticipantList = {}, layoutType = "important", currentPane = parseInt($(this).closest("tr").find(".displayName").data("panenumber"));

        //build a new scrubbed variable that contains only the participant information from the conference which we are moving participants
        $.each(participantList, function (participantListInnerKey, participantListInnerValue) {
            if (participantListInnerValue.conferenceName === conferenceName && participantListInnerValue.displayName !== "_" && participantListInnerValue.displayName !== "__") {
                scrubbedParticipantList[participantListInnerValue.participantName] = {
                    'participantName' : participantListInnerValue.participantName,
                    'participantProtocol' : participantListInnerValue.participantProtocol,
                    'participantType' : participantListInnerValue.participantType,
                    'displayName' : participantListInnerValue.displayName,
                    'pane' : participantListInnerValue.pane
                };

            }
        });

        setSpecialLayout(scrubbedParticipantList, participantName, participantProtocol, participantType, conferenceName, layoutType);

    });

    $(document).on('mousedown', '.setFocusParticipant', function () {
        var participantName = $(this).closest("tr").find(".displayName").data("participantname"), participantProtocol = $(this).closest("tr").find(".displayName").data("participantprotocol"), participantType = $(this).closest("tr").find(".displayName").data("participanttype"), conferenceName = $(this).closest("tr").find(".displayName").data("conferencename"), scrubbedParticipantList = {}, layoutType = "focus", currentPane = parseInt($(this).closest("tr").find(".displayName").data("panenumber"));

        //build a new scrubbed variable that contains only the participant information from the conference which we are moving participants
        $.each(participantList, function (participantListInnerKey, participantListInnerValue) {
            if (participantListInnerValue.conferenceName === conferenceName && participantListInnerValue.displayName !== "_" && participantListInnerValue.displayName !== "__") {
                scrubbedParticipantList[participantListInnerValue.participantName] = {
                    'participantName' : participantListInnerValue.participantName,
                    'participantProtocol' : participantListInnerValue.participantProtocol,
                    'participantType' : participantListInnerValue.participantType,
                    'displayName' : participantListInnerValue.displayName,
                    'pane' : participantListInnerValue.pane
                };

            }
        });

        setSpecialLayout(scrubbedParticipantList, participantName, participantProtocol, participantType, conferenceName, layoutType);

    });

    $(document).on('mousedown', '.specialLayout', function () {
        var participantName = $(this).closest("tr").find(".displayName").data("participantname"), participantProtocol = $(this).closest("tr").find(".displayName").data("participantprotocol"), participantType = $(this).closest("tr").find(".displayName").data("participanttype"), conferenceName = $(this).closest("tr").find(".displayName").data("conferencename"), scrubbedParticipantList = {}, layoutType = "", currentPane = parseInt($(this).closest("tr").find(".displayName").data("panenumber"));

        //build a new scrubbed variable that contains only the participant information from the conference which we are moving participants
        $.each(participantList, function (participantListInnerKey, participantListInnerValue) {
            if (participantListInnerValue.conferenceName === conferenceName && participantListInnerValue.displayName !== "_" && participantListInnerValue.displayName !== "__") {
                scrubbedParticipantList[participantListInnerValue.participantName] = {
                    'participantName' : participantListInnerValue.participantName,
                    'participantProtocol' : participantListInnerValue.participantProtocol,
                    'participantType' : participantListInnerValue.participantType,
                    'displayName' : participantListInnerValue.displayName,
                    'pane' : participantListInnerValue.pane
                };

            }
        });

        if (currentPane == 0) {
            layoutType = "transferFocus";
        } else {
            layoutType = "important";
        }


        setSpecialLayout(scrubbedParticipantList, participantName, participantProtocol, participantType, conferenceName, layoutType);

    });

    //Go back to default view from important view
    $(document).on('mousedown', '.resetspecialLayout', function () {
        var participantName = $(this).closest("tr").find(".displayName").data("participantname"), participantProtocol = $(this).closest("tr").find(".displayName").data("participantprotocol"), participantType = $(this).closest("tr").find(".displayName").data("participanttype"), conferenceName = $(this).closest("tr").find(".displayName").data("conferencename"), scrubbedParticipantList = {};

        //build a new scrubbed variable that contains only the participant information from the conference which we are moving participants
        $.each(participantList, function (participantListInnerKey, participantListInnerValue) {
            if (participantListInnerValue.conferenceName === conferenceName && participantListInnerValue.displayName !== "_" && participantListInnerValue.displayName !== "__") {
                scrubbedParticipantList[participantListInnerValue.participantName] = {
                    'participantName' : participantListInnerValue.participantName,
                    'participantProtocol' : participantListInnerValue.participantProtocol,
                    'participantType' : participantListInnerValue.participantType,
                    'displayName' : participantListInnerValue.displayName,
                    'pane' : participantListInnerValue.pane
                };
            }
        });

        $.ajax({
            type: "POST",
            url: "refresher.php",
            data: {action: "resetSpecialLayout", scrubbedParticipantList: scrubbedParticipantList, participantName: participantName, participantProtocol: participantProtocol, participantType: participantType, conferenceName: conferenceName},
            dataType: "json",
            cache: false,

            success: function (r) {
                if (r.alert) {
                    console.log(r.alert);
                }
            }
        });

    });

    $("*").dblclick(function(e){
        e.preventDefault();
    });

});
