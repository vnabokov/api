<?php
/** MESSAGEBOX API
	@Author: Juha-Matti Hopponen 2015
	
	Only for use as a coding demonstration
	
	Note:
	Lots of features have been not implemented nor done as they would be
	done in production code, because the goal have been to create a standalone
	PHP-api which can be ran in any server, without configuring the
	databases, .htaccess files etc. Further remarks on limited features and
	such had been written into the source code
	
	All the error handling is done with die(), which is actually a bad
	practice, but will work as an example
	
	Paradigm for this example:
	No frameworks, no database, one-file-solution
*/

function createPreview($str) {
	/* Show limited amount (70 in this case) of characters at most in
	the thread preview */
	
	if (strlen($str) < PREVIEW_LENGTH) // if the excerpt is shorter than defined max length, return trimmed string itself
		return trim($str);
	else // otherwise return 70 first characters
		return substr($str,0,PREVIEW_LENGTH);
}

function getAllThreads() {
	/* Reads all threads found in system
	IRL would make a database query instead of reading the .txt file */
	$file = file("messages.txt"); // read the threads into array
	$threads = Array();
	
	foreach ($file as $line) { // loop to access the properties
		$values = explode("|",$line); // explode the coded string
		if ($values[1] != 1) // if not the first message of the thread, don't parse
			continue;
		$item[0] = $values[0];
		$item[1] = createPreview($values[3]);
		array_push($threads, $item);	
		
		
	}
	return $threads;
	
}
function getThread($threadId) {
	/* Returns a messages of a certain thread
	Uses .txt -file as an example */
	$file = file("messages.txt"); // read the threads (and messages) into array
	$messages = Array();
	
	foreach ($file as $line) { // loop to access the properties
		$values = explode("|",$line); // explode the coded string
		if ($values[0] != $threadId) // if the ThreadId is not the one requested, don't parse
			continue;
		$arr = array("id" => $values[1], "nickname" => $values[2], "message" => trim($values[3])); // create an assocative array to contain the key - value pairs
		array_push($messages, $arr); // add created assoc array into array of messages
	}
	/* Create couple of arrays, one containing the id and one the messages 
	and merge them together and return the merged array */
	
	$arr1 = array("id" => $threadId); //
	$arr2 = array("messages" => $messages);
	$arr = array_merge($arr1, $arr2);
	return $arr;
	
}
function parseParams($uri) {
	/* Reads the parameters of Request URI and returns an array */
	
	$start_needle = strlen(PATH); // calculates the starting point of parameters
	$params_string = substr($uri,$start_needle);
	if (strpos($params_string,"/:")) // if the URI has the "/:" -part, we'll have an id as a parameter
		$params = explode("/:",$params_string);
	else //otherwise we allocate the action per string and assign ID as NULL
	{
		$params[0] = substr($params_string,0,strlen($params_string) -1);
		$params[1] = null;
	}
	return $params;
	
};

function parseRequest($request) {
	/* Reads the request (as no framework being in use) */
	$result[0] = $request["REQUEST_METHOD"];
	$result[1] = parseParams($request["REQUEST_URI"]);
	return $result;
}

function postNewThread($values) {
	/* Creates a new thread 
	In real life this should save the Thread and first message */
	
	$id = rand(100,1000); // this should be an lastInsertId() or similar, but random does an example
	$arr1 = array("threadId" => $id);
	$arr2 = array("message" => array("id" => 1, "nickname" => $values['name'], "messageBody" => $values['message']));
	$arr = array_merge($arr1,$arr2);
	return $arr;
}

function postToThread($threadId, $values) {
	/* Adds reply to a thread
	IRL should read the value of $threadId and select the thread accordingly */
	$arr = array("id" => 2, "nickname" => $values['name'], "message" => $values['message']);
	return $arr;
}

/* Define the Constants, which in real life would probably be either 
	database values or System ENVS */
define("PATH","/api/index.php/");
define("PREVIEW_LENGTH",70);

$request = parseRequest($_SERVER); // handle the Request

// check if ACTION is proper 
if ($request[1][0] != "threads") { /* because "threads" is only allowed action
									per specs, return an error if something else
										provided */
	die("Invalid API Call: Forbidden action");
};

// check if ID is an integer 
if (is_numeric($request[1][1]))
	$id = (int) $request[1][1];
else
	$id = null;

/* Perform the correct action based on the parsed request array */
if ($request[0] == "POST") { // if something sent via POST
	if ($id) // if the ID is provided, it's supposed to be a reply to a thread
		$results = postToThread($id, $_POST);
	else // otherwise it's new thread 
		$results = postNewThread($_POST);
	
}

elseif ($request[0] == "GET") { // else if something via GET
	if ($id) // id set, fetch certain thread
		$results = getThread($id);
	else // otherwise fetch them all
		$results = getAllThreads();
}

else { // if method is not "GET" nor "POST", it should not be allowed
	die("Invalid API Call: This method is not allowed");
}

if (!$results) // if $results is NULL something is wrong, show error
	die("Invalid API Call: Internal error");
else
	echo json_encode($results); // otherwise JSON the results
