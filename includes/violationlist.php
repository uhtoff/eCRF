<?php
$sql = "SELECT v.id as vid, d.option_text as centre_name, link_id, c.trialid, v.active FROM violation v 
LEFT JOIN violationlink a ON v.id = a.violation_id 
LEFT JOIN link b ON a.link_id = b.id 
LEFT JOIN core c ON b.core_id = c.id
LEFT JOIN centre d ON c.centre_id = d.id
WHERE v.active = 1";
if ( $user->isCentralAdmin() ) {
	$sql .= " GROUP BY a.link_id ORDER BY active";
	$result = DB::query($sql);
} elseif ( $user->isRegionalAdmin() ) {
	$sql .= " AND country_id = ?";
	$sql .= " GROUP BY a.link_id";
	$centre = new Centre( $user->getCentre() );
	$pA = array('i', $centre->get('country_id'));
	$result = DB::query($sql, $pA);
} else {
	$sql .= " AND centre_id = ?";
	$sql .= " GROUP BY a.link_id";
	$pA = array('i', $user->getCentre());
	$result = DB::query($sql, $pA);
}

if ( $result->getRows() ) {
	echo "<div class=\"container well\" style=\"background-color:#FFFFFF;\">";
	echo "<h3>Protocol deviations</h3>";
	if ( $user->isCentralAdmin() ) {
		echo "<h5>If you wish to remove a protocol deviation form then please select and click 'Delete' - the form will be stored for audit purposes.</h5>";
		echo "<form action=\"process.php\" method=\"POST\">";
	}
	echo '<table class="table table-striped table-bordered table-hover dataTable"><thead>';
	echo '<tr><th scope="col">Centre</th><th scope="col">' . Config::get('idName') . '</th><th scope="col">Deviation</th>';
    echo '<th scope="col">Description</th><th scope="col">Reported time</th>';
	if ( $user->isCentralAdmin() ) {
		echo '<th>Select</th>';
	}
	echo '</tr></thead>';
	echo "<tbody>\n";
	foreach($result->rows as $rowv ) {
		$e = new eCRF('violation');
		$e->addRecord($rowv->link_id);
		foreach($e->getViolations() as $v) {
			if ( $v->isActive() ) {
				echo "<tr class=\"clickable\"><td>{$rowv->centre_name}</td><td>{$rowv->trialid}</td>";
				$typearray = array(
					'no' => 'Participant in the intervention group did NOT receive CPAP',
					'low' => 'CPAP started at a dose other than 5cmH2O',
					'stop' => 'CPAP administered for less than 4 hours or with significant interruption',
					'wrong' => 'Participant in the usual care group DID receive CPAP'
				);
				$output = '<td><ul>';
				foreach ($typearray as $type => $title) {
					if ($v->{$type.'cpap'}) {
						$output .= "<li><b>{$title}</b></li>";
						$output .= "<ul>";
						foreach ($v->{$type.'cpapreason'} as $reason) {
							$sql = "SELECT option_text FROM {$type}cpapreason WHERE option_value = ?";
							$pA = array('i', $reason);
							$result = DB::query($sql, $pA);
							$output .= "<li>{$result->option_text}</li>";
						}
						$output .= "</ul>";
					}
				}
				echo $output;
				if ($v->otherdeviation) {
					echo "<li><b>Other protocol deviation</b></li>";
				}
				echo "</ul></td>";
				echo "<td>{$v->violationdesc}</td>";
				$sql = "SELECT `time` FROM violationAudit WHERE table_id = ? ORDER BY `time` ASC LIMIT 0,1";
				$pA = array('i',$rowv->vid);
				$result = DB::query($sql,$pA);
				echo "<td>{$result->time}</td>";
				if ($user->isCentralAdmin()) {
					echo "<td class='clickable'><input type='radio' name='vSelect' value='{$v->getID()}'/>";
				}
				echo "</tr>";
			}
		}
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
	echo "<h3>No protocol deviations recorded.</h3>";
}
