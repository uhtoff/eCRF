<?php
$sql = "SELECT d.option_text as centre_name, link_id, adverseevent_id, trialid FROM aelink a
LEFT JOIN link b ON a.link_id = b.id 
LEFT JOIN core c ON b.core_id = c.id
LEFT JOIN centre d ON c.centre_id = d.id
WHERE active = 1";
if ( $user->isCentralAdmin() ) {
	$sql .= " OR active = 0 ORDER BY active DESC";
	$result = DB::query($sql);
} elseif ( $user->isRegionalAdmin() ) {
	$sql .= " AND country_id = ?";
	$centre = new Centre( $user->getCentre() );
	$pA = array('i', $centre->get('country_id'));
	$result = DB::query($sql, $pA);
} else {
	$sql .= " AND centre_id = ?";
	$pA = array('i', $user->getCentre());
	$result = DB::query($sql, $pA);
}

if ( $result->getRows() ) {
	echo "<div class=\"container well\" style=\"background-color:#FFFFFF;\">";
	echo "<h3>Adverse events</h3>";
	echo '<table class="table table-striped table-bordered table-hover dataTable"><thead>';
	echo '<tr><th scope="col">Centre</th><th scope="col">' . Config::get('idName') . '</th><th scope="col">Adverse events</th>';
    echo '<th scope="col">CPAP outcome</th><th scope="col">Other outcome</th><th scope="col">Description</th></tr></thead>';
	echo "<tbody>\n";
	foreach( $result->rows as $rowae ) {
		$sql = "SELECT adverseevent.*, aeresponse.option_text FROM adverseevent 
		LEFT JOIN aeresponse 
		ON adverseevent.aeresponse = aeresponse.option_value 
		WHERE adverseevent.id = ?";
		$pA = array('i',$rowae->adverseevent_id);
		$row = DB::query($sql,$pA);
		$events = array();
		$outcomes = array();
		if ( $row->airleak ) {
			$events[] = "Air leak";
		}
		if ( $row->pain ) {
			$events[] = "Pain";
		}
		if ( $row->pressure ) {
			$events[] = "Pressure area";
		}
		if ( $row->claustrophobia ) {
			$events[] = "Claustrophobia";
		}
		if ( $row->dryness ) {
			$events[] = "Dryness";
		}
		if ( $row->hypercapnia ) {
			$events[] = "Hypercapnia";
		}
		if ( $row->instability ) {
			$events[] = "Instability";
		}
		if ( $row->vomiting ) {
			$events[] = "Vomiting";
		}
		if ( $row->other ) {
			$events[] = "Other";
		}
		if ( $row->death ){
			$outcomes[] = "Death";
		}
		if ( $row->complication ) {
			$outcomes[] = "Life-threatening complication";
		}
		if ( $row->prolonged ) {
			$outcomes[] = "Prolonged hospital stay";
		}
		if ( $row->disability ) {
			$outcomes[] = "Disability";
		}
		if ( $row->aspiration ) {
			$outcomes[] = "Gastric aspiration";
		}
		echo "<tr><td>{$rowae->centre_name}</td><td>{$rowae->trialid}</td><td>";
		if ( empty( $events ) ) {
			echo "None";
		} else {
			echo implode("<br/>",$events);
		}
		echo "</td><td>{$row->option_text}</td><td>";
		if ( empty($outcomes) ) {
			echo "None";
		} else {
			echo implode("<br/>",$outcomes);
		}
		echo "</td><td>{$row->description}</td></tr>";
	}
	echo "</tbody></table>";
	echo "</div>";
} else {
	echo "<h3>No adverse events recorded.</h3>";
}
