<?php
$sql = "SELECT CONCAT( forename, ' ', surname ) AS name, id FROM user WHERE centre_id = ?";
$pA = array( 's', $user->getCentre() );
$result = DB::query( $sql, $pA );
$runtotal = 0;
echo "<table class=\"table table-striped\"><thead><caption><h3>Data entry totals for your site's users</h3></caption>";
echo "<tr><th>User</th><th>Total entered</th><th>Total signed</th></tr></thead><tbody>";
foreach( $result->rows as $row ) {
	$sql = "SELECT count(id) AS total, signed FROM link WHERE firstuser=? GROUP BY signed";
	$pA = array( 's', $row->id );
	$total = $signed = 0;
	$result2 = DB::query( $sql, $pA );
    foreach( $result2->rows as $row2 ) {
        $total += $row2->total;
        $runtotal += $row2->total;
        if ( $row2->signed ) $signed += $row2->total;
    }
	echo "<tr><td>{$row->name}</td><td>{$total}</td><td>{$signed}</td></tr>";
}
echo "</tbody></table>";
echo "<h3>Total entered is {$runtotal}</h3>";

if ( !$user->isLocal() ) {
	$sql = "SELECT centre.name AS centrename, COUNT( centre_id ) as sitecount 
		FROM `link`
		LEFT JOIN core ON link.core_id = core.id
		LEFT JOIN centre ON core.centre_id = centre.id 
		WHERE centre_id IS NOT NULL 
		GROUP BY centre_id";
	$result = DB::query( $sql );
	echo "<table class=\"table table-striped\"><thead><caption><h3>Totals across sites</h3></caption>";
	echo "<tr><th>Site</th><th>Total entered</th></tr></thead><tbody>";
	foreach( $result->rows as $row ) {
		echo "<tr><td>{$row->centrename}</td><td>{$row->sitecount}</td></tr>";
	}
	echo "</tbody></table>";
}
?>