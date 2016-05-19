<?php
require_once( 'setup.php' );
session_start();

if ( isset( $_POST['page'] ) && ctype_alnum( $_POST['page'] ) ) { // If someone tries to send something odd then return to index
	$page = $_POST['page'];
} else {
	$_SESSION['error'] = "An error has occurred, please try again.";
	header( 'Location:index.php' );
	exit();
}

$trial = new eCRF( $page );

if ( isset( $_SESSION['user'] ) ) {
	$user = $_SESSION['user'];
	$loggedIn = $trial->addUser( $user );
	if ( !$loggedIn ) {
		$_SESSION['error'] = "Log in expired due to inactivity";
		header( "Location:index.php?expire=1" );
		exit();
	}
}

if ( !isset($_POST['csrfToken']) || !isset($_SESSION['csrfToken']) || $_POST['csrfToken'] != $_SESSION['csrfToken']) {
    $_SESSION['error'] = 'A token error has occurred, please try again.';
    if ( isset($_SESSION['csrfToken'])) {
        unset($_SESSION['csrfToken']);
    }
    header( "Location:index.php");
    exit();
}

if ( isset($_SESSION['csrfToken'])) {
    unset($_SESSION['csrfToken']);
}

$include = $trial->checkPageLogin( $page );

switch( $include ) {
	case 'usersett':
		$user = $trial->getUser();
		if( !$user->checkPassword( $_POST["{$page}-password"][0] ) ) {
			$_SESSION['error'] = 'You must enter your current password to change your details.';
			header( "Location:index.php?page=usersett" );
			exit();
        } else if ( $user->checkDuplicate($_POST["{$page}-email"]) ) {
            $_SESSION['error'] = "A user has already been registered with this email address.";
            header( "Location:index.php?page=usersett" );
			exit();
        } else {
			$trial->addUserInput( $_POST, $user ); // Add data to user object
            
			if ( !isset( $_SESSION['inputErr'] ) ) { // If no errors, report success
                $user->saveToDB(); // Save it to the database
				$_SESSION['message'] = 'Your details have been successfully updated.';
				header( "Location:index.php" );
				exit();
			}
		}
		header( "Location:index.php?page={$include}" );
		exit();
		break;
	case 'register':
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $postData = array('secret' => '6LcEFQ8TAAAAAPv9Mt58PDA9mcBt3vhhDtevEc-v', 'response' => $_POST['g-recaptcha-response']);

        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($postData),
            ),
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        $resp = json_decode($result);

		if ( !$resp->success ) {
			// What happens when the CAPTCHA was entered incorrectly
			$_SESSION['error'] = "The CAPTCHA was entered incorrectly. Please try again.";
		} else {   
			$data = new eCRFUser();
			$complete = $trial->addUserInput( $_POST, $data );
			if ( $complete ) {
				if ( $data->checkDuplicate() ) { // Check for duplicate users
					$_SESSION['error'] = "A user has already been registered with this email address.";
					$_SESSION['inputErr']['dupEmail'] = 1;
				} else { // No duplicates found so add to database and generate emails
					$data->createNewUser(); // Generate username and set privilege ID to 99
                    if ( $_POST['register-localadmin'] ) {
                        $newPriv = 98;
                    } else {
                        $newPriv = 99;
                    }
					$data->setPrivilege( $newPriv );
                    $data->saveToDB();
                    $data->getFromDB();
					$email = $data->writeEmail( $include );
					$mail = $trial->sendEmail( $email );
                    $_SESSION['message'] = 'You have been registered and are in the queue for validation.';
                    header( "Location:index.php" );
                    exit();
				}
			}
		}
		if ( isset($data) ) $_SESSION[$include] = $data;
		header( "Location:index.php?page={$include}" );
		exit();
		break;
    case 'testregister':
		$path = $_SERVER['DOCUMENT_ROOT'];
		require_once($path . '/addons/recaptchalib.php');
		$privatekey = "6Lc_XusSAAAAAOReovfKO421ksUcl2camDQsC05u";
		$resp = recaptcha_check_answer ($privatekey,
                                $_SERVER["REMOTE_ADDR"],
                                $_POST["recaptcha_challenge_field"],
                                $_POST["recaptcha_response_field"]);

		if (!$resp->is_valid) {
			// What happens when the CAPTCHA was entered incorrectly
			$_SESSION['error'] = "The CAPTCHA was entered incorrectly. Please try again.";
		} else {   
			$newUser = new eCRFUser();
			$complete = $trial->addUserInput( $_POST, $newUser );
			if ( $complete ) {
				if ( $newUser->checkDuplicate() ) { // Check for duplicate users
					$_SESSION['error'] = "A user has already been registered with this email address.";
					$_SESSION['inputErr']['dupEmail'] = 1;
				} else { // No duplicates found so add to database and generate emails
					$username = $newUser->createNewUser(); // Generate username and set privilege ID to 15 (FOR TEST PURPOSES)
					$newUser->setPrivilege( 15 );
                    $password = $newUser->generatePassword(); // Generate new password
					$newUser->setPassword( $password ); // Set it for the user
                    $admin = new eCRFUser(1);
					$email = $newUser->writeEmail( $include, $admin, $password );
					$mail = $trial->sendEmail( $email );
					if ( $mail ) {
                        $_SESSION['message'] = "You have been registered for the test site.  Your username is {$username} and your password is {$password}";
						$newUser->saveToDB();
						header( "Location:index.php" );
						exit();
					} else {
						$_SESSION['error'] = "There has been an error in sending email, however your username is {$username} and your password is {$password}";
                        $newUser->saveToDB();
					}
				}
			}
		}
		$_SESSION[$include] = $newUser;
		header( "Location:index.php?page={$include}" );
		exit();
		break;
    case 'siteinfo':
        $centre = new Centre( $user->getCentre() );
        $complete = $trial->addUserInput( $_POST, $centre );
        $centre->saveToDB();
        if ( $complete && !isset($_SESSION['inputErr']) ) {
            $newPage = 'index';
            $_SESSION['message'] = 'Thank you for completing the site information.';
        } else {
            $newPage = $page;
        }
        header( "Location:index.php?page={$newPage}" );
        exit();
        break;
	case 'forgotpass':
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $postData = array('secret' => '6LcEFQ8TAAAAAPv9Mt58PDA9mcBt3vhhDtevEc-v', 'response' => $_POST['g-recaptcha-response']);

        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($postData),
            ),
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        $resp = json_decode($result);

        if ( !$resp->success ) {
            // What happens when the CAPTCHA was entered incorrectly
            $_SESSION['error'] = "The CAPTCHA was entered incorrectly. Please try again.";
        } else {
            $data = new eCRFUser();
            $complete = $trial->addUserInput($_POST, $data);
            if ($complete) { // Has all the data been added correctly?
                $id = $data->isUser(); // Check if entered user exists
                if ($id) {
                    $user = new eCRFUser($id);
                    $user->forgotPassword(
                    ); // Then set privilege ID to 99 and delete password to prevent the account from being used
                    $email = $user->writeEmail($include); // Generate email to their local admin to revalidate them
                    $mail = $trial->sendEmail($email);
                    if ($mail) {
                        $user->saveToDB();
                        $_SESSION['message'] = 'Thank you. You will receive an email with your new password once your reset instructions have been confirmed.'; // return with success message
                        header("Location:index.php");
                        exit();
                    } else {
                        $_SESSION['error'] = 'There has been an error in sending email to your local admin, please try again.';
                    }
                } else {
                    $_SESSION['error'] = "User not found in database, please check for spelling mistakes and retry."; // Else return with 'not exists' message
                }
            }
        }
		if ( isset($data) ) $_SESSION[$include] = $data;
		header( "Location:index.php?page={$include}" );
		exit();
		break;
	case 'createuser':
		$data = new eCRFUser();
		$complete = $trial->addUserInput( $_POST, $data );
		if ( $complete ) { // Has all the data been added correctly?
			$id = $data->isUser(); // Check if entered user exists
			if ( !$id ) { // If it doesn't then crack on and generate them
				$data->createNewUser(); // Generate username			
				$data->setPassword( $data->tempPass, $trial->user->getKey() ); // Set password now we have a username
				$email = $data->writeEmail( $include ); // Generate email to me with username and password
				$mail = $trial->sendEmail( $email );
				if ( $mail ) {				
					$data->saveToDB();
					$_SESSION['message'] = "Thank you. User {$data->username} created."; // return with success message
					header( "Location:index.php" );
					exit();
				} else {
					$_SESSION['error'] = 'There has been an error in sending email to your local admin, please try again.';
				}
			} else {
				$_SESSION['error'] = "User already found in database, please retry."; // Else return with 'not exists' message
			}
		}
		$_SESSION[$include] = $data;
		header( "Location:index.php?page={$include}" );
		exit();
		break;
    case 'usereg':
        if ( isset( $_POST['userID'] ) 
                && is_numeric($_POST['userID'] )
                ) {
            $userEdit = new eCRFUser( $_POST['userID'] );
            if ( $userEdit->getPrivilege() > 90
                    && $_POST['usereg-privilege_id'] < 90 ) {
                $_SESSION['error'] = "You must use the User Admin screen to authorise new users.";
            } elseif ( $userEdit->getPrivilege() < $user->getPrivilege() || $user->getPrivilege() > $_POST['usereg-privilege_id'] ) {
                $_SESSION['error'] = "You cannot edit a user with greater privilege than yourself.";
            } elseif ( $userEdit->getCentre() != $user->getCentre() && $user->isLocal() ) {
                $_SESSION['error'] = "You cannot edit users from other centres.";
            } elseif ( $userEdit->getCountry() != $user->getCountry() && !$user->isCentralAdmin() ) {
                $_SESSION['error'] = "You cannot edit users from other countries.";
            } elseif ( $userEdit->getID() !== $user->getID() && $_POST['deleteUser'] === '1' ) {
                $userEdit->deleteUser();
                $_SESSION['message'] = "You have deleted the user.";
            } else {
                if ( $trial->addUserInput( $_POST, $userEdit ) ) {
                    if ( $_POST['usereg-revalUser'] === '1' ) {
                        $password = $userEdit->generatePassword(); // Generate new password
                        $userEdit->setPassword( $password, $user->getKey() ); // Set it for the user
                        $email = $userEdit->writeEmail( 'created', $user, $password ); // Send email with username and password
                        $mail = $trial->sendEmail( $email );
                    }
                    $userEdit->saveToDB();
                    $_SESSION['message'] = "You have updated the user's details.";
                } else {
                    $_SESSION['error'] = "An error has occurred, please try again.";
                }
            }
        } else {
            $_SESSION['error'] = "An error has occurred, please try again.";
        }
        break;
    case 'sitereg':
        if ( isset( $_POST['centreID'] ) 
                && is_numeric($_POST['centreID'] )
                ) {
            $centreEdit = new Centre( $_POST['centreID'] );
            if ( $user->getPrivilege() >= 10 ) {
                $_SESSION['error'] = "You do not have the privilege to edit centres.";
            } elseif ( $centreEdit->getCountry() != $user->getCountry() && !$user->isCentralAdmin() ) {
                $_SESSION['error'] = "You cannot edit centres from other countries.";
            } elseif ( $_POST['deleteCentre'] === '1' && $user->isCentralAdmin() ) {
                $centreEdit->deleteCentre();
                $sql = "DELETE FROM centreUnits WHERE centre_id = ?";
                $pA = array('i', $centreID);
                DB::query($sql,$pA);
                $_SESSION['message'] = "You have deleted the centre.";
            } elseif ( $user->isCentralAdmin() && $_POST['toggleLock'] === '1' ) {
                $centreEdit->toggleLock();
                if ( $centreEdit->isLocked() ) {
                    $_SESSION['message'] = "The centre is now locked for data entry.";
                } else {
                    $_SESSION['message'] = "The centre is now open for data entry.";
                }
            } else {
                if ( $trial->addUserInput( $_POST, $centreEdit ) ) {
                    $centreID = $centreEdit->saveToDB();
                    if (isset($_POST['units'])) {
                        $sql = "DELETE FROM centreUnits WHERE centre_id = ?";
                        $pA = array('i', $centreID);
                        DB::query($sql,$pA);
                        $sql = "INSERT INTO centreUnits ( centre_id, units_id )
                        VALUES ( ?, ? )";
                        $pA = array();
                        foreach ($_POST['units'] as $unitName => $unitChoice) { // Get the units that could be changed by the query
                            $pA[] = array('si', $centreID, $unitChoice);
                        }
                        $unitAdd = DB::query($sql, $pA);
                    }
                    $sql = "UPDATE centre
                    SET option_text = name
                    WHERE id = ?";
                    $pA = array('i',$centreID);
                    DB::query($sql,$pA);
                    $_SESSION['message'] = "You have updated the centre's details.";
                } else {
                    $_SESSION['error'] = "An error has occurred, please try again.";
                }
            }
        } else {
            $_SESSION['error'] = "An error has occurred, please try again.";
        }
        break;
    case 'addsite':
        $newCentre = new Centre();
        if ( $user->isCentralAdmin() ) {
            if ( $trial->addUserInput( $_POST, $newCentre ) ) {
                $lock = 1;
                $newCentre->set('locksite', $lock);
                $centreID = $newCentre->saveToDB();
                if (isset($_POST['units'])) {
                    $sql = "DELETE FROM centreUnits WHERE centre_id = ?";
                    $pA = array('i', $centreID);
                    $sql = "INSERT INTO centreUnits ( centre_id, units_id )
                        VALUES ( ?, ? )";
                    $pA = array();
                    foreach ($_POST['units'] as $unitName => $unitChoice) { // Get the units that could be changed by the query
                        $pA[] = array('si', $centreID, $unitChoice);
                    }
                    $unitAdd = DB::query($sql, $pA);
                }
                $sql = "UPDATE centre
                    SET option_text = name,
                    option_value = id,
                    option_order = id,
                    weight = 1
                    WHERE id = ?";
                $pA = array('i',$centreID);
                DB::query($sql,$pA);
                $_SESSION['message'] = "You have added {$newCentre->name}.";
                unset($_SESSION['newCentre']);
            } else {
                $_SESSION['newCentre'] = $newCentre;
                $_SESSION['error'] = "Please complete all the fields correctly.";
                header( "Location:index.php?page=addsite" );
                exit();
            }
        } else {
            $_SESSION['error'] = "You do not have the authority to add a site.";
        }
        break;
	case 'searchpt':
        // First checks to see if centre and trial ID have been entered to search
        // Else checks to see if link ID radio button had been selected
        // Else gives an error
		if( isset( $_POST['searchpt-trialid'] ) ) {
			$trialid = $_POST['searchpt-trialid'];
            if ( $user->isRegional() ) {
                $trialid = substr_replace( $trialid, str_pad($user->getCentre(),3,'0',0), 0, 3 );
            }
			$sql = "SELECT id FROM core WHERE trialid = ?"; // Select correct link id via core table
			$pA = array( 's', $trialid );
            $result = DB::query( $sql, $pA );
			if ( $result->getRows() ) {
				if( is_numeric( $result->id ) ) {
					$sql = "SELECT id FROM link WHERE core_id = ?";
					$pA = array( 'i', $result->rows[0]->id );
                    $result = DB::query( $sql, $pA );
					if( $result->getRows() ) {					
						$link_id = $result->rows[0]->id;
					} else $_SESSION['error'] = "An error has occurred, please try again.";
				} else $_SESSION['error'] = "An error has occurred, please try again.";
			} else $_SESSION['error'] = "Your search returned no patients, please try again.";
		} else if ( isset( $_POST['searchpt-link_id'] ) ) {
            // Gets the offered link id
			$linkid = $_POST['searchpt-link_id'];
            // Searches the link table to ensure it is a real one
			$sql = "SELECT link.id AS id, core.centre_id AS centre_id, centre.country_id FROM link 
                LEFT JOIN core ON link.core_id = core.id 
				LEFT JOIN centre ON core.centre_id = centre.id 
                WHERE link.id = ?";
			$pA = array( 'i', $linkid );
            $result = DB::query( $sql, $pA );
			if ( $result->getRows() ) {
				$userCentre = new Centre( $user->getCentre() );
                if ( $user->isCentralAdmin() || ( $user->isRegionalAdmin() && $userCentre->get('country_id') == $result->country_id ) || $result->centre_id == $user->getCentre() ) {
                    $link_id = $result->id;		
                } else $_SESSION['error'] = "You cannot access patients from other centres.";
			} else $_SESSION['error'] = "Provided record ID not found, please try again.";
		} else {
			$_SESSION['error'] = "As error has occurred, please try again.";
		}
		if ( isset( $link_id ) ) {
			$sql = "SELECT studygroup FROM core LEFT JOIN link ON core.id = link.core_id WHERE link.id = ?";
			$pA = array('i', $link_id );
			$result = DB::query( $sql, $pA );
			if ( is_null($result->rows[0]->studygroup) ) {
				$unassigned = true;
			} else {
				$unassigned = false;
			}
			$sql = "SELECT id FROM user WHERE link_id = ? AND user.id != ?";
			$pA = array( 'ii', $link_id, $user->getID() );
			$result = DB::query( $sql, $pA );
			if ( $result->getRows() ) {
				$_SESSION['error'] = "This record is currently opened by another user.";
			} else {
				$trial->user->linkRecord( $link_id );
				if ( $unassigned ) {
					header( "Location:index.php?page=addpt&keepData=1" );
					exit();
				}
                if ( isset( $_POST['searchpt-action'] ) ) {
                    switch( $_POST['searchpt-action'] ) {
                        case 'withdraw':
                            header( "Location:index.php?page=discontinue&keepData=1");
                            exit();
                            break;
                        case 'ae':
                            header( "Location:index.php?page=adverseevent&keepData=1");
                            exit();
                            break;
                        case 'violation':
                            header( "Location:index.php?page=violation&keepData=1");
                            exit();
                            break;
                        case 'unsign':
							if ( $trial->user->canUnsign() ) {
								$trial->addRecord();
								$trial->record->unsignRecord();
								$trial->record->unPreSignRecord();
								$_SESSION['message'] = "The record has been unsigned and opened for editing.";
							} else {
								$_SESSION['error'] = "You do not have permission to unsign records.";
								header( "Location:index.php");
								exit();
							}                           
                            break;
                    }
                }
                if ( isset( $_POST['sign'] ) ) {
                    header( "Location:dataentry.php?page=signpt" );
                    exit();
                } else {
                    header( "Location:dataentry.php?page=core" );
                    exit();
                }
			}
		}
		header( "Location:index.php?page={$include}" );
		exit();
		break;
	case 'useradm':
		if ( isset( $_POST['useradm_id'] ) ) {
			foreach( $_POST['useradm_id'] as $id ) {
				$newUser = new eCRFUser( $id );
                if ( isset( $_POST['admin']) && $_POST['admin'] == 'admin' && !$user->isRegional() ) {
                    $newPriv = 10;
                } else {
                    $newPriv = 15;
                }
				if ( $user->isRegional() && $newUser->getCentre() != $user->getCentre() ) { // Check not trying to validate someone elses users
					$_SESSION['error'] = "You can only validate users from your own centre";
				} else if ( !$newUser->email || ( $newPriv == 10 && $newUser->getPrivilege() == 99 ) || ( $newPriv == 15 && $newUser->getPrivilege() == 98 ) ) {
                    $_SESSION['error'] = "An error has occurred.";
                } else {
					$newUser->setPrivilege( $newPriv ); // Data entry privilege
					$password = $newUser->generatePassword(); // Generate new password
					$newUser->setPassword( $password, $user->getKey() ); // Set it for the user
					$email = $newUser->writeEmail( 'created', $user, $password ); // Send email with username and password
					$mail = $trial->sendEmail( $email );
					if ( $mail ) {
                        $newUser->saveToDB();
						if ( isset( $_SESSION['message'] ) ) {
                            $_SESSION['message'] = "Users successfully validated.";
						} else {
							$_SESSION['message'] = "User successfully validated.";
						}
					} elseif ( $_SERVER['HTTP_HOST'] == 'localhost' ) {
                        $_SESSION['message'] = "User validated - Password {$password}";
                        $newUser->saveToDB();
                    } else {
						$_SESSION['error'] = "An error has occurred sending email, please try again.";
					}
				}
			}
		}
		if ( isset( $_SESSION['error']  ) ) {
			header( "Location:index.php?page={$include}" );
			exit();
		}
		break;
	case 'postnews':
		if ( isset( $_POST['postnews-title'] ) && isset( $_POST['postnews-content'] ) ) {
			$sql = "INSERT INTO news ( title, content, user_id, time ) VALUES ( ?, ?, ?, NOW() )";
			$pA = array( 'ssi', $_POST['postnews-title'], $_POST['postnews-content'], $user->getID() );
            $result = DB::query( $sql, $pA );
			if ( $result->getRows() ) {
				$_SESSION['message'] = "You have successfully added a news item.";
			} else {
				$_SESSION['error'] = "An error has occurred, please try again.";
			}
		}
		break;
    case 'dlsite':
    case 'dldb':
        if ( isset( $_POST['encrypted']) && $_POST['encrypted'] == 1 
               && ( !isset( $_POST['password']) || !$user->checkPassword($_POST['password']) ) ) {
            $_SESSION['error'] = "You must enter your password to download patient identifiable data.";
            header('Location:index.php?page=' . $include);
            exit();
        } else {
            $encrypted = 0;
            if ( isset( $_POST['encrypted']) && $_POST['encrypted'] == 1 ) {
                $encrypted = 1;
            }
            
            $sql = "SELECT link.id FROM link LEFT JOIN 
                core ON core.id = link.core_id";
            if ( $include == 'dlsite' ) {
                $sql .= " WHERE centre_id = ?";
                $pA = array('i',$user->getCentre());
                $dataQuery = DB::query($sql, $pA);
                $data = $user->getCentre('name');
            } else {
                $dataQuery = DB::query($sql);
                $data = 'all';
            }
            
            $numRows = $dataQuery->getRows();
            
            $sql = "INSERT INTO downloadLog ( user_id, ip, numRows, encrypted, data ) VALUES ( ?, ?, ?, ?, ? )";
            $pA = array('isiis', $user->getID(), $_SERVER['REMOTE_ADDR'], $numRows, $encrypted, $data );
            
            DB::query( $sql, $pA );
            
            $headRecord = new Record( $dataQuery->rows[0]->id );
            
            $sql = "SELECT formFields.id as fieldID, labelText, pages_name, pages.label as pageLabel, fieldName, 
                formFields.type, pages.id as pageID FROM formFields 
                LEFT JOIN pages ON pages.name = pages_name 
                WHERE dataName = 'record'
                AND ( formFields.type != 'heading' AND formFields.type != 'data' )
                ORDER BY pageOrder, pages_name, entryorder";
            $result = DB::query( $sql );

            $output = "";
            $selectFields = array();
            $cbFields = array();

            foreach ( $result->rows as $row ) {
                if ( $include === 'dlsite' && !$trial->parseBranches($row->pageID, $headRecord->getID() ) ) 
                    continue;
                if ( !$encrypted && $row->fieldName !== 'age' 
                        && $headRecord->isFieldEncrypted($row->pages_name, $row->fieldName) )
                    continue;
                switch ($row->type) {
                    case 'checkbox':
                        $sql = "SELECT value FROM formVal
                            WHERE operator = 'IN LIST' AND
                            formFields_id = ?";
                        $pA = array('i', $row->fieldID );
                        $valueTable = DB::query($sql, $pA);
                        $sql = "SELECT id, name FROM {$valueTable->value}";
                        $checkboxes = DB::query($sql);          
                        foreach($checkboxes->rows as $cb){
                            $cbFields[$row->pages_name][$row->fieldName][] = $cb->id;
                            $output .= '"' . $row->pages_name . '_' . $row->labelText . '_' . $cb->name . '",';
                        }
                        break;
                    case 'select':
                    case 'radio':
                        $sql = "SELECT value FROM formVal
                            WHERE operator = 'IN LIST' AND
                            formFields_id = ?";
                        $pA = array('i', $row->fieldID );
                        $valueTable = DB::query($sql, $pA);
                        $sql = "SELECT id, name FROM {$valueTable->value}";
                        $select = DB::query($sql);
                        foreach( $select->rows as $sel ){
                            $selectFields[$row->pages_name][$row->fieldName][$sel->id]=$sel->name;
                        }
                    default:
                        $output .= '"' . $row->pages_name . '_' . $row->labelText . '",';
                        break;
                }
            }

            rtrim($output, ",");

            $output .= "\r\n";
            
            
            
            foreach ( $dataQuery->rows as $dataRow ) {
            $line = Array();
            $record = new Record( $dataRow->id );
            foreach ( $result->rows as $row ) {
                if ( $include === 'dlsite' && !$trial->parseBranches($row->pageID, $record->getID() ) ) continue;
                $field = $record->getField($row->pages_name,$row->fieldName);
                if ( !$encrypted && $record->isFieldEncrypted($row->pages_name, $row->fieldName) ) {
                    if ( $row->fieldName === 'age' ) {
                        if ( $field > 90 )
                            $field = 'Over 90';                            
                    } else
                        continue;
                }                
                switch ($row->type) {
                    case 'checkbox':         
                        foreach($cbFields[$row->pages_name][$row->fieldName] as $cb){
                            if ( emptyInput($field) ) {
                                $line[] = "";
                                continue;
                            }
                            if(in_array($cb,$field)){
                                $line[] = "1";
                            } else {
                                $line[] = "0";
                            }
                        }
                        break;
                    case 'select':
                    case 'radio':
                        if(isset($selectFields[$row->pages_name][$row->fieldName][$field])){
                            $line[] = '"' . $selectFields[$row->pages_name][$row->fieldName][$field] . '"';
                        } else {
                            $line[] = " ";
                        }
                        break;
                    case 'yesno':
                        if ( $field === 1 ) {
                            $line[] = 'Yes';
                        } else if ( $field === 0 ) {
                            $line[] = 'No';
                        } else {
                            $line[] = $field;
                        }
                        break;
                    case 'text':
                    case 'textarea':
                        $line[] = '"' . $field . '"';
                        break;
                    default:
                        $line[] = $field;
                        break;
                }
            }
            reset($result->rows);
            $output .= implode(',',$line);

            $output .= "\r\n";
            unset( $record );
            }
            header('Pragma: public');
            header('Expires: -1');
            header('Content-Transfer-Encoding: binary');
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="eCRFData.csv"');
            header('Cache-Control: max-age=0');

            // output the file
            echo $output;
            exit();
        }
        break;
    case 'ukcrn':
        $output = "Study Identifier,Study Acronym,Site Identifier,Site Name,Activity Date,Participant Type,Unique Participant ID,Activity Type\r\n";
        $sql = "SELECT link.id FROM link
  LEFT JOIN core ON link.core_id = core.id
  LEFT JOIN centre ON core.centre_id = centre.id
  WHERE centre.country_id = 30
  ORDER BY link.id";
        $query = DB::query($sql);
        $count = 1;
        foreach( $query->rows as $row ) {
            $record = new Record($row->id);
            $record->getAllData();
            $centre = new Centre($record->getCentre());
            $output .= "20252,PRISM,,";
            $output .= "{$centre->name},";
            $output .= $record->getField('core','randdate') . ',';
            $output .= "Participant with the relevant condition,";
            $output .= $record->getField('core','trialid') . ',';
            $output .= "Recruitment";
            $output .= "\r\n";
        }
        $date = date('Y-m-d');
        $filename = "CPMS.{$date}.csv";
        header('Pragma: public');
        header('Expires: -1');
        header('Content-Transfer-Encoding: binary');
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');
        echo $output;
        exit();
        break;
    case 'countdb':
        if ( !isset( $_POST['country_id'] ) ) {
            $_SESSION['error'] = "You must select a country to download.";
            header('Location:index.php?page=' . $include);
            exit();
        } else {
            $country = $_POST['country_id'];
        }
    case 'newdldb':
        if ( isset( $_POST['encrypted']) && $_POST['encrypted'] == 1 
               && ( !isset( $_POST['password']) || !$user->checkPassword($_POST['password']) ) ) {
            $_SESSION['error'] = "You must enter your password to download patient identifiable data.";
            header('Location:index.php?page=' . $include);
            exit();
        } else {
            $encrypted = 0;
            if ( isset( $_POST['encrypted']) && $_POST['encrypted'] == 1 ) {
                $encrypted = 1;
            }
            
            $sql = "SELECT COUNT(id) as numRows FROM all_data";
            if ( $include == 'countdb' ) {
                $sql .= " WHERE country_id = ?";
                $pA = array('i',$country);
                   $num = DB::query($sql, $pA);
            } else {
            
                $num = DB::query($sql);
            }
            $numRows = $num->numRows;
            
            $numPages = (int) ( $numRows / 1000 );
            
            for ( $i = 0; $i <= $numPages; $i++ ) {
            
                $sql = "SELECT * FROM all_data";
                if ( $include == 'dlsite' ) {
                    $sql .= " WHERE centre_id = ?";
                    $pA = array('i',$user->getCentre());
                    $dataQuery = DB::query($sql, $pA);
                    $data = $user->getCentre('name');
                } else if ( $include == 'countdb' ) {
                    $sql .= " WHERE country_id = ?";
                    $startRec = $i*1000;
                    $sql .= " LIMIT {$startRec},1000";
                    $pA = array('i',$country);
                    $dataQuery = DB::query($sql, $pA);
                    $data = 'country';
                } else {
                    $startRec = $i*1000;
                    $sql .= " LIMIT {$startRec},1000";
                    $dataQuery = DB::query($sql);
                    $data = 'all';
                }
                
                if ( $i == 0 ) {
					if ( !$dataQuery->getRows() ) {
						$_SESSION['error'] = "No records from that country available.";
						header('Location:index.php?page=' . $include);
						exit();
					}
                    $sql = "INSERT INTO downloadLog ( user_id, ip, numRows, encrypted, data ) VALUES ( ?, ?, ?, ?, ? )";
                    $pA = array('isiis', $user->getID(), $_SERVER['REMOTE_ADDR'], $numRows, $encrypted, $data );

                    DB::query( $sql, $pA );

                    $headRecord = new Record( $dataQuery->rows[0]->id );
                    $skipFields = array();
                    $encryptedFields = array();

                    $sql = "SELECT formFields.id as fieldID, labelText, pages_name, pages.label as pageLabel, fieldName, 
                        formFields.type, pages.id as pageID, dl_name FROM formFields 
                        LEFT JOIN pages ON pages.name = pages_name 
                        WHERE dataName = 'record'
                        AND ( formFields.type != 'heading' AND formFields.type != 'data' )
                        ORDER BY pageOrder, pages_name, entryorder";
                    $result = DB::query( $sql );

                    $output = "'Signed',";
                    $selectFields = array();
                    $cbFields = array();

                    foreach ( $result->rows as $row ) {
                        if ( $include === 'dlsite' && !$trial->parseBranches($row->pageID, $headRecord->getID() ) ) {
                            $skipFields[] = $row->fieldID;
                            continue;
                        }
                        if ( $headRecord->isFieldEncrypted($row->pages_name, $row->fieldName) ) {
                            $encryptedFields[] = $row->fieldID;
                            if ( !$encrypted && $row->fieldName !== 'age' ) {
                                $skipFields[] = $row->fieldID;
                                continue;
                            }
                        }
                        switch ($row->type) {
                            case 'checkbox':
                                $sql = "SELECT value FROM formVal
                                    WHERE operator = 'IN LIST' AND
                                    formFields_id = ?";
                                $pA = array('i', $row->fieldID );
                                $valueTable = DB::query($sql, $pA);
                                $sql = "SELECT id, name FROM {$valueTable->value}";
                                $checkboxes = DB::query($sql);          
                                foreach($checkboxes->rows as $cb){
                                    $cbFields[$row->pages_name][$row->fieldName][] = $cb->id;
                                    $output .= '"' . $row->pages_name . '_' . $row->labelText . '_' . $cb->name . '",';
                                }
                                break;
                            default:
                                $output .= '"' . $row->pages_name . '_' . $row->labelText . '",';
                                break;
                        }
                    }

                    rtrim($output, ",");

                    $output .= "\r\n";
                }

                foreach ( $dataQuery->rows as $dataRow ) {
                $line = Array();
                $line[] = $dataRow->signed;
                foreach ( $result->rows as $row ) {
                    $field = NULL;
                    if ( in_array($row->fieldID,$skipFields) ) continue;
                    if( isset( $dataRow->{$row->dl_name} ) ) $field = $dataRow->{$row->dl_name};
                    if ( in_array($row->fieldID,$encryptedFields) && !is_null($field) ) {
                        $td = new Encrypt($_SESSION['user']->getKey());
                        $field = $td->decrypt($field);
                        if ( !$encrypted && $row->fieldName === 'age' ) {
                            if ( $field > 90 ) {
                                $field = 'Over 90';
                            }
                        }
                    } 
                    switch ($row->type) {
                        case 'checkbox':
                            $sql = "SELECT {$row->fieldName}_id as cbVal FROM {$row->pages_name}{$row->fieldName} WHERE {$row->pages_name}_id = ?";
                            $pA = array( 'i', $dataRow->{$row->pages_name . '_id'});
                            $cbVal = DB::query($sql, $pA);
                            $field = $cbVal->getArray('cbVal');
                            foreach($cbFields[$row->pages_name][$row->fieldName] as $cb){
                                if ( emptyInput($field) ) {
                                    $line[] = "";
                                    continue;
                                }
                                if(in_array($cb,$field)){
                                    $line[] = "1";
                                } else {
                                    $line[] = "0";
                                }
                            }
                            break;
                        case 'text':
                        case 'textarea':
                            $line[] = '"' . $field . '"';
                            break;
                        default:
                            $line[] = '"' . $field . '"';
                            break;
                    }
                }
                reset($result->rows);
                $output .= implode(',',$line);

                $output .= "\r\n";
                unset( $record );
                }
            }
            header('Pragma: public');
            header('Expires: -1');
            header('Content-Transfer-Encoding: binary');
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="eCRFData.csv"');
            header('Cache-Control: max-age=0');

            // output the file
            echo $output;
            exit();
        }
        break;
    case 'locksite':
        if ( isset($_POST['lockSite']) ) {
            $centre = new Centre( $user->getCentre() );
            $centre->lockSite();
            $_SESSION['message'] = "You have locked your site for data entry, you may now download your data from Admin->Download your data";
        }
        break;
    case 'certs':
        addIncludePath('/addons/tfpdf');
        addIncludePath('/addons/fpdi');

        // Check first for if the confirmation code is being issued
        $certificate = "lead";
        $name = $user->forename . ' ' . $user->surname;

        // map FPDF to tFPDF so FPDF_TPL can extend it
        class FPDF extends tFPDF {
            protected $_tplIdx;
        }

        $pdf = new FPDI(); 

        if ( $user->getPrivilege() <= 10 ) {
            $source = "docs/certificatelead.pdf";
        } else {
            $source = "docs/certificate.pdf";
        }

        $pagecount = $pdf->setSourceFile($source);

        $tplidx = $pdf->importPage(1); 

        $pdf->addPage('L'); 
        $pdf->useTemplate($tplidx, 0, 0, 0 );

        $date = date('jS F Y');
        $pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
        $pdf->SetFont('DejaVu','',30); 
        $pdf->SetTextColor(0,0,0); 
        $pdf->SetXY(0, 95);
        $pdf->Cell(297, 15, $name, 0, 0, 'C', 0);
        $pdf->SetFont('Arial','',18); 
        $pdf->SetXY(170, 160);
        $pdf->Write(0, $date); 

        $attachment = $pdf->output('Certificate.pdf', 'S');
        header('Pragma: public');
        header('Expires: -1');
        header('Content-Transfer-Encoding: binary');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment;filename="ISOS%20Certificate.pdf"');
        header('Cache-Control: max-age=0');

        // output the file
        echo $attachment;
        exit();
        break;
    case 'violationlist':
        $v = new Violation($_POST['vSelect']);
        $v->makeInactive();
        $v->saveToDB();
        $_SESSION['message'] = "You have successfully removed the violation form.";
        break;
    case 'aelist':
        $ae = new AdverseEvent($_POST['aeSelect']);
        $ae->makeInactive();
        $ae->saveToDB();
        $_SESSION['message'] = "You have successfully removed the adverse event form.";
        break;
	default:
		$_SESSION['error'] = "An error has occurred, please try again. Code - 0x01";
		break;
}
header( "Location:index.php" );
exit();
?>