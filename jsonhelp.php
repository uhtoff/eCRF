<?php
require_once( 'setup.php' );
session_start();

// Valid user logged in check - if not to login page, if header change fails exit anyway
$loggedin = check_login();
if( !$loggedin ) {
	header( 'Location:vision.php' );
	exit();
}

$output = array();
$sql = "SELECT columnname, help FROM labels WHERE help IS NOT NULL AND tablename = ?";
$pA = array( 's', $_GET['page'] );
$result = DB::query( $sql, $pA );

foreach ( $result->rows as $row ) {
	$output[] = array( "fieldid" =>  $row->columnname,
						"help" => $row->help );
}

echo json_encode( $output );
?>