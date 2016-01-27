<?php
class eCRFUser extends User {
    protected $_session = 'trial';
    protected $_db;
    protected $_adminUser = 1;
    public function __construct($id=NULL) {
        $this->_db = Config::get('userdb');
        parent::__construct($id);
    }
    public function getFlag() {
        return false;
    }
    public function login($username,$password, $allowEmail = TRUE ) {
        $login = parent::login($username,$password, $allowEmail);
        if($login)
            $this->setKey( $password, $this->dbkey );
        return $login;
    }
    public function logout($session=true){
        $this->unlinkRecord();
        parent::logout($session);
    }
	public function setKey( $pass, $key ) {
		$td = new Encrypt( $pass );
		$this->key = $td->decrypt( $key );
	}
	public function getPrivilege() {
		return $this->privilege_id;
	}
	public function setPrivilege( $value ) {
		$this->privilege_id = $value;
	}
    public function getCountry() {
        return $this->centre_country_id;
    }
	public function getLanguage() {
		return $this->centre_language_code;
	}

    /**
     * @param null $field
     * @return string|int
     */
	public function getCentre( $field = NULL ) {
		if ( $field == 'name' ) {
			$getCentre = $this->centre_name;
		} else {
			$getCentre = $this->centre_id;
		}
		return $getCentre;
	}
    public function getCentreUnits() {
        $getCentreUnits = array();
        if ( $this->getCentre() ) {
            $centre = new Centre($this->getCentre());
            $getCentreUnits = $centre->getUnits();
        }
        return $getCentreUnits;
    }
	public function isLocal() { // Returns false if user has privilege to see other site data
		$isLocal = true;
		if ( $this->getPrivilege() < 10 ) {
			$isLocal = false;
		}
		return $isLocal;
	}
    public function isRegional() {
        $isRegional = true;
		if ( $this->getPrivilege() < 9 ) {
			$isRegional = false;
		}
		return $isRegional;
    }
    public function isDataEntry() {
        $isDataEntry = false;
		if ( $this->getPrivilege() === 15 ) {
			$isDataEntry = true;
		}
		return $isDataEntry;
    }
    public function isLocalAdmin() {
        return ( $this->getPrivilege() === 10 || $this->getPrivilege() === 9 );
    }
	public function isRegionalAdmin() {
		return $this->getPrivilege() === 9;
	}
    public function isCentralAdmin() {
        return ( $this->getPrivilege() <= 5 );
    }
    public function accessAudit() {
        $accessAudit = false;
        if ( $this->getPrivilege() <= 15 ) {
            $accessAudit = true;
        }
        return $accessAudit;
    }
	public function canUnsign() {
		$canUnsign = false;
		if ( $this->getPrivilege() < 10 ) {
			$canUnsign = true;
		}
		return $canUnsign;
	}
    public function canUnPreSign() {
		$canUnPreSign = false;
		if ( $this->getPrivilege() <= 10 ) {
			$canUnPreSign = true;
		}
		return $canUnPreSign;
	}
    public function canPreSign() {
        $canPreSign = false;
		if ( $this->getPrivilege() <= 15 ) {
			$canPreSign = true;
		}
		return $canPreSign;
    }
    public function canSign() {
        $canSign = false;
        if ( $this->getPrivilege() <= 10 ) {
            $canSign = true;
        }
        return $canSign;
    }
    public function canIgnore() {
        return $this->isCentralAdmin();
    }
    public function canDelete() {
        return true;
    }
	public function createNewUser() {
        $forename = iconv("utf-8","ascii//IGNORE",$this->forename);
        $surname = iconv("utf-8","ascii//IGNORE",$this->surname);
		$username = strtolower( substr( $forename, 0, 1 ) ) . 
                strtolower( substr( str_pad( $surname, 6, '0', STR_PAD_RIGHT), 0, 6 ) );
		$username = DB::clean( $username );
		$sql = "SELECT username FROM user WHERE username LIKE '{$username}%'";
		$result = DB::query( $sql, $this->_db );
		if ( $result->getRows() )
			$username .= $result->num_rows;
		$this->username = $username;
		return $this->username;
	}
    public function deleteUser() {
        $this->deleteFromDB();
    }
    // Will get the person able to validate them (local Admin if normal user, or 
    // higher if they are higher)
	protected function getEmailContact( $type ) {
        $contact = array();
        if ( $type == 'testregister' || $type == 'created' ) {
            $contact[] = $this;
        } else if ( $type === 'register' || $type === 'forgotpass' ) {
            if ( $this->getPrivilege() == 98 ) {
                $contact[] = new eCRFUser($this->_adminUser);                
            } else {
                $sql = "SELECT id FROM user WHERE centre_id = ?";
                $pA = array( 'i', $this->getCentre() );
                $search = DB::query($sql,$pA);
                foreach( $search->rows as $row ) {
                    $possContact = new eCRFUser( $row->id );
                    if ( $possContact->isLocalAdmin() ) {
                        $contact[] = $possContact;
                    }
                }
                if ( empty( $contact) ) {
                    $contact[] = new eCRFUser($this->_adminUser);
                }
            }
        } else if ( $type === 'createuser' ) {
            $contact[] = new eCRFUser( 11 );
        } else {
            $contact[] = new eCRFUser($this->_adminUser);
        }
		return $contact;
	}
	public function writeEmail( $type, $sendUser = NULL, $password = NULL ) {
        $email = array();
        $counter = 1;
		$contacts = $this->getEmailContact( $type );
		$centre = $this->getCentre( 'name' );
        if ( !$sendUser ) {
            $sendUser = new eCRFUser($this->_adminUser);
        }        
        foreach( $contacts as $contact ) {
            switch( $type ) {
//                case 'register':
//                    $subject = "ISOS Trial user validation";
//                    $message = "<p>Dear {$contact->forename},</p>";
//                    $message .= "<p>{$this->forename} {$this->surname} from {$centre} has requested access to the ISOS database, if they are a valid user please log in at www.isos.org.uk/database and validate them from the 'User Admin' option in the menu.</p>";
//                    $message .= "<p>Best wishes,</p>";
//                    $message .= "<p>Trial admin</p>";
//                    $message .= "<p>Please note, this email address is not monitored, any problems email admin@isos.org.uk</p>";
//                    break;
                case 'forgotpass':
                    $subject = "Trial password reset";
                    $message = "<p>Dear {$contact->forename},</p>";
                    $message .= "<p>{$this->forename} {$this->surname} from {$centre} has requested their password to be reset, if they are a valid user please log in at www.isos.org.uk/database and validate them from the 'User Admin' option in the menu.</p>";
                    $message .= "<p>Best wishes,</p>";
                    $message .= "<p>Trial admin</p>";
                    $message .= "<p>Please note, this email address is not monitored, any problems email the trial co-ordinator</p>";
                    break;
                case 'createuser':
                    $subject = "Trial user creation";
                    $message = "<p>Username:{$this->username}</p>";
                    $message .= "<p>This user has been created with privilege level - {$this->getPrivilege()}</p>";
                    break;
                case 'register':
                case 'testregister':
                case 'created':
                    $sql = "SELECT * FROM messages WHERE type = ?";
                    $pA = array( 's', $type );
                    $q = DB::query( $sql, $pA );
                    if ( $q->getRows() ) {
                        $subject = $q->subject;
                        $message = $q->message;
                        $message = str_replace('//NAME//', $this, $message);
                        $message = str_replace('//CONTACTNAME//', $contact->forename, $message );
                        $message = str_replace('//CENTRE//', $centre, $message);
                        $message = str_replace('//USERNAME//', $this->username, $message);
                        $message = str_replace('//PASSWORD//', $password, $message);
                        $message = str_replace('//SENDNAME//', $sendUser, $message);
                        $message = str_replace('//SENDEMAIL//', $sendUser->email, $message);
                    } else {
                        $subject = "Trial registration";
                        $message = "<p>Dear {$this},</p>";
                        $message .= "<p>You have been validated to use the online database, your details follow:</p>";
                        $message .= "<p>Username: {$this->username}</p>";
                        $message .= "<p>Password: {$password}</p>";
                        $message .= "<p>You can change your password from the 'Update your details' option on the header.</p>";
                        $message .= "<p>Best wishes,</p>";
                        $message .= "<p>{$sendUser}</p>";
                        $message .= "<p>Trial Admin</p>";
                        $message .= "<p>Please note, this email address is not monitored, any problems email {$sendUser->email}.</p>";
                    }
                    break;
            }
            if ( $this->getPrivilege() == 98 ) {
                $subject = "[Local Admin validation]{$subject}";
            }
            if ( $this->getPrivilege() == 10 ) {
                $sql = "SELECT CONCAT( forename, ' ', surname ) as name FROM user WHERE centre_id = ? AND privilege_id = 99";
                $pA = array( 'i', $this->getCentre() );
                $result = DB::query($sql, $pA );
                if ( $result->getRows() ) {
                    $message .= "<p>The following users have already registered and are awaiting validation.</p>";
                    foreach( $result->rows as $row ) {
                        $message .= "<p>{$row->name}</p>";
                    }
                }
            }
            $email[$counter]['subject'] = $subject;
            $email[$counter]['message'] = $message;
            $email[$counter]['name'] = "{$contact->forename} {$contact->surname}";
            $email[$counter]['email'] = $contact->email;
            $counter++;
        }
		return $email;
	}
	public function linkRecord( $link ) {
		$this->link_id = $link;
		$this->saveToDB();
	}
	public function unlinkRecord() {
		$this->link_id = NULL;
		$this->saveToDB();
	}
	public function isLinked() {
		return $this->link_id;
	}
}
?>
