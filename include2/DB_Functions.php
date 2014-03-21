<?php

class DB_Functions {

    private $db;

    // constructor
    function __construct() {
        require_once 'DB_Connect.php';
        // connecting to database
        $this->db = new DB_Connect();
        $this->db->connect();
    }

    // destructor
    function __destruct() {
        
    }

    public function createUser($name, $email, $password, $timeToSleep, $timeToWakeup) {
    	mysql_set_charset('utf8');
        $response = array();
 
        // First check if user already existed in db
        if (!$this->isUserExists($email)) { 
            $query = mysql_query("INSERT INTO user(Name, Email, Password, Status, TimeToSleep, TimeToWakeup, CreatedAt) 
                values('$name', '$email', '$password', 0, '$timeToSleep', '$timeToWakeup', CURRENT_TIMESTAMP)");
 
            // Check for successful insertion
            if ($query) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }
 
        return $response;
    }

    public function loginUser($email, $password) {
        mysql_set_charset('utf8');
        $query = mysql_query("SELECT * FROM user WHERE Email = '$email'");
        $no_of_rows = mysql_num_rows($query);
        if ($no_of_rows > 0) {
            $result = mysql_fetch_array($query);
            $response_pwd = $result['Password'];
            // check for password equality
            if ($response_pwd == $password) {
                // user authentication details are correct
                return $result;
            } else {
                return USER_LOGIN_FAILED_PASSWORD;
            }
        } else {
            // user not found
            return USER_LOGIN_FAILED_EMAIL;
        }
    }

    public function getDream($id) {
        mysql_set_charset('utf8');
        $query = mysql_query("SELECT DreamId, Name, Content, Privilege, dream.CreatedAt 
            FROM dream INNER JOIN user on user.UserId=dream.AuthorId where Privilege!=0 AND dream.DreamId='$id'");
        $no_of_rows = mysql_num_rows($query);
        if ($no_of_rows > 0) {
            return mysql_fetch_array($query);
        } else {
            return false;
        }
    }

    public function getAllDreams($userId) {
        mysql_set_charset('utf8');
        $query = mysql_query("SELECT DreamId, Name, Content, Privilege, dream.CreatedAt 
            FROM dream INNER JOIN user on user.UserId=dream.AuthorId where Privilege!=0 AND dream.AuthorId='$userId'");
        return $this->onDbResponse($query);
    }

    public function getAllTimelineDreams($userId) {
        mysql_set_charset('utf8');
        $query = mysql_query("SELECT DreamId, Name, Content, Privilege, dream.CreatedAt 
            FROM dream INNER JOIN user on user.UserId=dream.AuthorId where dream.AuthorId='$userId'");
        return $this->onDbResponse($query);
    }

    public function getAllComments($dreamId) {
        mysql_set_charset('utf8');
        $query = mysql_query("SELECT CommentId, Name, Content, Comment.CreatedAt 
            FROM comment INNER JOIN user ON comment.AuthorId=user.UserId WHERE DreamId=$dreamId");
        return $this->onDbResponse($query);
    }

    public function getAllTags($dreamId) {
        mysql_set_charset('utf8');
        $query = mysql_query("SELECT dreamtag.TagId, tag.Name 
            from tag inner join dreamtag on dreamtag.tagid=tag.tagid where dreamtag.DreamId='$dreamId'");
        return $this->onDbResponse($query);
    }

    public function getAllMentions($dreamId) {
        mysql_set_charset('utf8');
        $query = mysql_query("SELECT mention.MentionId, user.Name 
            from mention inner join user on mention.userid=user.userid where mention.DreamId='$dreamId'");
        return $this->onDbResponse($query);
    }

    public function getAllFriends($userId) {
        mysql_set_charset('utf8');
        $query = mysql_query("SELECT user.UserId, user.Name, user.Status 
            FROM friendship INNER JOIN user ON user.UserId=friendship.FriendId WHERE SenderId='$userId' AND State='accepted'");
        return $this->onDbResponse($query);
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {
        $query = mysql_query("SELECT UserId FROM user WHERE Email = '$email'");
        $no_of_rows = mysql_num_rows($query);
        return $no_of_rows > 0;
    }

    private function onDbResponse($query) {
        $no_of_rows = mysql_num_rows($query);
        if ($no_of_rows > 0) {
            return $query;
        } else {
            return false;
        }
    }

    public function getAllMessages() {
	    mysql_set_charset('utf8');
		$result = mysql_query("SELECT * FROM message");
		$no_of_rows = mysql_num_rows($result);
		if ($no_of_rows > 0) {
			return $result;
		} else {
			return false;
		}
	}
}

?>
