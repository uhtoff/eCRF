<?php
require_once('setup.php');
session_start();
unset( $_SESSION['newTrialID']);
unset( $_SESSION['returnTo'] );
if ( isset( $_GET['expire'] ) ) {
	$_SESSION['error'] = "Log in expired due to inactivity";
    unset( $_SESSION['user'] );
}
if ( isset( $_GET['page'] ) && ctype_alnum( $_GET['page'] ) ) { // If someone tries to send something odd then just go to default
	$page = $_GET['page'];
} else {
	$page = NULL;
}
$loggedIn = false;
$trial = new eCRF( $page ); // Create trial object
if ( isset( $_SESSION['user'] ) && $_SESSION['user'] ) { // Add current user to trial object (if they exist)
	$user =& $_SESSION['user'];
	$loggedIn = $trial->addUser( $user ); // On adding user it checks to see if user has been expired and if not renews their last logon time
	if ( !$loggedIn ) {
        unset ( $_SESSION['user'] );
		header( "Location:index.php?expire=1" );
		exit();
	}
	if ( !isset($_GET['keepData']) && $trial->user->isLinked() ) {
        $trial->addRecord();
        $complete = $trial->checkComplete('core');
        if ( !$complete ) {
            $trial->record->deleteAllData($user->getID(),'Incomplete Randomisation',true);
            $_SESSION['error'] = "Your partially entered randomisation data has been deleted.  Please note the new trial ID when you go to re-enter.";
        }
		$trial->user->unlinkRecord(); // Unlink user from any records
	}
	$trial->addRecord();
}
$include = $trial->checkPageLogin( $page ); // Generate correct include file, assuming user has correct privilege
if ( $include == 'logout' ) {	
	$loggedIn = $include = false;
	$_SESSION['message'] = "You have been successfully logged out.";
    header('Location:index.php');
    exit();
}
$trial->writeHead($page);
echo "<body>";
echo "<div id=\"wrap\"";
if ( is_null($page) || $page == 'logout' )
{
	echo " class=\"background\"";
}
echo ">";
$trial->writeNavBar();
echo "<div class=\"container\">";
if ( isset( $_SESSION['error'] ) && $_SESSION['error'] ) {
	echo "<div class=\"alert alert-error\">";
	echo "<button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>";
	echo "{$_SESSION['error']}";
	echo "</div>";
	unset( $_SESSION['error'] );
}
if ( isset( $_SESSION['message'] ) && $_SESSION['message'] ) {
	echo "<div class=\"alert alert-success\">";
	echo "<button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>";
	echo "{$_SESSION['message']}";
	echo "</div>";
	unset( $_SESSION['message'] );
}
if ( isset( $_SESSION['info'] ) && $_SESSION['info'] ) {
    echo "<div class=\"alert alert-info\">";
    echo "<button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>";
    echo "{$_SESSION['info']}";
    echo "</div>";
    unset( $_SESSION['info'] );
}
if ( isset($user) && is_null( $page ) ) {
    $centre = new Centre($user->getCentre());
    if ( !$centre->infolock && $user->isLocalAdmin() && !$trial->checkComplete('siteinfo',$centre) ) {
        echo "<div class=\"alert alert-warning\">";
        echo "<button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>";
        echo "Please go to Admin → Edit Site Information and complete the one-time hospital information form there.";
        echo "</div>";
    }
}
if ( isset($user) && is_null( $page ) ) {
    $centre = new Centre($user->getCentre());
    if ( $centre->isLocked() ) {
        echo "<div class=\"alert alert-info\">";
        echo "<button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>";
        echo "Your centre is not currently opened for data entry.";
        echo "</div>";
    }
}
if ( $include ) {
    $include = basename( $include ); // Should be unneccesary, but you never know!
    require( "./includes/{$include}.php" );	
} else {
    echo $trial->getWelcome();
    if ( $trial->randomisationOffline() ) {
        echo "<h4>Apologies, but randomisation is currently paused, it should be resumed soon.</h4>";
    }
    if ( $trial->isOffline() ) {
        echo "<h2>The site is currently offline for maintenance.</h2>";
    } else if ( $loggedIn ) {
/*        $result = DB::query( "SELECT title, content, time, forename, surname FROM news INNER JOIN user ON news.user_id = user.id ORDER BY time DESC LIMIT 0, 4" );
        if ( $result->getRows() ) {
            echo <<<_END
            <div id="news">
                <h3>Latest news:</h3>
_END;
            $pattern = '/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/';
            foreach ( $result->rows as $row ) {
                preg_match( $pattern, $row->time, $date );
                $datetime = strtotime($row->time);
                $mysqldate = date("g:ia", $datetime ) . " on the " . date( "jS F, Y", $datetime);
                if( $row->title ) echo "<h4>{$row->title}</h4>";
                echo "<h5>Posted at {$mysqldate} by {$row->forename} {$row->surname}</h5>";
                echo "<article>" . nl2br( HTML::clean( $row->content ) ) . "</article>";
            }
            echo "</div>";
        }
*/
    } else {
    echo '<p>Please log in to access the site.</p>';
    }
}
echo "</div>"; // End container div
echo "<div id=\"push\"></div>";
echo "</div>"; // End wrap div
$trial->writeFooter();
unset( $_SESSION[$include] ); // unset any page specific session variables
?>