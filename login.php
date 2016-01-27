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

if( isset( $_POST['username'] ) && isset( $_POST['password'] ) ) {
	$user = new eCRFUser();
	$login = $user->login( $_POST['username'], $_POST['password'] );
	if ( $login ) {
		$_SESSION['user'] = $user;
	}
}
header( 'Location:index.php' );
?>