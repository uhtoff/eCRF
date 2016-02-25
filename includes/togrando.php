<?php
$sql = "SELECT active FROM pages WHERE name = 'addpt'";
$result = DB::query( $sql );

if ( $result->active ) {
    echo "<p class=\"lead\">Randomisation has now been paused.</p>";
    $sql = "UPDATE pages SET active = 0 WHERE name='addpt'";
    DB::query( $sql );
} else {
    echo "<p class=\"lead\">Randomisation has now been recommenced.</p>";
    $sql = "UPDATE pages SET active = 1 WHERE name='addpt'";
    DB::query( $sql );
}
?>