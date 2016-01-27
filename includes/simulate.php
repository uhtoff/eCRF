<?php
if ( isset( $argv[3] ) ) {
	$fileOpen = $argv[3];
} else {
	$fileOpen = 'w';
}
$fh = fopen('simulation.csv', $fileOpen);
if ( $fileOpen === 'w' ) {
	fputcsv($fh,array('Sim no','Total patients','Num study','Num control','Num study France', 'Num control France', 'Num study Germany', 'Num control Germany','Num study Italy', 'Num control Italy','Num study UK','Num control UK', 'Num study Spain', 'Num study Spain', 'Num study Norway', 'Num control Norway',  'Num study lower GI', 'Num control lower GI', 'Num study HPB', 'Num control HPB', 'Num study Upper GI', 'Num control Upper GI', 'Num study obesity', 'Num control obesity', 'Num study vascular', 'Num control Vascular', 'Num study other', 'Num control other', 'Num study oesophagus', 'Num control oesophagus', 'Num study epidural', 'Num control epidural', 'Num study no epidural', 'Num control no epidural', 'Greatest centre disparity study', 'Greatest centre disparity control', 'Max recruits','Min recruits'));
}
for( $i = 1; $i <= $argv[2]; $i++ ) {
	if ( isset( $argv[4] ) ) {
		$simno = $i + $argv[4];
	} else {
		$simno = $i;
	}
	$trial->clearAllData();
	$trial->simulateTrial($simno);
	$output = array();
	$output[] = $simno;
	$sql = "SELECT count(id) as totalCase, sum(studygroup) as numStudy FROM core";
	$result = DB::query($sql);
	$output[] = $result->totalCase;
	$output[] = $result->numStudy;
	$output[] = $result->totalCase - $result->numStudy;
	$sql = "SELECT count(core.id) as totalCase, sum(studygroup) as numStudy FROM core LEFT JOIN centre ON centre.id = core.centre_id GROUP BY centre.country_id ORDER BY country_id";
	$result = DB::query($sql);
	foreach( $result->rows as $row ) {
		$output[] = $row->numStudy;
		$output[] = $row->totalCase - $row->numStudy;
	}
	$sql = "SELECT count(id) as totalCase, sum(studygroup) as numStudy FROM core GROUP BY planned_surgery ORDER BY planned_surgery";
	$result = DB::query($sql);
	foreach( $result->rows as $row ) {
		$output[] = $row->numStudy;
		$output[] = $row->totalCase - $row->numStudy;
	}
	$sql = "SELECT count(id) as totalCase, sum(studygroup) as numStudy FROM core GROUP BY planned_epidural ORDER BY planned_epidural";
	$result = DB::query($sql);
	foreach( $result->rows as $row ) {
		$output[] = $row->numStudy;
		$output[] = $row->totalCase - $row->numStudy;
	}
	$sql = "SELECT count(id) as totalCase, sum(studygroup) as numStudy FROM core GROUP BY centre_id";
	$result = DB::query($sql);
	$disparity = 0;
	$maxCentre = 0;
	$minCentre = 1000;
	foreach( $result->rows as $row ) {
		if ( $row->totalCase > $maxCentre ) {
			$maxCentre = $row->totalCase;
		}
		if ( $row->totalCase < $minCentre ) {
			$minCentre = $row->totalCase;
		}
		$numStudy = $row->numStudy;
		$numControl = $row->totalCase - $row->numStudy;
		if ( !min($numStudy,$numControl) ) continue;
		$centreDisp = max($numStudy,$numControl)/min($numStudy,$numControl);
		if ( $centreDisp > $disparity ) {
			$disparity = $centreDisp;
			$saveCentre['study'] = $numStudy;
			$saveCentre['control'] = $numControl;
		}
	}
	$output[] = $saveCentre['study'];
	$output[] = $saveCentre['control'];
	$output[] = $maxCentre;
	$output[] = $minCentre;
	fputcsv($fh, $output);
}
fclose($fh);
echo "Completed simulation";
?>