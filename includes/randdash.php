<?php
$sql = "SELECT core.id, core.studygroup, DATE(time) as dateOnly, planned_epidural, surgicalprocedure.option_text AS surgery, country.name AS countryName FROM coreAudit 
        LEFT JOIN core on coreAudit.table_id = core.id 
        LEFT JOIN link ON link.core_id = core.id 
        LEFT JOIN centre ON core.centre_id = centre.id 
        LEFT JOIN country ON centre.country_id = country.id
        LEFT JOIN surgicalprocedure ON core.planned_surgery = surgicalprocedure.option_value
        WHERE field = 'randdatetime' AND link.discontinue_id IS NULL";
$result = DB::query($sql);
$array = '';
$numControl = $numIntervention = $total = 0;
$minimisation = array();
$minimisation['epidural']['study'] = 0;
$minimisation['noepidural']['study'] = 0;
$minimisation['epidural']['control'] = 0;
$minimisation['noepidural']['control'] = 0;

foreach ($result->rows as $row) {
    if ($row->studygroup) {
        $numIntervention++;
        if ( isset($minimisation['surgery'][$row->surgery]['study']) ) {
            $minimisation['surgery'][$row->surgery]['study']++;
        } else {
            $minimisation['surgery'][$row->surgery]['study'] = 1;
        }
        if ( isset($minimisation['country'][$row->countryName]['study']) ) {
            $minimisation['country'][$row->countryName]['study']++;
        } else {
            $minimisation['country'][$row->countryName]['study'] = 1;
        }
        if ( $row->planned_epidural ) {
            $minimisation['epidural']['study']++;
        } else {
            $minimisation['noepidural']['study']++;
        }
    } else {
        $numControl++;
        if ( isset($minimisation['surgery'][$row->surgery]['control'] ) ) {
            $minimisation['surgery'][$row->surgery]['control']++;
        } else {
            $minimisation['surgery'][$row->surgery]['control'] = 1;
        }
        if ( isset($minimisation['country'][$row->countryName]['control'] ) ) {
            $minimisation['country'][$row->countryName]['control']++;
        } else {
            $minimisation['country'][$row->countryName]['control'] = 1;
        }
        if ( $row->planned_epidural ) {
            $minimisation['epidural']['control']++;
        } else {
            $minimisation['noepidural']['control']++;
        }
    }
}


echo "<p>Control count: {$numControl}</p><p>Intervention count: {$numIntervention}</p>";
echo "<table class='table table-bordered table-striped'><thead><th>Group</th><th>Study</th><th>Control</th></thead>";
echo "<tbody>";
$countries = array();
foreach ($minimisation['country'] as $country => $assignment) {
    echo "<tr><td>$country</td>";
    if (isset($assignment['study'])) {
        echo "<td>{$assignment['study']}</td>";
    } else {
        echo "<td>0</td>";
    }
    if (isset($assignment['control'])) {
        echo "<td>{$assignment['control']}</td>";
    } else {
        echo "<td>0</td>";
    }
}
foreach ($minimisation['surgery'] as $country => $assignment) {
    echo "<tr><td>$country</td>";
    if (isset($assignment['study'])) {
        echo "<td>{$assignment['study']}</td>";
    } else {
        echo "<td>0</td>";
    }
    if (isset($assignment['control'])) {
        echo "<td>{$assignment['control']}</td>";
    } else {
        echo "<td>0</td>";
    }
}
echo "<tr><td>Epidural</td>";
echo "<td>{$minimisation['epidural']['study']}</td>";
echo "<td>{$minimisation['epidural']['control']}</td>";
echo "<tr><td>No epidural</td>";
echo "<td>{$minimisation['noepidural']['study']}</td>";
echo "<td>{$minimisation['noepidural']['control']}</td>";
echo "</tbody></table>";