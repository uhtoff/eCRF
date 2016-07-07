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