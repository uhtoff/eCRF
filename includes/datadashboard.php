<?php
$sql = "SELECT violation.nocpap, violation.violationdesc, core.trialid, studygroup, cpap.cpap FROM core LEFT JOIN link ON core.id = link.core_id LEFT JOIN cpap ON link.cpap_id = cpap.id LEFT JOIN violationlink ON link.id = violationlink.link_id LEFT JOIN violation ON violationlink.violation_id = violation.id WHERE studygroup = 1 AND cpap.cpap = 0 AND link.discontinue_id IS NULL";
$result = DB::query($sql);
$nodeviation = array();
$deviation = array();
foreach ($result->rows as $row) {
    if (is_null($row->nocpap) || !$row->nocpap) {
        $nodeviation[] = $row->trialid;
    } else {
        $deviation[] = $row->trialid;
    }
}
if (!empty($nodeviation) || !empty($deviation)) {
    echo "<p>All the following trial IDs were assigned to the study group, but did not receive CPAP</p>";
    echo "<ul>";
    if (!empty($nodeviation)) {
        echo "<li>The following Trial IDs have no appropriate Protocol Deviation form entered</li>";
        echo "<ul>";
        foreach ($nodeviation as $id) {
            echo "<li>{$id}</li>";
        }
        echo "</ul>";
    }
    if (!empty($deviation)) {
        echo "<li>The following Trial IDs have an appropriate Protocol Deviation form entered</li>";
        echo "<ul>";
        foreach ($deviation as $id) {
            echo "<li>{$id}</li>";
        }
        echo "</ul>";
    }
    echo "</ul>";
} else {
    echo "<p>No patients assigned to the study group did not receive CPAP</p>";
}

$sql = "SELECT violation.nocpap, violation.violationdesc, core.trialid, studygroup, cpap.cpap FROM core LEFT JOIN link ON core.id = link.core_id LEFT JOIN cpap ON link.cpap_id = cpap.id LEFT JOIN violationlink ON link.id = violationlink.link_id LEFT JOIN violation ON violationlink.violation_id = violation.id WHERE studygroup = 0 AND cpap.cpap = 1";
$result = DB::query($sql);
$nodeviation = array();
$deviation = array();
foreach ($result->rows as $row) {
    if (is_null($row->nocpap)) {
        $nodeviation[] = $row->trialid;
    } else {
        $deviation[] = $row->trialid;
    }
}
if (!empty($nodeviation) || !empty($deviation)) {
    echo "<p>All the following trial IDs were assigned to the control group, but did receive CPAP</p>";
    echo "<ul>";
    if (!empty($nodeviation)) {
        echo "<li>The following Trial IDs have no appropriate Protocol Deviation form entered</li>";
        echo "<ul>";
        foreach ($nodeviation as $id) {
            echo "<li>{$id}</li>";
        }
        echo "</ul>";
    }
    if (!empty($deviation)) {
        echo "<li>The following Trial IDs have an appropriate Protocol Deviation form entered</li>";
        echo "<ul>";
        foreach ($deviation as $id) {
            echo "<li>{$id}</li>";
        }
        echo "</ul>";
    }
    echo "</ul>";
} else {
    echo "<p>No patients assigned to the control group have received CPAP</p>";
}

$records = $trial->getAllRecords();
$today = new DateTime();
$completeCutOff = $today->modify('40 days ago');
$startTarget = new DateTime('2016-02-02');
$centreArr = array();
foreach ($records as $record) {
    if ($record->getRandomisationDate() < $startTarget) {
        continue;
    }
    if ($record->getRandomisationDate() < $completeCutOff) {
        if (!isset($centreArr[$record->getCentreName()])) {
            $centreArr[$record->getCentreName()]['recruited'] = 1;
            $centreArr[$record->getCentreName()]['complete'] = 0;
        } else {
            $centreArr[$record->getCentreName()]['recruited']++;
        }
        if (count($trial->checkInterimComplete($record))==0) {
            $centreArr[$record->getCentreName()]['complete']++;
        }
    }
}

echo "<table class='table table-striped table-bordered dataTable'><thead><th>Centre</th><th>Num recruited &gt; 40 days ago</th><th>Data complete</th><th>Percent complete</th></thead><tbody>";
foreach ($centreArr as $centre => $centreData ) {
    $percentComplete = round($centreData['complete']*100/$centreData['recruited'],1);
    echo "<tr><td>$centre</td><td>{$centreData['recruited']}</td><td>{$centreData['complete']}</td><td>{$percentComplete}</td></tr>";
}
echo "</tbody></table>";