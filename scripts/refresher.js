/*jslint browser: true, devel: true*/
/*global $, jQuery, alert*/
//Set deployment specific variables
var participantList;
var conferenceList;
var waitingRoom = "";
var showIsLive;

//Create variables that we need to survive automatic refreshes and button clicks
var refreshInterval;
var timeout = null;
var lastRefresh = 0;
var currentTime = new Date().getTime();
//var showSetup = false;
var conferenceTotal;
var conferenceImportant = false;
var checkContent = [];
var openModalId = "";
checkContent[0] = "";

function panePlacementDropdowns(panePlacement, conferenceName, conferenceId, displayName) {
    'use strict';
    var i;
    $.each(panePlacement.panes, function (panesArrayInnerKey, panesArrayInnerValue) {
        if (panesArrayInnerValue.type === 'default') {
            $("#select" + conferenceId + panesArrayInnerKey)
                .find('option')
                .remove()
                .end()
                .append($("<option></option>")
                    .attr("value", 0)
                    .attr("data-conf", conferenceName)
                    .attr("data-panenumber", panesArrayInnerKey)
                    .attr("data-type", "default")
                    .text("Default")
                    .prop('selected', true));
            $("#select" + conferenceId + panesArrayInnerKey)
                .append($("<option></option>")
                    .attr("value", 1)
                    .attr("data-conf", conferenceName)
                    .attr("data-panenumber", panesArrayInnerKey)
                    .attr("data-type", "blank")
                    .text("Blank"));
            $("#select" + conferenceId + panesArrayInnerKey)
                .append($("<option></option>")
                    .attr("value", 2)
                    .attr("data-conf", conferenceName)
                    .attr("data-panenumber", panesArrayInnerKey)
                    .attr("data-type", "loudest")
                    .text("Loudest"));
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
                    $("#select" + conferenceId + panesArrayInnerKey)
                        .append($("<option></option>")
                            .attr("value", i)
                            .attr("data-conf", conferenceName)
                            .attr("data-panenumber", panesArrayInnerKey)
                            .attr("data-type", "participant")
                            .attr("data-participantType", participantListInnerValue.participantType)
                            .attr("data-participantProtocol", participantListInnerValue.participantProtocol)
                            .attr("data-participantName", participantListInnerValue.participantName)
                            .text(displayName));
                    i = i + 1;
                }
            });

        } else if (panesArrayInnerValue.type === 'blank') {
            $("#select" + conferenceId + panesArrayInnerKey)
                .find('option')
                .remove()
                .end()
                .append($("<option></option>")
                    .attr("value", 0)
                    .attr("data-conf", conferenceName)
                    .attr("data-panenumber", panesArrayInnerKey)
                    .attr("data-type", "default")
                    .text("Default"));
            $("#select" + conferenceId + panesArrayInnerKey)
                .append($("<option></option>")
                    .attr("value", 1)
                    .attr("data-conf", conferenceName)
                    .attr("data-panenumber", panesArrayInnerKey)
                    .attr("data-type", "blank")
                    .text("Blank")
                    .prop('selected', true));
            $("#select" + conferenceId + panesArrayInnerKey)
                .append($("<option></option>")
                    .attr("value", 2)
                    .attr("data-conf", conferenceName)
                    .attr("data-panenumber", panesArrayInnerKey)
                    .attr("data-type", "loudest")
                    .text("Loudest"));
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
                    $("#select" + conferenceId + panesArrayInnerKey)
                        .append($("<option></option>")
                            .attr("value", i)
                            .attr("data-conf", conferenceName)
                            .attr("data-panenumber", panesArrayInnerKey)
                            .attr("data-type", "participant")
                            .attr("data-participantType", participantListInnerValue.participantType)
                            .attr("data-participantProtocol", participantListInnerValue.participantProtocol)
                            .attr("data-participantName", participantListInnerValue.participantName)
                            .text(displayName));
                    i = i + 1;
                }
            });

        } else if (panesArrayInnerValue.type === 'loudest') {
            $("#select" + conferenceId + panesArrayInnerKey)
                .find('option')
                .remove()
                .end()
                .append($("<option></option>")
                    .attr("value", 0)
                    .attr("data-conf", conferenceName)
                    .attr("data-panenumber", panesArrayInnerKey)
                    .attr("data-type", "default")
                    .text("Default"));
            $("#select" + conferenceId + panesArrayInnerKey)
                .append($("<option></option>")
                    .attr("value", 1)
                    .attr("data-conf", conferenceName)
                    .attr("data-panenumber", panesArrayInnerKey)
                    .attr("data-type", "blank")
                    .text("Blank"));
            $("#select" + conferenceId + panesArrayInnerKey)
                .append($("<option></option>")
                    .attr("value", 2)
                    .attr("data-conf", conferenceName)
                    .attr("data-panenumber", panesArrayInnerKey)
                    .attr("data-type", "loudest")
                    .text("Loudest")
                    .prop('selected', true));
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
                    $("#select" + conferenceId + panesArrayInnerKey)
                        .append($("<option></option>")
                            .attr("value", i)
                            .attr("data-conf", conferenceName)
                            .attr("data-panenumber", panesArrayInnerKey)
                            .attr("data-type", "participant")
                            .attr("data-participantType", participantListInnerValue.participantType)
                            .attr("data-participantProtocol", participantListInnerValue.participantProtocol)
                            .attr("data-participantName", participantListInnerValue.participantName)
                            .text(displayName));
                    i = i + 1;
                }
            });

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
            $("#select" + conferenceId + panesArrayInnerKey)
                .find('option')
                .remove()
                .end()
                .append($("<option></option>")
                    .attr("value", 0)
                    .attr("data-conf", conferenceName)
                    .attr("data-panenumber", panesArrayInnerKey)
                    .attr("data-type", "default")
                    .text("Default"));
            $("#select" + conferenceId + panesArrayInnerKey)
                .append($("<option></option>")
                    .attr("value", 2)
                    .attr("data-conf", conferenceName)
                    .attr("data-panenumber", panesArrayInnerKey)
                    .attr("data-type", "blank")
                    .text("Blank"));
            $("#select" + conferenceId + panesArrayInnerKey)
                .append($("<option></option>")
                    .attr("value", 3)
                    .attr("data-conf", conferenceName)
                    .attr("data-panenumber", panesArrayInnerKey)
                    .attr("data-type", "loudest")
                    .text("Loudest"));
            $("#select" + conferenceId + panesArrayInnerKey)
                .append($("<option></option>")
                    .attr("value", 4)
                    .attr("data-conf", conferenceName)
                    .attr("data-panenumber", panesArrayInnerKey)
                    .attr("data-type", "participant")
                    .attr("data-participanttype", panesArrayInnerValue.participantType)
                    .attr("data-participantprotocol", panesArrayInnerValue.participantProtocol)
                    .attr("data-participantname", panesArrayInnerValue.participantName)
                    .text(displayName)
                    .prop('selected', true));
            i = 5;
            $.each(participantList, function (participantListInnerKey, participantListInnerValue) {
                if (participantListInnerValue.conferenceName === conferenceName && participantListInnerValue.participantName !== panesArrayInnerValue.participantName) {
                    if (participantListInnerValue.displayName === "_") {
                        displayName = "Loop";
                    } else if (participantListInnerValue.displayName === "__") {
                        displayName = "Show Feed";
                    } else {
                        displayName = participantListInnerValue.displayName;
                    }
                    $("#select" + conferenceId + panesArrayInnerKey)
                        .append($("<option></option>")
                            .attr("value", i)
                            .attr("data-conf", conferenceName)
                            .attr("data-panenumber", panesArrayInnerKey)
                            .attr("data-type", "participant")
                            .attr("data-participantType", participantListInnerValue.participantType)
                            .attr("data-participantProtocol", participantListInnerValue.participantProtocol)
                            .attr("data-participantName", participantListInnerValue.participantName)
                            .text(displayName));
                    i = i + 1;
                }
            });
        }
    });
}

$('head').append('<link rel="stylesheet" href="css/base.css" type="text/css" />');
var refreshTimer = 800;
var refreshPreview = 10000;

function layoutsDialog(dialogId) {
    "use strict";
    var el = document.getElementById(dialogId);
    el.style.visibility = (el.style.visibility === "visible") ? "hidden" : "visible";
    openModalId = (el.style.visibility === "visible") ? dialogId : "";
}

//Grabs all the data from the API response and build the tables in html
function appRefresh(refreshType) {
    "use strict";
    //call refresher.php poster with "action" pressed.
    $.customPOST({action: 'refresh', type: refreshType}, function (r) {
        if (r.conferenceArray && r.participantArray) {
            participantList = r.participantArray;
            conferenceList = r.conferenceArray;
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
                portType = '720p60';
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
            content[0] += '<td colspan="100">Show Setup and Teardown Options</td>';
            content[0] += '</tr>';
            content[0] += '</thead>';
            content[0] += '<tr>';
            content[0] += '<td><input class="setupAll" type="button" value="Setup Conferences"/></td>';
            content[0] += '<td><input class="clearPanePlacement" type="button" value="Reset All Panes"/></td>';
            content[0] += '<td><input class="teardown" type="button" value="TEARDOWN"/></td>';
			content[0] += '<td class="' + portAlert + '">' + portsAvailable + ' ' + portType + ' Ports Available' + '</td>';
			
            //Check if we are in pre-show or Live mode and then have the appropriate radio button already selected
            if (showIsLive === true) {
                content[0] += '<td><input class="preShow" type="radio" name="showIsLive" value="false">Pre-Show</td>';
                content[0] += '<td><input class="liveShow" type="radio" name="showIsLive" value="true" checked="checked">Live!</td>';
            } else {
                content[0] += '<td><input class="preShow" type="radio" name="showIsLive" value="false" checked="checked">Pre-Show</td>';
                content[0] += '<td><input class="liveShow" type="radio" name="showIsLive" value="true">Live!</td>';
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
            //content[0] += '<th class="participantPreview">Preview</th>';
            content[0] += '<th class="count">#</th>';
            content[0] += '<th class="displayName">Name</th>';
            content[0] += '<th class="specialGrid">Special Layouts</th>';
            content[0] += '<th class="audioRxMuted">Mute</th>';
            content[0] += '<th class="videoRxMuted hide">videoRxMuted</th>';
            //content[0] += '<th class="videoTxMuted hide">videoRxMuted</th>';
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

                //Print the conferences. We also mark the title position so we can append a button to it later
                layoutID = parseInt(customLayout, 10) - 1;
                if (layoutID + 1 === 1) {
                    isDisabled1 = ' disabled';
                    isDisabled16 = '';
                    isDisabled2 = '';
                    isDisabled8 = '';
                    isDisabled53 = '';
                    isDisabled3 = '';
                    isDisabled9 = '';
                    isDisabled4 = '';
                    isDisabled43 = '';
                    layoutName = '1x1';
                } else if (layoutID + 1 === 16) {
                    isDisabled1 = '';
                    isDisabled16 = ' disabled';
                    isDisabled2 = '';
                    isDisabled8 = '';
                    isDisabled53 = '';
                    isDisabled3 = '';
                    isDisabled9 = '';
                    isDisabled4 = '';
                    isDisabled43 = '';
                    layoutName = '1x2';
                } else if (layoutID + 1 === 2) {
                    isDisabled1 = '';
                    isDisabled16 = '';
                    isDisabled2 = ' disabled';
                    isDisabled8 = '';
                    isDisabled53 = '';
                    isDisabled3 = '';
                    isDisabled9 = '';
                    isDisabled4 = '';
                    isDisabled43 = '';
                    layoutName = '2x2';
                } else if (layoutID + 1 === 8) {
                    isDisabled8 = ' disabled';
                    isDisabled1 = '';
                    isDisabled16 = '';
                    isDisabled2 = '';
                    isDisabled53 = '';
                    isDisabled3 = '';
                    isDisabled9 = '';
                    isDisabled4 = '';
                    isDisabled43 = '';
                    layoutName = '3x2';
                } else if (layoutID + 1 === 53) {
                    isDisabled53 = ' disabled';
                    isDisabled1 = '';
                    isDisabled16 = '';
                    isDisabled8 = '';
                    isDisabled2 = '';
                    isDisabled3 = '';
                    isDisabled9 = '';
                    isDisabled4 = '';
                    isDisabled43 = '';
                    layoutName = '4x2';
                } else if (layoutID + 1 === 3) {
                    isDisabled3 = ' disabled';
                    isDisabled1 = '';
                    isDisabled16 = '';
                    isDisabled8 = '';
                    isDisabled53 = '';
                    isDisabled2 = '';
                    isDisabled9 = '';
                    isDisabled4 = '';
                    isDisabled43 = '';
                    layoutName = '3x3';
                } else if (layoutID + 1 === 9) {
                    isDisabled9 = ' disabled';
                    isDisabled1 = '';
                    isDisabled16 = '';
                    isDisabled8 = '';
                    isDisabled53 = '';
                    isDisabled3 = '';
                    isDisabled2 = '';
                    isDisabled4 = '';
                    isDisabled43 = '';
                    layoutName = '4x3';
                } else if (layoutID + 1 === 4) {
                    isDisabled4 = ' disabled';
                    isDisabled1 = '';
                    isDisabled16 = '';
                    isDisabled8 = '';
                    isDisabled53 = '';
                    isDisabled3 = '';
                    isDisabled9 = '';
                    isDisabled2 = '';
                    isDisabled43 = '';
                    layoutName = '4x4';
                } else if (layoutID + 1 === 43) {
                    isDisabled43 = ' disabled';
                    isDisabled1 = '';
                    isDisabled16 = '';
                    isDisabled8 = '';
                    isDisabled53 = '';
                    isDisabled3 = '';
                    isDisabled9 = '';
                    isDisabled4 = '';
                    isDisabled2 = '';
                    layoutName = '5x4';
                } else if (layoutID + 1 === 33) {
                    isDisabled43 = '';
                    isDisabled1 = '';
                    isDisabled16 = '';
                    isDisabled8 = '';
                    isDisabled53 = '';
                    isDisabled3 = '';
                    isDisabled9 = '';
                    isDisabled4 = '';
                    isDisabled2 = '';
                    layoutName = 'Important';
                    conferenceImportant = true;
                } else if (layoutID + 1 === 23) {
                    isDisabled43 = '';
                    isDisabled1 = '';
                    isDisabled16 = '';
                    isDisabled8 = '';
                    isDisabled53 = '';
                    isDisabled3 = '';
                    isDisabled9 = '';
                    isDisabled4 = '';
                    isDisabled2 = '';
                    layoutName = 'Important';
                    conferenceImportant = true;
                } else {
                    isDisabled1 = '';
                    isDisabled16 = '';
                    isDisabled43 = '';
                    isDisabled8 = '';
                    isDisabled53 = '';
                    isDisabled3 = '';
                    isDisabled9 = '';
                    isDisabled4 = '';
                    isDisabled2 = '';
                    layoutName = 'Custom';
                }
                modalId = "openModal" + conferenceArrayInnerValue.uniqueId;
                content[0] += '<td class="conferenceLayout" colspan="4" data-layout="' + layoutID + '">';
                if (layoutName !== 'Important') {
                    content[0] += '<a href="#' + modalId + '" class="openLayouts" data-conf="' + currentConference + '" data-confid="' + conferenceArrayInnerValue.uniqueId + '">';
                    content[0] += '<img class="currentLayoutHeader" src="css/images/layout' + layoutName + '.png" alt="currentLayout" height="40" onclick="layoutsDialog((\'' + modalId + '\'))"/></a>';
                } else {
                    content[0] += '<img class="currentLayoutHeader"; src="css/images/layout' + layoutName + '.png" alt="currentLayout" height="40"/>';
                }
                content[0] += '<div id="' + modalId + '" class="modalDialog">';
                content[0] += '<div>';
                
                content[0] += '<a href="#close" title="Close" class="close" onclick="layoutsDialog((\'' + modalId + '\'))">X</a>';
                content[0] += '<h2>' + currentConference + ' Layout</h2>';
                content[0] += '<img class="currentLayout" src="css/images/layout' + layoutName + '.png" alt="currentLayout"/>';
                content[0] += '<ul class="layoutMenu">';
                content[0] += '<li><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="1" value="1x1"' + isDisabled1 + '><img src="css/images/layout1x1.png" alt="1x1" height="40"/></button></li>';
                content[0] += '<li><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="16" value="1x2"' + isDisabled16 + '><img src="css/images/layout1x2.png" alt="1x2" height="40"/></button></li>';
                content[0] += '<li><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="2" value="2x2"' + isDisabled2 + '><img src="css/images/layout2x2.png" alt="2x2" height="40"/></button></li>';
                content[0] += '<li><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="8" value="3x2"' + isDisabled8 + '><img src="css/images/layout3x2.png" alt="3x2" height="40"/></button></li>';
                content[0] += '<li><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="53" value="4x2"' + isDisabled53 + '><img src="css/images/layout4x2.png" alt="4x2" height="40"/></button></li>';
                content[0] += '<li><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="3" value="3x3"' + isDisabled3 + '><img src="css/images/layout3x3.png" alt="3x3" height="40"/></button></li>';
                content[0] += '<li><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="9" value="4x3"' + isDisabled9 + '><img src="css/images/layout4x3.png" alt="4x3" height="40"/></button></li>';
                content[0] += '<li><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="4" value="4x4"' + isDisabled4 + '><img src="css/images/layout4x4.png" alt="4x4" height="40"/></button></li>';
                content[0] += '<li><button class="layout" data-conf="' + currentConference + '" type="button" data-layout="43" value="5x4"' + isDisabled43 + '><img src="css/images/layout5x4.png" alt="5x4" height="40"/></button></li>';
                content[0] += '</ul>';
                content[0] += '<form id="panePlacement">';


                //post this information to refresher.php to take action

                paneNumber = 0;

                //loop through each pane in the conference array
                $.each(conferenceArrayInnerValue.panes, function (panesArrayInnerKey, panesArrayInnerValue) {
                    paneLabelNumber = paneNumber + 1;
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
                    paneNumber = paneNumber + 1;
                });

                content[0] += '</form>';
                content[0] += '<div style="clear:both"></div>';
                content[0] += '</div>';
                content[0] += '</div>';
                content[0] += '</td>';
                content[0] += '<td class="confTitle" colspan="' + conferenceTotal + '"><span>' + currentConference + '</span>';
                titlePosition = content[0].length;
                content[0] += '</td>';
                content[0] += '<td class="dialOutSection" colspan="2">';
                content[0] += '<form onsubmit="return false" class="dialOutForm" id="dialOut' + conferenceArrayInnerValue.uniqueId + '">';
                content[0] += '<input class="dialOutInput" id="call' + conferenceArrayInnerValue.uniqueId + '" type="text" name="call">';
                content[0] += '<input class="dialOut" data-confid="' + conferenceArrayInnerValue.uniqueId + '" data-conf="' + currentConference + '" type="button" value="Call">';
                content[0] += '</form>';
                content[0] += '</td>';
                content[0] += '</tr>';

                //For each conference we will loop through each participant in the participant array
                $.each(participantList, function (participantArrayInnerKey, participantArrayInnerValue) {

                    if (participantArrayInnerValue.conferenceName === currentConference) {

                        //participantCounter = participantCounter + 1;

                        if (participantArrayInnerValue.displayName === "_") {
                            content[0] += '<tr class="loop">';
                        } else if (participantArrayInnerValue.displayName === "__") {
                            content[0] += '<tr class="codec">';
                        } else {
                            participantCounter = participantCounter + 1;
                            content[0] += '<tr class="participantRow">';
                        }

                        //For each participant, we will loop through each piece of data in the sub-array for that participant
                        $.each(participantArrayInnerValue, function (participantArraySubI, participantArraySubObject) {
                            elementCounter = elementCounter + 1;

                            //These fields get printed to the table, but the "hide" tag is added to each so they are not visible
                            if (participantArraySubI === 'participantName' || participantArraySubI === 'participantProtocol' || participantArraySubI === 'participantType' || participantArraySubI === 'conferenceName' || participantArraySubI === 'videoRxMuted' || participantArraySubI === 'connectionUniqueId' || participantArraySubI === 'cpLayout') {
                                content[0] += '<td class="' + participantArraySubI + ' hide">' + participantArraySubObject + '</td>';

                            } else if (participantArraySubI === 'pane') {
                                content[0] += '<td class="paneNumber">' + participantArraySubObject + '</td>';
                            } else if (participantArraySubI === 'participantPreview') {

                                //This section ensures that we only update the preview URL if we have passed the refreshPreview timeout
                                currentTime = new Date().getTime();

                                if (currentTime >= (lastRefresh + refreshPreview)) {
                                    lastRefresh = currentTime;
                                    time = lastRefresh;
                                } else {
                                    time = lastRefresh;
                                }

                                //This code adds previews to the page. Right now it is disabled until we can figure out how to download the images to the web server then host from here. Otherwise, load/latency is too high on MCU. DO NOT DELETE
                                //content[0] += '<td class="'+participantArraySubI+' hide"> <div class="preview"><img class="fullsize" src="'+participantArraySubObject+'?'+time+'" alt="Preview"><img class="thumb" width="40" height="22" src="'+participantArraySubObject+'?'+time+'" alt="Preview"> </div></td>';

                                //content[0] += '<td class="mobilehide1">' + participantCounter + '</td>';

                                //Set Pane for participant
                                //content[0] += '<td class="mobilehide">'+participantCounter+'</td>';

                            } else if (participantArraySubI === 'audioRxMuted') {

                                //if (participantArrayInnerValue.displayName == "__") {
                                    /*
                                    if(participantArrayInnerValue.audioTxMuted==true){
                                        content[0] += '<td class="muteimages"><div class="mutebuttons"><img class="muteCommand" src="./css/images/muted.png" value="Unmute" action="confUnmute" conf="'+currentConference+'">';
                                    } else {
                                        content[0] += '<td class="muteimages"><div class="mutebuttons"><img class="muteCommand" src="./css/images/unmuted.png" value="Mute" action="confMute" conf="'+currentConference+'">';
                                    }
                                    */

                                    //content[0] += '<td class="muteimages"></td>';

                                //} else {
                                    //Set mute images appropriately based on the response from the API. This should a participants current audio and video mute status
                                if (participantArraySubObject === false || participantArraySubObject === '0') {
                                    content[0] += '<td class="muteimages"><div class="mutebuttons"><img class="muteCommand" src="./css/images/unmuted.png" value="Mute" action="audioMute" data-conf="' + currentConference + '">';
                                } else {
                                    content[0] += '<td class="muteimages"><div class="mutebuttons"><img class="muteCommand" src="./css/images/muted.png" value="Unmute" action="audioUnmute" data-conf="' + currentConference + '">';
                                }

                                if (participantArrayInnerValue.videoRxMuted === false || participantArrayInnerValue.videoRxMuted === '0') {
                                    content[0] += '<img class="muteCommand" src="./css/images/video-unmuted.png" value="Mute" action="videoMute" data-conf="' + currentConference + '">';
                                } else {
                                    content[0] += '<img class="muteCommand" src="./css/images/video-muted.png" value="Unmute" action="videoUnmute" data-conf="' + currentConference + '">';
                                }

                                if (participantArrayInnerValue.videoTxMuted === false || participantArrayInnerValue.videoTxMuted === '0') {
                                    content[0] += '<img class="muteCommand" src="./css/images/videoaudiotx-unmuted.png" value="Mute" action="txMuteAll" data-conf="' + currentConference + '"></div><div class="clearfloat"></div></td>';
                                } else {
                                    content[0] += '<img class="muteCommand" src="./css/images/videoaudiotx-muted.png" value="Unmute" action="txUnmuteAll" data-conf="' + currentConference + '"></div><div class="clearfloat"></div></td>';
                                }

                                //}

                            } else if (participantArraySubI === 'videoTxMuted' || participantArraySubI === 'audioTxMuted' || participantArraySubI === 'important') {
                                // Print NOTHING
                            } else {
                                //Check for special character names for Loops and Codecs to treat them properly
                                var participantNameOveride = participantArraySubObject;
                                if (participantArraySubI === 'displayName' && participantArraySubObject === "_") {
                                    participantNameOveride = 'Loop';
                                } else if (participantArraySubI === 'displayName' && participantArraySubObject === "__") {
                                    participantNameOveride = 'Codec';
                                } else {
									//For non-codec and non-loop participants
									//Is the current participant important?
									if (participantArrayInnerValue.important === true) {
										var participantMarked = "reset";
									} else {
										var participantMarked = "";
									}
								}
								
								//If the current layout is NOT 1 (single participant), then offer the focus button to make a user important
								if (participantNameOveride != 'Loop' && participantNameOveride != 'Codec' && currentConference != waitingRoom) {
									if (layoutID + 1 === 2 || layoutID + 1 === 3 || layoutID + 1 === 8) {
                                        content[0] += '<td class="' + participantArraySubI + '" data-participantname="' + participantArrayInnerValue.participantName + '" data-panenumber="' + participantArrayInnerValue.pane + '" data-displayName="' + participantArrayInnerValue.displayName + '" data-participantprotocol="' + participantArrayInnerValue.participantProtocol + '" data-participanttype="' + participantArrayInnerValue.participantType + '" data-connectionuniqueid="' + participantArrayInnerValue.connectionUniqueId + '" data-conferencename="' + participantArrayInnerValue.conferenceName + '">' + participantNameOveride + '</td><td><input class="setImportantParticipant" type="button" value="Special Grid"/><input class="setFocusParticipant" type="button" value="Single Pane"/></td>';
                                    } else if (layoutID + 1 === 33 || layoutID + 1 === 23) {
                                        if (participantMarked === "reset") {
                                            content[0] += '<td class="' + participantArraySubI + '" data-participantname="' + participantArrayInnerValue.participantName + '" data-panenumber="' + participantArrayInnerValue.pane + '" data-displayName="' + participantArrayInnerValue.displayName + '" data-participantprotocol="' + participantArrayInnerValue.participantProtocol + '" data-participanttype="' + participantArrayInnerValue.participantType + '" data-connectionuniqueid="' + participantArrayInnerValue.connectionUniqueId + '" data-conferencename="' + participantArrayInnerValue.conferenceName + '">' + participantNameOveride + '</td><td><input class="' + participantMarked + 'specialLayout" type="button" value="Reset View"/></td>';
                                        } else {
                                            content[0] += '<td class="' + participantArraySubI + '" data-participantname="' + participantArrayInnerValue.participantName + '" data-panenumber="' + participantArrayInnerValue.pane + '" data-displayName="' + participantArrayInnerValue.displayName + '" data-participantprotocol="' + participantArrayInnerValue.participantProtocol + '" data-participanttype="' + participantArrayInnerValue.participantType + '" data-connectionuniqueid="' + participantArrayInnerValue.connectionUniqueId + '" data-conferencename="' + participantArrayInnerValue.conferenceName + '">' + participantNameOveride + '</td><td><input class="' + participantMarked + 'specialLayout" type="button" value="Switch Focus"/></td>';
                                        }
                                    } else {
                                        content[0] += '<td class="' + participantArraySubI + '" data-participantname="' + participantArrayInnerValue.participantName + '" data-panenumber="' + participantArrayInnerValue.pane + '" data-displayName="' + participantArrayInnerValue.displayName + '" data-participantprotocol="' + participantArrayInnerValue.participantProtocol + '" data-participanttype="' + participantArrayInnerValue.participantType + '" data-connectionuniqueid="' + participantArrayInnerValue.connectionUniqueId + '" data-conferencename="' + participantArrayInnerValue.conferenceName + '">' + participantNameOveride + '</td><td></td>';
                                    }
								} else {
                                    content[0] += '<td class="' + participantArraySubI + '" data-participantname="' + participantArrayInnerValue.participantName + '" data-panenumber="' + participantArrayInnerValue.pane + '" data-displayName="' + participantArrayInnerValue.displayName + '" data-participantprotocol="' + participantArrayInnerValue.participantProtocol + '" data-participanttype="' + participantArrayInnerValue.participantType + '" data-connectionuniqueid="' + participantArrayInnerValue.connectionUniqueId + '" data-conferencename="' + participantArrayInnerValue.conferenceName + '">' + participantNameOveride + '</td><td></td>';
                                }							
                            }
                        });
                        //For each conference, we create a button to move that participant to the NOT-CURRENT conference. For the current conference, loops, and codecs, we do not offer transfer buttons.
                        $.each(r.conferenceArray, function (conferenceArrayInnerKey, conferenceArrayInnerValue) {
                            $.each(conferenceArrayInnerValue, function (conferenceArraySubI, conferenceArraySubObject) {
                                if (conferenceArraySubObject !== currentConference && conferenceArraySubI === "conferenceName") {
                                    if (participantArrayInnerValue.displayName === "_") {
                                        content[0] += '<td class="loopConference">&#10008;</td>';
                                    } else if (participantArrayInnerValue.displayName === "__") {
                                        content[0] += '<td class="loopConference">&#10008;</td>';
                                    } else if (conferenceImportant === true) {
                                        //content[0] += '<td class="participant">' + conferenceArraySubObject + '</td>';
                                        content[0] += '<td class="participant"><input class="doNothing" type="button" value="' + conferenceArraySubObject + '"/></td>';
                                    } else {
                                        content[0] += '<td class="participant"><input class="transfer' + conferenceArraySubI + '" type="button" value="' + conferenceArraySubObject + '"/></td>';
                                    }
                                } else if (conferenceArraySubObject === currentConference && conferenceArraySubI === "conferenceName") {
                                    content[0] += '<td class="currentConference">' + conferenceArraySubObject + '</td>';
                                }
                            });
                        });

                        //Creates a drop button for each participant including Loops and Codecs
                        content[0] += '<td class="dropCell"><input class="drop" type="button" value="DROP"/></td>';
                        content[0] += '</tr>';

                    }

                });
                if (participantCounter > 1) {
                    content[0] += '<tr class="allUsers">';
                    content[0] += '<td></td>';
                    content[0] += '<td>ALL PARTICIPANTS</td>';
                    content[0] += '<td></td>';
                    content[0] += '<td></td>';
                    $.each(conferenceList, function (conferenceArrayInnerKey, conferenceArrayInnerValue) {
                        //$.each(conferenceArrayInnerValue, function (conferenceArraySubI, conferenceArraySubObject) {
                        if (conferenceArrayInnerKey !== currentConference) {
                            content[0] += '<td class="participant"><input class="transferAll" type="button" data-conf="' + currentConference + '" value="' + conferenceArrayInnerValue.conferenceName + '"/></td>';
                        } else if (conferenceArrayInnerKey === currentConference) {
                            content[0] += '<td class="currentConference">' + conferenceArrayInnerValue.conferenceName + '</td>';
                        }
                        //});
                    });

                    //Creates a drop button for each participant including Loops and Codecs
                    content[0] += '<td class="dropCell"><input class="dropAll" type="button" data-conf="' + currentConference + '" value="DROP ALL"/></td>';
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
			
            //See if anything has changed. If not, don't update. If there is a change, update.
            if (checkContent[0] == '' || checkContent[0] !== content[0]) {
                $('#mainSlice').empty();
                //Push the content variable which contains all the HTML to the mainslice
                $('#mainSlice').append(content[0]);
                if (openModalId !== "") {
                    layoutsDialog(openModalId);
                }
                checkContent[0] = content[0];
            }
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
            } else if (r.refresh === true) {

                clearInterval(refreshInterval);

                appRefresh('refresh');

                refreshInterval = setInterval(function () {
                    appRefresh('refresh');
                }, refreshTimer);

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
                //alert('in alert');
            } else {
                //mcuIP = r.settings.mcuIP.value;
                //wallConference = r.settings.wallConference.value;
                waitingRoom = r.settings.waitingRoom.value;
                //mobileConf2 = r.settings.mobileConf2.value;
                //mobileConf3 = r.settings.mobileConf3.value;
                //domainName = r.settings.domainName.value;
                //alert('reading settings');
                /*
                for (i = 1; i < 10; i = i + 1) {
                    var codecN = 'codec' + i;
                    codecsArray[i] = r.settings[codecN].value;
                }
                */
            }
        }
    });

    //If the page was just loaded, we will run the first initial page refresh
    if (lastRefresh === 0) {
        //appRefresh('first');
    }

    //Set the interval for how often we want the page to refresh
    refreshInterval = setInterval(function () {
        appRefresh('refresh');
    }, refreshTimer);

    //Transfer a single participant
    $(document).on('click', '.transferconferenceName', function () {
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
            } else {
                sourceType = "grid";
            }
        }

        if (destinationConference === waitingRoom) {
            destType = "waiting";
        } else {
            if (destLayout === 0) {
                destType = "focus";
            } else {
                destType = "grid";
            }
        }

        //console.log(scrubbedParticipantList);// + ' ' + sourceConference + ' ' + sourceType + ' ' + destinationConference + ' ' + destType);
        transferParticipants(scrubbedParticipantList, sourceConference, sourceType, destinationConference, destType);

    });

    //Transfer ALL participants in a conference
    $(document).on('click', '.transferAll', function () {
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
            } else {
                destType = "grid";
            }
        }

        //Present a warning popup and require a confirmation
        if (confirm('This will move ALL participants from "' + sourceConference + '" to "' + destinationConference + '". Are you sure?')) {
            transferParticipants(scrubbedParticipantList, sourceConference, sourceType, destinationConference, destType);
        }

    });

    //Drop a participant
    $(document).on('click', '.drop', function () {
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
    $(document).on('click', '.muteCommand', function () {

        var conferenceName = $(this).data("conf"), muteCommand = $(this).attr("action"), muteAction, muteChannel, participantName, participantProtocol, participantType;

        if (muteCommand === 'audioMute') {
            muteAction = 'mute';
            muteChannel = 'audioRxMuted';
            participantName = $(this).closest("tr").find(".displayName").data("participantname");
            participantProtocol = $(this).closest("tr").find(".displayName").data("participantprotocol");
            participantType = $(this).closest("tr").find(".displayName").data("participanttype");
        } else if (muteCommand === 'audioUnmute') {
            muteAction = 'unmute';
            muteChannel = 'audioRxMuted';
            participantName = $(this).closest("tr").find(".displayName").data("participantname");
            participantProtocol = $(this).closest("tr").find(".displayName").data("participantprotocol");
            participantType = $(this).closest("tr").find(".displayName").data("participanttype");
        } else if (muteCommand === 'videoMute') {
            muteAction = 'mute';
            muteChannel = 'videoRxMuted';
            participantName = $(this).closest("tr").find(".displayName").data("participantname");
            participantProtocol = $(this).closest("tr").find(".displayName").data("participantprotocol");
            participantType = $(this).closest("tr").find(".displayName").data("participanttype");
        } else if (muteCommand === 'videoUnmute') {
            muteAction = 'unmute';
            muteChannel = 'videoRxMuted';
            participantName = $(this).closest("tr").find(".displayName").data("participantname");
            participantProtocol = $(this).closest("tr").find(".displayName").data("participantprotocol");
            participantType = $(this).closest("tr").find(".displayName").data("participanttype");
        } else if (muteCommand === 'confMute') {
            muteAction = 'mute';
            muteChannel = 'audioTxMuted';
            participantName = $('tr:has(td.conferenceName:contains(' + conferenceName + ')):has(td.displayName:contains("Codec"))').find(".displayName").data("participantname");
            participantProtocol = $('tr:has(td.conferenceName:contains(' + conferenceName + ')):has(td.displayName:contains("Codec"))').find(".displayName").data("participantprotocol");
            participantType = $('tr:has(td.conferenceName:contains(' + conferenceName + ')):has(td.displayName:contains("Codec"))').find(".displayName").data("participanttype");
        } else if (muteCommand === 'confUnmute') {
            muteAction = 'unmute';
            muteChannel = 'audioTxMuted';
            participantName = $('tr:has(td.conferenceName:contains(' + conferenceName + ')):has(td.displayName:contains("Codec"))').find(".displayName").data("participantname");
            participantProtocol = $('tr:has(td.conferenceName:contains(' + conferenceName + ')):has(td.displayName:contains("Codec"))').find(".displayName").data("participantprotocol");
            participantType = $('tr:has(td.conferenceName:contains(' + conferenceName + ')):has(td.displayName:contains("Codec"))').find(".displayName").data("participanttype");
        } else if (muteCommand === 'txMuteAll') {
            muteAction = 'mute';
            muteChannel = 'txAll';
            participantName = $(this).closest("tr").find(".displayName").data("participantname");
            participantProtocol = $(this).closest("tr").find(".displayName").data("participantprotocol");
            participantType = $(this).closest("tr").find(".displayName").data("participanttype");
        } else if (muteCommand === 'txUnmuteAll') {
            muteAction = 'unmute';
            muteChannel = 'txAll';
            participantName = $(this).closest("tr").find(".displayName").data("participantname");
            participantProtocol = $(this).closest("tr").find(".displayName").data("participantprotocol");
            participantType = $(this).closest("tr").find(".displayName").data("participanttype");
        }

        //alert(/*conferenceName + ' ' + muteCommand + ' ' + */ muteAction + ' ' + muteChannel);

        //post this information to refresher.php to take action
        $.ajax({
            type: "POST",
            url: "refresher.php",
            data: {action: "muteCommand", participantName: participantName, participantProtocol: participantProtocol, participantType: participantType, conferenceName: conferenceName, muteChannel: muteChannel, operationScope: "activeState", muteAction: muteAction},
            dataType: "json",
            cache: false,

            success: function (r) {
                //alert(r);
                if (r.alert) {
                    console.log(r.alert);
                }
            }
        });
    });

    /*
    //Shows or hides the setup options for the grid conference *****Delete?
    $(document).on('click', '.showSetup', function () {

        //alert($(this).val());

        if ($(this).val() === "Show Setup") {
            showSetup = true;
        } else if ($(this).val() === "Hide Setup") {
            showSetup = false;
        }

        //alert(showSetup);

    });
    */

    //Set the layout for the grid conference
    $(document).on('click', '.layout', function () {
        var layoutNumber = $(this).data("layout"), conferenceName = $(this).data("conf"), modalId = $(this).parent().parent().parent().parent().attr('id'), conferenceId = $(this).parent().parent().parent().parent().parent().data("confid"), displayName;
        //alert(layoutNumber);

        //post this information to refresher.php to take action
        $.ajax({
            type: "POST",
            url: "refresher.php",
            data: {action: "changeLayout", conferenceName: conferenceName, layoutNumber: layoutNumber},
            dataType: "json",
            cache: false,

            success: function (r) {
                if (r.panePlacement) {
                    panePlacementDropdowns(r.panePlacement, conferenceName, conferenceId, displayName);
                }
                if (r.alert) {
                    console.log(r.alert);
                }
            }
        });
    });

    //Initiate dial out
    $(document).on('click', '.dialOut', function () {

        var conferenceName = $(this).data("conf"), confId = $(this).data("confid"), callNumber = $('#call' + confId).val();

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

        //}

    });

    $(document).on("keypress", '.dialOutInput', function (e) {
            /* ENTER PRESSED*/
        if (e.keyCode === 13) {

            var conferenceName = $(this).parent().find(".dialOut").data("conf"), confId = $(this).parent().find(".dialOut").data("confid"), callNumber = $(this).val();
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
    });

    //Drop all participants from a conference
    $(document).on('click', '.dropAll', function () {
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
    $(document).on('click', '.teardown', function () {
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
    $(document).on('click', '.setupAll', function () {
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
    $(document).on('click', '.clearPanePlacement', function () {
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
    $(document).on('click', '.setImportantParticipant', function () {
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
	
	$(document).on('click', '.setFocusParticipant', function () {
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
    
    $(document).on('click', '.specialLayout', function () {
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
    $(document).on('click', '.resetspecialLayout', function () {
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

//Sends post info to refresher.php
$.customPOST = function (data, callback) {
    "use strict";
    $.post('refresher.php', data, callback, 'json');
};