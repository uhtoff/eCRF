<?php
if ( php_sapi_name() !== 'cli' ) {
	header('Location:index.php');
	exit();
}
require_once('setup.php');
session_start();

if ( isset( $argv[1] ) && ctype_alnum( $argv[1] ) ) { // If someone tries to send something odd then just go to default
	$page = $argv[1];
} else {
	$page = NULL;
}
$loggedIn = false;
$trial = new eCRF( $page ); // Create trial object
$user = new eCRFUser(11);
$trial->addUser($user);
$_SESSION['user'] = $user;
if ( $page ) {
    $include = basename( $page ); // Should be unneccesary, but you never know!
    require( "./includes/{$include}.php" );	
}
?>