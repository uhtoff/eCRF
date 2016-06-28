<?php
if(count(get_included_files()) ==1) exit("Direct access not permitted.");
if ( php_sapi_name() === 'cli' ) {
	$path = '.';
} else {
	$path = $_SERVER['DOCUMENT_ROOT'];
}
require( $path . '/libs/serverconfig.php');

addIncludePath('/classes');
addIncludePath('/addons');

$trial = 'PRISM';
Config::set('userdb', $db);
Config::set('database', $db);
Config::set('trial', $trial);
Config::set('idName', 'PRISM ID');

if ( !DB::setDB($db) ) {
    exit( 'Unable to set database' );
}

require( 'ecrflib.php' );
require( 'mainlib.php' );
session_name('PRISM');
?>