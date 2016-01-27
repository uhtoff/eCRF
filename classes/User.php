<?php
abstract class User extends DBObject {
    protected $_session = 'user';
    protected $_db = 'user';
    const TABLE = 'user';
    const EXPIRE = 10;
    public function __toString() {
		return HTML::clean( $this->forename	. ' ' . $this->surname );
	}
    public function isLoggedIn() {
        return $this->loggedin;
    }
    public function getKey() {
        $getKey = NULL;
        if ( isset ( $this->key ) ) {
            $getKey = $this->key;
        }
		return $getKey;
	}
	public function set( $prop, &$value ) {
		if ( $prop == 'password' ) {
			$this->setPassword( $value );
		} else {
			parent::set( $prop, $value );
		}
	}

    /**
     * @param string $username
     * @param string $password
     * @param bool|false $allowEmail
     * @return bool
     */
	public function login( $username, $password, $allowEmail = FALSE ) {
		$login = false;   
		$sql = "SELECT id, password, loggedin, active FROM user WHERE username = ?";
        if ( $allowEmail ) {
            $sql .= " OR email = ?";
            $pA = array( 'ss', $username, $username );
        } else {
            $pA = array( 's', $username );
        }
        $result = DB::query( $sql, $pA, $this->_db );
		if ( $result->getRows() ) {
			if (is_null($result->password)) {
				$_SESSION['error'] = "Account still awaiting validation";
			} else if ($result->loggedin) {
				$_SESSION['error'] = "Account already logged in.";
			} else if (!$result->active) {
                $_SESSION['error'] = "The user account has been deactivated.";
			} else if ( Encrypt::checkPassword( $password, $result->password ) ) {
				$this->setID( $result->id );
				$this->getFromDB();
				$this->loggedin = 1;
				$this->lastlogon = gmdate("Y-m-d H:i:s");
				$this->saveToDB();
				$login = true;
			} else {
				$_SESSION['error'] = "Incorrect username or password";
			}
		} else {
			$_SESSION['error'] = "User not found.";
		}
		return $login;
	}
	public function logout( $session = true ) {
		$this->loggedin = 0;
		$this->saveToDB();
		if ( $session ) {
			session_unset();
			session_destroy();
			session_write_close();
			if (isset($_COOKIE[session_name()])) {
				setcookie(session_name(), '', time()-42000, '/');
			}
			session_regenerate_id( true );
			session_name( $this->_session );
			session_start();
			$_SESSION = array();
		}
	}
    public function check_login() {
	// To check if current user is still logged in 
    // expire login and then recheck, otherwise mark user as still active
		$logon = false;
		$userExpired = $this->expireUsers();
		if ($userExpired)
            $_SESSION['loginerr'] = "Log in expired due to inactivity";
		else {
			$logon = 1;
			$sql = "UPDATE " .
                    self::TABLE .
                    " SET lastactive=NULL WHERE id=?";
			$pA = array( 'i', $this->getID() );
			DB::query( $sql, $pA, $this->_db );
		}
		return $logon;
	}
    // Searches user database for users to expire
    public function expireUsers() {
		$expireUsers = false;
        $sql = "SELECT id FROM " .
                self::TABLE . 
                " WHERE MINUTE( TIMEDIFF( NOW(), lastactive ) ) > " .
                self::EXPIRE .
                " AND loggedin=1";
        $result = DB::query( $sql, $this->_db );
        $c = get_called_class();
        foreach( $result->rows as $row ) {
            $session = false;
            if ( $row->id == $this->getID() ) {
                $this->logout(true);
                $expireUsers = true;
            } else {
                $user = new $c( $row->id );
                $user->logout( false );
            }
        }
        return $expireUsers;
	}
	public function expire_login() {
		$sql = "SELECT id FROM " .
                self::TABLE .
                " WHERE MINUTE( TIMEDIFF( NOW(), lastactive ) ) > " . 
                self::EXPIRE .
                " AND loggedin=1";
       $result = DB::query( $sql, $this->_db );
		if ( $result->getRows() ) {
			$pA = array();
			$pA[] = str_repeat( 'i', $result->num_rows );
			$sql = "UPDATE " .
                    self::TABLE .
                    " SET loggedin = 0 WHERE id IN (";
			foreach ( $result->rows as $row ) {
				$sql .= "?, ";
				$pA[] = $row->id;
			}
			$sql = substr( $sql, 0, -2 );
			$sql .= ")";
			DB::query( $sql, $pA, $this->_db );
		}
	}
	public function refreshUser() {
        // Check to see if user is logged out
		$sql = "SELECT loggedin FROM user WHERE id=?"; 
		$pA = array( 'i', $this->getID() );
        $result = DB::query( $sql, $pA, $this->_db );
		if ( $result->getRows() ) {
			if( $result->loggedin == 0 ) { // If not logged on
				$this->logout();
				$refreshUser = false;
			} else {
                // Renew last active field of user
				$sql = "UPDATE user SET lastactive = NULL WHERE id = ?";
				$pA = array( 'i', $this->getID() );
				DB::query( $sql, $pA, $this->_db );
				$refreshUser = true;
			}
		}
		return $refreshUser;		
	}
    public function forgotPassword() {
        if ( $this->getPrivilege() == 10 ) {
            $this->privilege_id = 98;
        } else {
            $this->privilege_id = 99;
        }
		$this->delete('password');
        $this->delete('dbkey');
	}
	public function isUser() {
		$isUser = false;
		$sql = "SELECT id FROM user 
            WHERE forename = ? AND surname = ? AND email = ?";
		$pA = array( 'sss', $this->forename, $this->surname, $this->email );
        $result = DB::query( $sql, $pA, $this->_db );
		if ( $result->getRows() )
			$isUser = $result->id;
		return $isUser;
	}
	public function generatePassword($length = 8) {
		$password = "";
		$possible = "2346789bcdfghjkmnpqrtvwxyz!%&*BCDFGHJKLMNPQRTVWXYZ";
		$maxlength = strlen($possible);
		if ($length > $maxlength) {
			$length = $maxlength;
		}
		$i = 0; 
		while ($i < $length) { 
			$char = substr($possible, mt_rand(0, $maxlength-1), 1);
			$password .= $char;
			$i++;
		}
        $pattern = '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/';
        while ( !preg_match($pattern,$password) ) {
            $password = $this->generatePassword();
        }
		return $password;
	}
    public function setPassword( $value, $key = NULL ) {
        if ( is_null( $value ) ) {
            $this->password = NULL;
        } else if ( !$this->username ) { // Without username you can't set the password
			$this->tempPass = $this->password = $value;
		} else {
			$newPass = Encrypt::hashPassword($value);
			$this->password = NULL;
			$this->get( 'password' );
			if ( $this->password != $newPass ) { // Only fiddle with the key if it's a change in password (prevents multiple passes)
				$this->password = $newPass;				
				if ( !$key ) { // Allow a key to be sent with the setPassword
					$key = $this->getKey();
				}
                if ( $key ) {
                    $td = new Encrypt( $value );
                    $this->dbkey = $td->encrypt( $key );
                }
			}
			unset( $this->tempPass );
		}
	}
	public function checkPassword( $password ) {
		$checkPassword = false;
		$currPass = $this->get( 'password' );
		if (Encrypt::checkPassword($password, $currPass )) {
			$checkPassword = true;
		} else {
			$checkPassword = false;
		}
		return $checkPassword;
	}
	public function checkDuplicate($email=NULL) {
		$checkDuplicate = false;
        $checkEmail = is_null($email) ? $this->email : $email;
		$sql = "SELECT id, email FROM user WHERE email = ?";
		$pA = array( 's', $checkEmail );
        $result = DB::query( $sql, $pA, $this->_db );
		if ( $result->getRows() && $result->id != $this->getID() )
            $checkDuplicate = true;
		return $checkDuplicate;
	}
}
?>
