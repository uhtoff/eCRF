<?php
require_once( 'setup.php' );
session_start();

$page = "addpt";
$trial = new eCRF( $page );

if ( isset( $_SESSION['user'] ) ) {
    $user = $_SESSION['user'];
    $loggedIn = $trial->addUser( $user );
} else {
    $loggedIn = false;
}

if ( !$loggedIn ) {
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

$include = $trial->checkPageLogin( $page );

if ( !$include ) {
	$_SESSION['error'] = "Unauthorised access attempted.";
	header( "Location:index.php" );
	exit();
}

// Reset page to core rather than the addpt used for security
// Allows for different permissions to add confidential patient data and to view it

$page = 'core';

$trial->setPage($page);

$trial->addRecord();

$data = $trial->record->getData( $page );

if ( $trial->user->isRegional() && $_POST['core-centre_id'] != $trial->user->getCentre() ) {
	$_SESSION['error'] = 'You are not authorised to enter records for other centres.';
	$complete = false;
} elseif ( !isset($_SESSION['newTrialID']) || $_POST['core-trialid'] != $_SESSION['newTrialID'] ) {
    $_SESSION['error'] = "Please use the " . Config::get('idName') . " as generated for you, this will prevent ID collisions.";
	$complete = false;
} else {
    $centre = new Centre($_POST['core-centre_id']);
    if ( $centre->isLocked() ) {
        $_SESSION['error'] = 'This centre is locked for any data entry.';
        $complete = false;
    } else {
//        $_POST['core-trialid'] = substr_replace($_POST['core-trialid'], str_pad($_POST['core-centre_id'],3,'0',0), 0, 3);
        $complete = $trial->addUserInput( $_POST, $data );
        if ( $trial->record->checkDuplicate() ) {
            $_SESSION['error'] = "A patient has already been entered from that centre with that " . Config::get('idName') . ".";
            $complete = false;
        }
    }
}

$trial->user->linkRecord( $trial->record->saveToDB() );

unset( $_SESSION['newTrialID']);
if ( $complete ) {
	$trial->setStudyGroup();
	$trial->record->saveToDB();
	$sGName = $trial->record->getData('core')->get('studygroup') ? 'CPAP Study' : 'Control';
	$message = "<h3>You have successfully randomised a patient to the {$sGName} group.</h3>";
    $email = $trial->generateRandomisationEmail();
    $sent = $trial->sendEmail($email);
    $email = $trial->generateCentralRandomisationEmail();
    if ( $email ) $trial->sendEmail($email);
    if ( $sent ) {
        $message .= "<p>Please note down their trial ID ({$data->trialid}) and group assignment, you should have received an email with this information.</p>";
    } else {
        $message .= "<p>Please note down their trial ID ({$data->trialid}) and group assignment, there has been an error in sending emails, so please ensure you record this.</p>";
    }
    if ( $sGName == 'Control' ) {
        $_SESSION['info'] = $message;
    } else {
        $_SESSION['message'] = $message;
    }
	header( 'Location:index.php' );
} else {
	header( "Location:index.php?page={$_POST['return']}&keepData=1" );
}
?>