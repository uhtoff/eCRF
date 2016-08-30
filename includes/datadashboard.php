<?php
Timer::start();
echo "<div class='container well'>";
$sql = "SELECT SUM(violation.nocpap) AS nocpap, violation.violationdesc, core.trialid, studygroup, cpap.cpap FROM core 
LEFT JOIN link ON core.id = link.core_id 
LEFT JOIN cpap ON link.cpap_id = cpap.id 
LEFT JOIN violationlink ON link.id = violationlink.link_id 
LEFT JOIN violation ON violationlink.violation_id = violation.id 
WHERE studygroup = 1 
AND cpap.cpap = 0 
AND link.discontinue_id IS NULL
GROUP BY core.trialid";
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

$sql = "SELECT SUM(violation.nocpap) as nocpap, violation.violationdesc, core.trialid, studygroup, cpap.cpap FROM core 
LEFT JOIN link ON core.id = link.core_id 
LEFT JOIN cpap ON link.cpap_id = cpap.id 
LEFT JOIN violationlink ON link.id = violationlink.link_id 
LEFT JOIN violation ON violationlink.violation_id = violation.id 
WHERE studygroup = 0 
AND cpap.cpap = 1 
AND link.discontinue_id IS NULL
GROUP BY core.trialid";
$result = DB::query($sql);
$nodeviation = array();
$deviation = array();
$numNoCpap = $result->getRows();
foreach ($result->rows as $row) {
    if (is_null($row->nocpap) || !$row->nocpap) {
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

$sql = "SELECT SUM(violation.stopcpap) as stopcpap, violation.violationdesc, core.trialid, studygroup, cpap.cpap, cpap.cpapduration FROM core 
LEFT JOIN link ON core.id = link.core_id 
LEFT JOIN cpap ON link.cpap_id = cpap.id 
LEFT JOIN violationlink ON link.id = violationlink.link_id 
LEFT JOIN violation ON violationlink.violation_id = violation.id 
WHERE studygroup = 1 
AND cpap.cpap = 1 
AND link.discontinue_id IS NULL
AND cpapduration < 240
GROUP BY core.trialid";
$result = DB::query($sql);
$nodeviation = array();
$deviation = array();
$durations = array();
foreach ($result->rows as $row) {
    if (is_null($row->stopcpap) || !$row->stopcpap) {
        $nodeviation[] = $row->trialid;
    } else {
        $deviation[] = $row->trialid;
    }
    $durations[] = $row->cpapduration;
}
if (!empty($nodeviation) || !empty($deviation)) {
    echo "<p>All the following trial IDs were assigned to the trial group and received less than 240 minutes of CPAP</p>";
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
    echo "<p>All patients assigned to the study group and receiving CPAP received at least 240 minutes of CPAP</p>";
}
$patientCount = count($durations);
$timeSum = array_sum($durations);
$avgTime = round($timeSum/$patientCount,1);

$sql = "SELECT COUNT(cpap.cpap) AS num_cpap FROM cpap
LEFT JOIN link ON cpap.id = link.cpap_id
LEFT JOIN core ON core.id = link.core_id
WHERE studygroup = 1
AND cpap.cpap = 1
AND link.discontinue_id IS NULL";
$result = DB::query($sql);

$totalCpap = $result->num_cpap;

$sql = "SELECT COUNT(cpap.cpap) AS num_assigned FROM cpap
LEFT JOIN link ON cpap.id = link.cpap_id
LEFT JOIN core ON core.id = link.core_id
WHERE studygroup = 1
AND cpap.cpap IS NOT NULL
AND link.discontinue_id IS NULL";
$result = DB::query($sql);

$numAssigned = $result->num_assigned;

echo "<p>For patients who have data entered:</p>";
echo "<p>{$numAssigned} were assigned to CPAP</p>";
echo "<p>{$result->num_cpap} received CPAP</p>";
echo "<p>Of those {$patientCount} received less than 240 minutes with an average time of {$avgTime} minutes</p>";

echo "<table class='table table-striped table-bordered'>";
echo "<thead><th>0 - 60 minutes</th><th>61 - 120 minutes</th><th>121 - 180 minutes</th><th>181 - 240 minutes</th></thead>";
echo "<tbody><tr><td>";
echo count(array_filter($durations, function($element) {
    return ( $element >= 0 && $element <= 60 );
}));
echo "</td><td>";
echo count(array_filter($durations, function($element) {
    return ( $element >= 61 && $element <= 120 );
}));
echo "</td><td>";
echo count(array_filter($durations, function($element) {
    return ( $element >= 121 && $element <= 180 );
}));
echo "</td><td>";
echo count(array_filter($durations, function($element) {
    return ( $element >= 181 && $element <= 240 );
}));
echo "</td></tr></tbody>";
echo "<table>";
/** @var eCRFUser $user */
if ($user->getPrivilege() == 1) {
    echo Timer::show();
}
$records = $trial->getAllRecords();
if ($user->getPrivilege() == 1) {
    echo Timer::show();
}
$today = new DateTime();
$completeCutOff = $today->modify('40 days ago');
$startTarget = new DateTime('2016-02-02');
$centreArr = array();
$incompleteArr = array();

foreach ($records as $record) {
    $randDate = new DateTime($record->getRandomisationDate());
    if ($randDate < $startTarget) {
        continue;
    }
    if ($randDate < $completeCutOff) {
        if (!isset($centreArr[$record->getCentreName()])) {
            $centreArr[$record->getCentreName()]['recruited'] = 1;
            $centreArr[$record->getCentreName()]['complete'] = 0;
        } else {
            $centreArr[$record->getCentreName()]['recruited']++;
        }
        $incompletePages = $trial->checkInterimComplete($record);
        if (count($incompletePages)==0) {
            $centreArr[$record->getCentreName()]['complete']++;
        } else {
            $incompleteArr[$record->getField('core','trialid')]['centrename'] = $record->getCentreName();
            $incompleteArr[$record->getField('core','trialid')]['pages'] = $incompletePages;
        }
    }
}
if ($user->getPrivilege() == 1) {
    echo Timer::show();
}
echo "<table class='table table-striped table-bordered dataTable'><thead><th>Centre</th><th>Num recruited &gt; 40 days ago</th><th>Data complete</th><th>Percent complete</th></thead><tbody>";
foreach ($centreArr as $centre => $centreData ) {
    $percentComplete = round($centreData['complete']*100/$centreData['recruited'],1);
    echo "<tr><td>$centre</td><td>{$centreData['recruited']}</td><td>{$centreData['complete']}</td><td>{$percentComplete}</td></tr>";
}
echo "</tbody></table>";
echo "<p>Incomplete CRFs include:</p>";
echo "<table class='table table-striped table-bordered dataTable'><thead><th>Centre</th><th>Trial ID</th><th>Imcomplete pages</th></thead><tbody>";
foreach ($incompleteArr as $incomplete => $record) {
    echo "<tr><td>{$incomplete}</td>";
    echo "<td>{$record['centrename']}</td>";
    echo "<td>";
    echo "<ul>";
    foreach ($record['pages'] as $page) {
        echo "<li>$page</li>";
    }
    echo "</ul>";
    echo "</td></tr>";
}
echo "</tbody></table>";
if ($user->getPrivilege() == 1) {
    echo Timer::show();
}
echo "</div>";