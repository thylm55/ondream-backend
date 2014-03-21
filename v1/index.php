<?php
 
require '.././libs/Slim/Slim.php';
require_once '../include2/DB_Functions.php';
 
\Slim\Slim::registerAutoloader();
 
$app = new \Slim\Slim();
 
// User id from db - Global Variable
$user_id = NULL;
 
/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
 
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["success"] = false;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoResponse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
 
    // setting response content type to json
    $app->contentType('application/json;charset=utf-8');
 
    echo json_encode($response);
}

/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/register', function() use ($app) {
	// check for required params
	verifyRequiredParams(array('name', 'email', 'password', 'sleep', 'wakeup'));

	$response = array();

	// reading post params
	$name = $app->request->post('name');
	$email = $app->request->post('email');
	$password = $app->request->post('password');
	$sleep = $app->request->post('sleep');
	$wakeup = $app->request->post('wakeup');

	$db = new DB_Functions();
	$res = $db->createUser($name, $email, $password, $sleep, $wakeup);

	if ($res == USER_CREATED_SUCCESSFULLY) {
		$response["success"] = true;
		$response["message"] = "You are successfully registered";
		echoResponse(201, $response);
	} else if ($res == USER_CREATE_FAILED) {
		$response["success"] = false;
		$response["message"] = "Oops! An error occurred while registereing";
		echoResponse(200, $response);
	} else if ($res == USER_ALREADY_EXISTED) {
		$response["success"] = false;
		$response["message"] = "Sorry, this email already existed";
		echoResponse(200, $response);
	}
});

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
	verifyRequiredParams(array('email', 'password'));

	$response = array();

	$email = $app->request->post('email');
	$password = $app->request->post('password');

	$db = new DB_Functions();
	$res = $db->loginUser($email, $password);

	if ($res == USER_LOGIN_FAILED_PASSWORD) {
		$response["success"] = false;
		$response["message"] = "Password is incorrect";
		echoResponse(200, $response);
	} else if ($res == USER_LOGIN_FAILED_EMAIL) {
		$response["success"] = false;
		$response["message"] = "Email is incorrect";
		echoResponse(200, $response);
	} else {
		$response["success"] = true;
		$response["message"] = "You are login successfully";
		$data = array();
		$data["id"] =					$res["UserId"];
		$data["name"] =					$res["Name"];
		$data["email"] =				$res["Email"];
		$data["status"] =				$res["Status"];
		$data["sleep_time"] =			$res["TimeToSleep"];
		$data["wakeup_time"] =			$res["TimeToWakeup"];
		$data["created_at"] =			$res["CreatedAt"];
		$response["data"] = $data;
		echoResponse(200, $response);
	}
});

$app->get('/friends', function() use ($app) {
	$response = array();

	$userId = $app->request->get('user_id');

	$db = new DB_Functions();
	$friends = $db->getAllFriends($userId);

	if ($friends == false) {
		$response["success"] = false;
		$response["message"] = "Poor you, no friends in the list";
	} else {
		$response["success"] = true;
		$response["friends"] = array();
		while ($friend = mysql_fetch_array($friends)) {
			$tmp_friend = array();
			$tmp_friend["id"] = 		$friend["UserId"];
			$tmp_friend["name"] = 		$friend["Name"];
			$tmp_friend["status"] = 	$friend["Status"];

			array_push($response["friends"], $tmp_friend);
		}
	}

	echoResponse(200, $response);
});

$app->get('/timeline', function() use ($app) {
	$response = array();

	$userId = $app->request->get('user_id');

	$db = new DB_Functions();
	$dreams = $db->getAllTimelineDreams($userId);

	if (!$dreams) {
		$response["success"] = 			false;
		$response["message"] = 			"Your timeline is empty";
	} else {
		$response["success"] = 			true;
		$response["dreams"] = 			array();

		while ($dream = mysql_fetch_array($dreams)) {
			array_push($response["dreams"], getDream($dream));
		}
	}
	echoResponse(200, $response);
});

$app->get('/dream', function() use ($app) {
	$response = array();

	$id = $app->request->get('id');

	$db = new DB_Functions();
	$res = $db->getDream($id);

	if ($res == false) {
		$response["success"] = false;
		$response["message"] = "Dream not found";
	} else {
		$response["success"] = true;
		$response["data"] = array();
		array_push($response["data"], getDream($res));
	}

	echoResponse(200, $response);
});

/**
Get all dreams at news feed specific by user id
*/
$app->get('/dreams', function() use ($app) {
	$response = array();

	$userId = $app->request->get('user_id');

	$db = new DB_Functions();
	$friends = $db->getAllFriends($userId);

	if ($friends == false) {
		$response["success"] = 			false;
		$response["message"] = 			"You have no friends in the list";
	} else {
		$response["success"] = 			true;
		$response["dreams"] = array();
		while ($friend = mysql_fetch_array($friends)) {
			$friend_id = $friend["UserId"];

			$friend_dreams = $db->getAllDreams($friend_id);

			if ($friend_dreams) {
				while ($dream = mysql_fetch_array($friend_dreams)) {
					array_push($response["dreams"], getDream($dream));
				}
			}
		}
	}

	echoResponse(200, $response);
});

function getDream($dream) {
	$tmp = array();
	$tmp["id"] = 						$dream["DreamId"];
	$tmp["author"] = 					$dream["Name"];
	$tmp["content"] = 					$dream["Content"];
	$tmp["privilege"] = 				$dream["Privilege"];
	$tmp["created_at"] = 				$dream["CreatedAt"];

	$db = new DB_Functions();
	$comments = $db->getAllComments($dream["DreamId"]);

	if ($comments) {
		$tmp["comments"] = array();
		while ($comment = mysql_fetch_array($comments)) {
			$tmp_comment = array();
			$tmp_comment["id"] = 		$comment["CommentId"];
			$tmp_comment["author"] =	$comment["Name"];
			$tmp_comment["content"] = 	$comment["Content"];
			$tmp_comment["created_at"] =$comment["CreatedAt"];

			array_push($tmp["comments"], $tmp_comment);
		}
	}

	$tags = $db->getAllTags($dream["DreamId"]);

	if ($tags) {
		$tmp["tags"] = array();
		while ($tag = mysql_fetch_array($tags)) {
			$tmp_tag = array();
			$tmp_tag["id"] = 			$tag["TagId"];
			$tmp_tag["name"] = 			$tag["Name"];

			array_push($tmp["tags"], $tmp_tag);
		}
	}

	$mentions = $db->getAllMentions($dream["DreamId"]);

	if ($mentions) {
		$tmp["mentions"] = array();
		while ($mention = mysql_fetch_array($mentions)) {
			$tmp_mention = array();
			$tmp_mention["id"] = 		$mention["MentionId"];
			$tmp_mention["name"]=$mention["Name"];

			array_push($tmp["mentions"], $tmp_mention);
		}
	}

	return $tmp;
}

$app->get('/messages', function() use ($app) {
	$response = array();
    $db = new DB_Functions();
	$res = $db->getAllMessages();

	$response["success"] = true;
	$response["messages"] = array();

	// looping through result and preparing tasks array
	while ($message = mysql_fetch_array($res)) {
		$tmp = array();
		$tmp["message_id"] = 			$message["MessageId"];
		$tmp["friendship_id"] = 		$message["FriendshipId"];
		$tmp["type"] = 					$message["MessageType"];
		$tmp["content"] = 				$message["Content"];
		$tmp["push_at"] = 				$message["PushAt"];
		array_push($response["messages"], $tmp);
	}

	echoResponse(200, $response);
});

$app->run();

?>