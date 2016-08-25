<?php
$sql = "SELECT d.option_text as centre_name, a.link_id, adverseevent_id, trialid, CONCAT(user.forename,' ',user.surname) as full_name, privilege.option_text as privilege_name FROM aelink a
LEFT JOIN link b ON a.link_id = b.id 
LEFT JOIN core c ON b.core_id = c.id
LEFT JOIN centre d ON c.centre_id = d.id
LEFT JOIN adverseevent ON a.adverseevent_id = adverseevent.id
LEFT JOIN adverseeventAudit ON adverseevent.id = adverseeventAudit.table_id
LEFT JOIN user on adverseeventAudit.user_id = user.id
LEFT JOIN privilege ON user.privilege_id = privilege.option_value
WHERE adverseevent.active = 1
AND adverseeventAudit.field = 'ae'";
if ( $user->isCentralAdmin() ) {
	$sql .= " ORDER BY adverseevent.active DESC";
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
	if ( $user->isCentralAdmin() ) {
		echo "<h5>If you wish to remove an adverse event form then please select and click 'Delete' - the form will be stored for audit purposes.</h5>";
		echo "<form action=\"process.php\" method=\"POST\">";
	}
	echo '<table class="table table-striped table-bordered table-hover dataTable"><thead>';
	echo '<tr><th scope="col">Centre</th><th scope="col">' . Config::get('idName') . '</th><th scope="col">Type of adverse event</th>';
    echo '<th scope="col">CPAP outcome</th><th scope="col">Serious adverse event criteria</th><th scope="col">Description</th><th scope="col">Reported time</th><th scope="col">Reported by</th>';
	if ( $user->isCentralAdmin() ) {
		echo '<th>Select</th>';
	}
	echo '</tr></thead>';
	echo "<tbody>\n";
	foreach( $result->rows as $rowae ) {
		$sql = "SELECT adverseevent.*, aeresponse.option_text, aedate, aetime FROM adverseevent 
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
		echo "</td><td>{$row->description}</td>";
		echo "<td>{$row->aedate} {$row->aetime}</td>";
        echo "<td>{$rowae->full_name} ({$rowae->privilege_name})</td>";
		if ($user->isCentralAdmin()) {
			echo "<td class='clickable'><input type='radio' name='aeSelect' value='{$row->id}'/>";
		}
		echo "</tr>";
	}
	echo "</tbody></table>";
	if ($user->isCentralAdmin()) {
		echo "<input type=\"hidden\" name='page' value='{$page}'/>";
		$_SESSION['csrfToken'] = $token = base64_encode(openssl_random_pseudo_bytes(32));
		echo "<input type=\"hidden\" name='csrfToken' value='{$token}'/>";
		echo "<div class=\"form-actions\">
		<button type=\"submit\" class=\"btn btn-danger\">Delete</button>
		</div>";
		echo "</form>";
	}
	echo "</div>";
} else {
	echo "<h3>No adverse events recorded.</h3>";
}
