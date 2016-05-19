<?php
require_once( 'setup.php' );
session_start();

if ( isset( $_SESSION['user'] ) && isset( $_POST['page'] ) ) {
	$user = $_SESSION['user'];
	$page = $_POST['page'];
	$trial = new eCRF( $page );
	$loggedIn = $trial->addUser( $user );
	if ( !$loggedIn ) {
		$_SESSION['error'] = "Log in expired due to inactivity";
		header( "Location:index.php?expire=1" );
		exit();
	}
} else {
	$_SESSION['error'] = "Log in expired due to inactivity";
	header( "Location:index.php?expire=1" );
	exit();
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

$link_id = $trial->addRecord();

if ( !$link_id ) {
	$_SESSION['error'] = "No record linked to this user.";
	header( 'Location:index.php' );
	exit();
} elseif ( !isset($_POST['link_id']) || $link_id != $_POST['link_id'] ) {
    $_SESSION['error'] = "An error has occurred, please reselect the patient you wish to edit.";
    header('Location:index.php');
    exit();
}

$include = $trial->checkPageLogin( $page );

if ( !$include ) {
	$_SESSION['error'] = "Unauthorised access attempted.";
	header( "Location:index.php" );
	exit();
}

if ( $trial->user->isRegional() ) {
	if ( $trial->record->getCentre() != $trial->user->getCentre() ) {
		$_SESSION['error'] = 'You are not authorised to edit records for other centres.';
		$complete = false;
		header( "Location:index.php" );
		exit();
	} else if ( isset( $_POST['core-centre_id'] ) && $_POST['core-centre_id'] != $trial->user->getCentre() ) {
		$_SESSION['error'] = 'You are not authorised to change records to other centres.';
		$complete = false;
		header( "Location:index.php" );
		exit();
	}
}

$centre = new Centre($trial->record->getCentre());
if ( $centre->isLocked() && !$trial->user->isCentralAdmin() ) {
    $_SESSION['error'] = 'This centre is locked for data entry.';
    $complete = false;
    header( "Location:index.php" );
    exit();
}

if ( $page == 'signpt' ) {
    if ( isset( $_SESSION['returnTo'] ) ) {
        $return = "index.php?page={$_SESSION['returnTo']}";
    } else {
        $return = "index.php";
    }
    $presigned = $trial->record->isPreSigned();
	$signed = $trial->record->isSigned(); // To detect if there's a change in signed status
    if ( isset( $_POST['ignoreFlag']) ) {
        $trial->record->ignoreFlag();
        $_SESSION['message'] = 'The flags on this record will be ignored.';
        header( "Location:{$return}" );
        exit();
    }

    if ( isset( $_POST['deleteRecord']) && $trial->user->canDelete() ) {
        if ( $_POST['deletePassword'] && $trial->user->checkPassword( $_POST['deletePassword'] ) ) {
            $reason = isset($_POST['deleteReason']) ? $_POST['deleteReason'] : NULL;
            $trial->record->deleteAllData( $trial->user->getID(), $reason );
            $trial->user->unlinkRecord();
            $_SESSION['message'] = 'The record has been deleted.';
            $return = "index.php";
        } else {
            $_SESSION['error'] = 'You must enter your password correctly to delete the record.';
            $return = "dataentry.php?page=signpt";
        }
        header( "Location:{$return}" );
        exit();
    }
	$trial->addSignInput( $_POST );
	if ( $trial->record->isSigned() && $signed != $trial->record->isSigned() ) { // If record now signed and previously it was unsigned
        if ( isset( $_POST['incomplete']) && $_POST['comment'] == '' ) {
            $comment = 'Incomplete record';
            $trial->record->set( 'comment', $comment );
			$trial->record->saveToDB();
        }
        $_SESSION['message'] = "The record has been signed. Thank you.";
        
        header( "Location:{$return}" );
		exit();
	} elseif ( $trial->record->isPreSigned() && $presigned != $trial->record->isPreSigned() ) {
        if ( isset( $_POST['incomplete']) && $_POST['comment'] == '' ) {
            $comment = 'Incomplete record';
            $trial->record->set( 'comment', $comment );
			$trial->record->saveToDB();
        }
        $_SESSION['message'] = "The record has been marked as complete. Thank you.";
        header( "Location:{$return}" );
		exit();
    } elseif ( $trial->record->isPreSigned() && $signed ) {
        $trial->record->unPreSignRecord();
        header( "Location:dataentry.php?page=signpt" );
		exit();
    } else {
		header( "Location:dataentry.php?page=signpt" );
		exit();
	}
} else if ( $page === 'discontinue' ) {
    unset( $_SESSION['inputErr']);
	$complete = $trial->addUserInput( $_POST );
    if ( $complete ) {
        $trial->record->signRecord();
        $comment = 'Patient record discontinued';
        $trial->record->set('comment', $comment);
        $trial->record->saveToDB();
        $_SESSION['message'] = "This patient has now been withdrawn from the study.  Their record is now no longer editable.";
        header( "Location:index.php" );
        exit();
    } else {
        $_SESSION['error'] = "You must complete the form to discontinue a patient from the trial.  Please try again.";
        header( "Location:index.php" );
        exit();
    }
} else if ( $page === 'adverseevent' ) {
    unset($_SESSION['inputErr']);
    $ae = new AdverseEvent;
    $complete = $trial->addUserInput($_POST, $ae);
    $ae->makeActive();
    if ($complete) {
        $ae->saveToDB();
        $trial->addAdverseEvent($ae);
        $email = $trial->generateAdverseEventEmail();
        $trial->sendEmail($email);
        $_SESSION['message'] = "Thank you for recording this adverse event.";
    } else {
        $_SESSION['error'] = "You must complete the adverse event form in its entirety, please try again.";
    }
    header("Location:index.php");
    exit();
} else if ( $page === 'violation' ) {
        unset( $_SESSION['inputErr']);
        $v = new Violation();
        $complete = $trial->addUserInput( $_POST, $v );
        if ( $complete ) {
            if ( !$v->hasViolation() ) {
                $_SESSION['error'] = 'No deviation was selected. Please try again.';
            } else {
                $v->saveToDB();
                $trial->addViolation($v);
                $email = $trial->generateViolationEmail();
                $trial->sendEmail($email);
                $_SESSION['message'] = "Thank you for recording this protocol deviation.";
            }
        } else {
            $_SESSION['error'] = "You must complete the protocol deviation form in its entirety, please try again.";
        }
        header("Location:index.php");
        exit();
} else {
		$complete = $trial->addUserInput( $_POST );
		if ( $page == 'core' && $trial->record->checkDuplicate() ) {
			$_SESSION['error'] = "{$trial->record->getID()} - A patient has already been entered from that centre with that " . Config::get('idName') . ".";
			$complete = false;
		} else {
			$trial->record->saveToDB();
		}

		if ( $complete ) {
			$newPage = $trial->getNextPage();
		} else {
			$newPage = $page;
		}

		header( "Location:dataentry.php?page={$newPage}" );
		exit();
	}
?>