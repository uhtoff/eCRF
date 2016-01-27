<?php
$sql = "SELECT offline, timeoff FROM ecrf";
$result = DB::query( $sql );

if ( $result->offline || $result->timeoff ) {
    echo "<p class=\"lead\">The eCRF is now back online.</p>";
	$sql = "UPDATE ecrf SET offline = 0, timeoff = NULL";
	DB::query( $sql );
} else {
    echo "<p class=\"lead\">The eCRF will go offline in 2 minutes.</p>";
	$sql = "UPDATE ecrf SET timeoff = DATE_ADD( NOW(), INTERVAL 2 MINUTE )";
	DB::query( $sql );
}
?>
