<?php
$sql = "SELECT d.option_text as centre_name, link_id, c.trialid FROM violationlink a
LEFT JOIN link b ON a.link_id = b.id 
LEFT JOIN core c ON b.core_id = c.id
LEFT JOIN centre d ON c.centre_id = d.id";
if ( $user->isCentralAdmin() ) {
	$sql .= " GROUP BY a.link_id";
	$result = DB::query($sql);
} elseif ( $user->isRegionalAdmin() ) {
	$sql .= " WHERE country_id = ?";
	$sql .= " GROUP BY a.link_id";
	$centre = new Centre( $user->getCentre() );
	$pA = array('i', $centre->get('country_id'));
	$result = DB::query($sql, $pA);
} else {
	$sql .= " WHERE centre_id = ?";
	$sql .= " GROUP BY a.link_id";
	$pA = array('i', $user->getCentre());
	$result = DB::query($sql, $pA);
}

if ( $result->getRows() ) {
	echo "<div class=\"container well\" style=\"background-color:#FFFFFF;\">";
	echo "<h3>Protocol deviations</h3>";
	echo '<table class="table table-striped table-bordered table-hover dataTable"><thead>';
	echo '<tr><th scope="col">Centre</th><th scope="col">' . Config::get('idName') . '</th><th scope="col">Violation</th>';
    echo '<th scope="col">Description</th></tr></thead>';
	echo "<tbody>\n";
	foreach($result->rows as $rowv ) {
		$e = new eCRF('violation');
		$e->addRecord($rowv->link_id);
		foreach($e->getViolations() as $v) {
			echo "<tr><td>{$rowv->centre_name}</td><td>{$rowv->trialid}</td>";
			$typearray = array('no' => 'No CPAP',
					'low' => 'Wrong CPAP level',
					'stop' => 'Stopped CPAP',
					'wrong' => 'CPAP on usual care patient');
			$output = '<td><ul>';
			foreach( $typearray as $type => $title ) {
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
			echo "</ul></td>";
			echo "<td>{$v->violationdesc}</td></tr>";
		}
	}
	echo "</tbody></table>";
	echo "</div>";
} else {
	echo "<h3>No protocol deviations recorded.</h3>";
}
