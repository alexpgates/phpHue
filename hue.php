<?php

$bridge = '10.0.1.18';
$key = 'weatheruser';
$utc_adjustment = 18000; // + or -, this is the user's adjustment to UTC from local time zone (used in scheduling functions)

require('pest-master/PestJSON.php');


// Registers your script with your Hue hub
function register() {
	global $bridge, $key;

	$pest = new Pest("http://$bridge/api");
	$data = json_encode(array('username' => 'abcd1234', 'devicetype' => 'Ray Solutions Scripts'));
	$result = $pest->post('', $data);
	return "$result\n";
}



// Returns a big array of the state of either a single light, or all your lights
function getLightState($lightid = false) { 
	global $bridge, $key;
	$targets = array();
	$result = array();

	if ($lightid === false) {	
		$targets = getLightIdsList();
	} else {
		if (! is_array($lightid)) {
			$targets[] = $lightid;
		} else {
			$targets = $lightid;
		}
	}

	foreach ($targets as $id) {

		$pest = new PEST("http://$bridge/api/$key/");
		$deets = json_decode($pest->get("lights/$id"), true);
		$state = $deets['state'];
		
		$result[$id] = $state;

	}
	return $result;
}

// simple function to return true or false when asked if a light is on
function lightIsOn($lightid){
	global $bridge, $key;
	$pest = new PEST("http://$bridge/api/$key/");
	$deets = json_decode($pest->get("lights/$lightid"), true);
	$on = $deets['state']['on'];
	return($on);
}

// Returns an array of the light numbers in the system
function getLightIdsList() {
	
		global $bridge, $key;
		$pest = new Pest("http://$bridge/api/$key/");

		$result = json_decode($pest->get('lights'), true);
		$targets = array_keys($result);
		return $targets;
}

// sets the alert state of a single light. 'select' blinks once, 'lselect' blinks repeatedly, 'none' turns off blinking
function alertLight($target, $type = 'select') {
		global $bridge, $key;
		$pest = new Pest("http://$bridge/api/$key/");
		$data = json_encode(array("alert" => $type));
		echo $data;
		$result = $pest->put("lights/$target/state", $data);

		return $result;
}

// function for setting the state property of one or more lights
function setLight($lightid, $input) {
	global $bridge, $key;
	$pest = new Pest("http://$bridge/api/$key/");

	$data = json_encode($input);
	$result = '';

	if (is_array($lightid)) {
		foreach ($lightid as $id) {
			$pest = new Pest("http://$bridge/api/$key/");
			$result .= $pest->put("lights/$id/state", $data);
		}
	} else {
		$result = $pest->put("lights/$lightid/state", $data);
	}
	return $result;
}

// function for setting the state property of a group of lights
function setGroup($groupid, $input) {
	global $bridge, $key;
	$pest = new Pest("http://$bridge/api/$key/");

	$data = json_encode($input);
	$result = '';

	if (is_array($groupid)) {
		foreach ($groupid as $id) {
			$pest = new Pest("http://$bridge/api/$key/");
			$result .= $pest->put("groups/$id/action", $data);
		}
	} else {
		$result = $pest->put("groups/$groupid/action", $data);
	}
	return $result;
}

// gin up a random color
function getRandomColor() {
	$return = array();

	$return['hue'] = rand(0, 65535);
	$return['sat'] = rand(0,254);
	$return['bri'] = rand(0,254);

	return $return;
}

// gin up a random temp-based white setting
function getRandomWhite() {
	$return = array();
	$return['ct'] = rand(150,500);
	$return['bri'] = rand(0,255);

	return $return;
}

function dayWhite() {
	$command = array();
	$command['on'] = true;
	$command['ct'] =  300;
	$command['bri'] = 254;
	$command['transitiontime'] = 80;
	setGroup(0, $command);
}

function nightWhite() {
	$command = array();
	$command['on'] = true;
	$command['ct'] =  450;
	$command['bri'] = 254;
	$command['transitiontime'] = 80;
	setGroup(0, $command);
}

// build a few color commands based on color names.
function predefinedColors($colorname) {
	$command = array();
	switch ($colorname) {
		case "green":
			$command['hue'] =  182 * 140;
			$command['sat'] = 254;
			$command['bri'] = 254;
			break;
		case "red":
			$command['hue'] =  0;
			$command['sat'] = 254;
			$command['bri'] = 254;
			break;
		case "blue":
			$command['hue'] =  182 * 250;
			$command['sat'] = 254;
			$command['bri'] = 254;
			break;
		case "coolwhite":
			$command['ct'] =  150;
			$command['bri'] = 254;
			break;
		case "warmwhite":
			$command['ct'] =  500;
			$command['bri'] = 254;
			break;
		case "purple":
			$command['hue'] =  182 * 270;
			$command['sat'] = 254;
			$command['bri'] = 254;
			break;
		case "pink-purple":
			$command['hue'] =  182 * 300;
			$command['sat'] = 254;
			$command['bri'] = 254;
			break;
		case "yellow":
			$command['hue'] =  182 * 85;
			$command['sat'] = 254;
			$command['bri'] = 254;
			break;
		case "orange":
			$command['hue'] =  182 * 25;
			$command['sat'] = 254;
			$command['bri'] = 254;
			break;

	}
	return $command;
}

// Returns a big array of the currently set schedules
function getSchedules() { 
	global $bridge, $key;

	$pest = new PEST("http://$bridge/api/$key/");
	$schedules = json_decode($pest->get("schedules"), true);

	return $schedules;
}

// Creates a scheduled event
function createSchedule($wrapper){
	global $bridge, $key, $utc_adjustment;

	// This function accepts $time as a unix timestamp and will perform the conversion to ISO 8601:2004
	//Time when the scheduled event will occur in ISO 8601:2004 format.
	//The bridge measures time in UTC and only accepts extended format, non-recurring, local time (YYYY-MM-DDThh:mm:ss). 
	//Incorrectly formatted dates will raise an error of type 7. If the time is in the past an error 7 will also be raised.

	// a rough example

	// $schedule_wrapper = array();
	// $schedule_command = array();
	// $schedule_body = array();

	// $schedule_wrapper['name'] = 'First Schedule Test';
	// $schedule_wrapper['time'] = '1367198360';
	// $schedule_wrapper['description'] = 'My attempt at a scheduled command';
	// $schedule_wrapper['command']['address'] = '/api/'.$key.'/groups/0/action';
	// $schedule_wrapper['command']['method'] = 'PUT';
	// $schedule_wrapper['command']['body'] = array('on' => true);

	// createSchedule($schedule_wrapper);

	$data = array();
	$data['name'] = $wrapper['name'];
	$data['description'] = $wrapper['description'];

	// adjust time for utc
	$time = $wrapper['time'] + $utc_adjustment;

	$data['time'] = date('c', $time);
	echo $data['time'];
	$data['command'] = $wrapper['command'];

	$json_body = json_encode($data);

	$pest = new PEST("http://$bridge/api/$key/");
	$schedules = json_decode($pest->post("schedules", $json_body), true);


}

function deleteSchedule($id){
	global $bridge, $key;

	$pest = new PEST("http://$bridge/api/$key/");
	$schedules = json_decode($pest->delete("schedules/$id"), true);

	return $schedules;
}

?>
