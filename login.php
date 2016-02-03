<?php
require_once( 'setup.php' );
session_start();

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

$sql = "SELECT id FROM failed_login WHERE ip_address = ? AND failed_time > ADDDATE(NOW(), INTERVAL -5 MINUTE)";
$ip_address = $_SERVER['REMOTE_ADDR'];
$pA = array('s',$ip_address);
$failures = DB::query($sql, $pA);
$num_failed = $failures->getRows();
$too_many_attempts = false;

if ( $num_failed > 3 ) {
    $_SESSION['error'] = 'Too many failed attempts from your location, please try again in 5 minutes.';
    $too_many_attempts = true;
}

if( !$too_many_attempts && isset( $_POST['username'] ) && isset( $_POST['password'] ) ) {
	$user = new eCRFUser();
	$login = $user->login( $_POST['username'], $_POST['password'] );
	if ( $login ) {
		$_SESSION['user'] = $user;
	}
} else {
    $login = false;
}

if ( $too_many_attempts || !$login) {
    $sql = "INSERT INTO failed_login ( username, ip_address, too_many, failed_time ) VALUES ( ?, ?, ?, ? )";
    $username = substr($_POST['username'],0,50);
    $failed_time = gmdate("Y-m-d H:i:s");
    $pA = array( 'ssis', $username, $ip_address, $too_many_attempts, $failed_time );
    DB::query($sql, $pA);
}

header( 'Location:index.php' );
?>