<?php
$sql = "SELECT core.id, core.studygroup, DATE(time) as dateOnly FROM coreAudit LEFT JOIN core on coreAudit.table_id = core.id WHERE field = 'randdatetime'";
$result = DB::query($sql);
$array = '';
$numControl = $numIntervention = $total = 0;
foreach ($result->rows as $row) {
    if ($row->studygroup) {
        $numIntervention++;
    } else {
        $numControl++;
    }
}

$sql = "SELECT COUNT(core.id) as numRecruited, core.studygroup, DATE(time) as dateOnly FROM coreAudit LEFT JOIN core on coreAudit.table_id = core.id WHERE field = 'randdatetime' GROUP BY dateOnly";
$result = DB::query($sql);
$startTarget = new DateTime('2016-02-02');
$stepUp = new DateTime('2016-08-01');
$endTarget = new DateTime('2018-12-01');
$dateDiff = $startTarget->diff($endTarget,true);
$months = $dateDiff->y * 12 + $dateDiff->m;
$target = 4800;
$shares = 6 + ( $months - 6 ) * 2;
$sharedRecruit = floor($target/$shares);
echo <<<_END
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          [{type:'date', label:'Day'}, 'Recruited', 'Recruited after Feb 2016', 'Target' ],
_END;
$postFeb = 0;
$recruitTarget = 0;
foreach( $result->rows as $row ) {
    $recruitDate = DateTime::createFromFormat('Y-m-d',$row->dateOnly);
    if (!isset($date)) {
        $date = $recruitDate;
        $firstMonth = $date->format('m') - 1;
        $firstYear = $date->format('Y');
    }
    while ($recruitDate!=$date) {
        if ( $date->format('d') == 1 ) {
            if ( $date > $startTarget && $date <= $stepUp ) {
                $recruitTarget += $sharedRecruit;
            } else if ($date>$stepUp) {
                $recruitTarget += ( $sharedRecruit * 2 );
            }
        }
        $array .= "[";
        $array .= "new Date({$date->format('Y')}, ";
        $array .= $date->format('m') - 1;
        $array .= ", {$date->format('d')})";
        $array .= ",{$total},{$postFeb},{$recruitTarget}],";
        $date->modify('+1 day');
    }
    $total+=$row->numRecruited;
    if ( $date > $startTarget ) {
        $postFeb+=$row->numRecruited;
    }
    if ( $date->format('d') == 1 ) {
        if ( $date > $startTarget && $date <= $stepUp ) {
            $recruitTarget += $sharedRecruit;
        } else if ($date>$stepUp) {
            $recruitTarget += ( $sharedRecruit * 2 );
        }
    }
    $array .= "[";
    $array .= "new Date({$date->format('Y')}, ";
    $array .= $date->format('m') - 1;
    $array .= ", {$date->format('d')})";
    $array .= ",{$total},{$postFeb},{$recruitTarget}],";
    $date->modify('+1 day');
}
$lastMonth = $date->format('m') - 1;
$lastYear = $date->format('Y');
$array = rtrim($array, ',');
echo $array;
echo <<<_END
        ]);

        var options = {
          title: 'Recruitment',
          hAxis: {
            title: 'Date',
            ticks: [
_END;
while( $firstMonth != $lastMonth || $firstYear != $lastYear ) {
    echo "new Date({$firstYear},{$firstMonth}),";
    $firstMonth++;
    if ( $firstMonth == 12 ) {
        $firstMonth = 0;
        $firstYear++;
    }
}
echo "new Date({$lastYear},{$lastMonth})";
echo <<<_END
          ]},
          vAxis: {
            gridlines: { count:4 }
           }
        };

        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script>
<div id="chart_div" style="width: 900px; height: 500px;"></div>
_END;
echo "<div><p>Total recruited: {$total}</p>";
echo "<p>Total recruited post Feb 2016: {$postFeb}</p>";
if ( $user->getPrivilege() == 1 ) {
    echo "<p>Control count: {$numControl}</p><p>Intervention count: {$numIntervention}</p>";
}
echo "</div>";
//$trial->simulateTrial();
/*$sql = "SELECT COUNT(signed) as crfCount, SUM(signed) as numSigned FROM link";
$crfs = DB::query($sql);
echo "<h4>Current CRF totals</h4>";
echo "<p>Currently there are {$crfs->crfCount} CRFs entered of which {$crfs->numSigned} are signed and " . ( $crfs->crfCount - $crfs->numSigned ) . " are unsigned.</p>";
echo "<p>This total does not include 1042 South African CRFs.</p>";
$sql = "SELECT COUNT(table_id) as crfCount, DATE(new_value) AS dateOnly FROM linkAudit WHERE field = 'created' GROUP BY dateOnly";
$result = DB::query( $sql );
$sql = "SELECT COUNT(table_id) as crfCount, DATE(time) AS dateOnly FROM linkAudit WHERE type = 'DELETE' GROUP BY dateOnly";
$deleted = DB::query($sql);
$deletedArr = $deleted->getArray('crfCount','dateOnly');
$sql = "SELECT COUNT(table_id) as crfCount, DATE(time) AS dateOnly FROM linkAudit WHERE field = 'signed' AND new_value = 1 GROUP BY dateOnly";
$signed = DB::query( $sql );
$signedArr = $signed->getArray('crfCount', 'dateOnly');
$sql = "SELECT COUNT(table_id) as crfCount, DATE(time) AS dateOnly FROM linkAudit WHERE field = 'signed' AND new_value = 0 GROUP BY dateOnly";
$unsigned = DB::query( $sql );
$unsignedArr = $unsigned->getArray('crfCount', 'dateOnly');
echo <<<_END
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Day', 'Created', 'Signed', 'Total', 'Signed Total'],
_END;
$array = '';
$signedTotal = $total = 0;
foreach( $result->rows as $row ) {
    if ( isset( $deletedArr[$row->dateOnly]) ) {
        $dayTotal = $row->crfCount - $deletedArr[$row->dateOnly];
    } else {
        $dayTotal = $row->crfCount;
    }
    $total += $dayTotal;
	if ( $dayTotal < 0 ) $dayTotal = 0;
    $signedNum = 0;
    if ( isset( $signedArr[$row->dateOnly]) ) {
        $signedNum = $signedArr[$row->dateOnly];
    }
    if ( isset( $unsignedArr[$row->dateOnly] ) ) {
        $signedNum -= $unsignedArr[$row->dateOnly];
    }
    $signedTotal += $signedNum;
    if ( $signedNum < 0 ) $signedNum = 0;
    $array .= "['{$row->dateOnly}',{$dayTotal}, $signedNum, $total, $signedTotal],";
}
$array = rtrim($array, ',');
echo $array;
echo <<<_END
        ]);

        var options = {
          title: 'CRFs'
        };

        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script>
<div id="chart_div" style="width: 900px; height: 500px;"></div>
_END;
echo <<<_END
 <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Country', 'No users', 'No CRFs entered', 'CRFs being entered', 'Completed and locked'],
_END;

$sql = "SELECT count(centre.id) AS numLocked, country_id FROM centre WHERE datalock = 1 AND infolock = 1 GROUP BY country_id ORDER BY country_id";
$result = DB::query( $sql );
$lockArr = $result->getArray('numLocked', 'country_id');
$sql = "SELECT count(DISTINCT centre_id) AS numCRFs, country_id FROM core LEFT JOIN centre ON core.centre_id = centre.id LEFT JOIN country ON centre.country_id = country.id GROUP BY country.id ORDER BY country_id";
$result = DB::query( $sql );
$crfArr = $result->getArray('numCRFs', 'country_id');
$sql = "SELECT COUNT( DISTINCT centre.id ) as numUsers, country_id FROM user LEFT JOIN centre ON user.centre_id = centre.id LEFT JOIN country ON centre.country_id = country.id GROUP BY country.id";
$result = DB::query( $sql );
$userArr = $result->getArray('numUsers', 'country_id');
$sql = "SELECT count(centre.id) AS numCentres, country.name AS countryName, country_id FROM country LEFT JOIN centre ON country.id = country_id GROUP BY country.id";
$result = DB::query( $sql );

$data = '';
foreach( $result->rows as $row ) {
    if( isset( $lockArr[$row->country_id] ) ) {
        $locked = $lockArr[$row->country_id];
    } else {
        $locked = 0;
    }
    if( isset( $crfArr[$row->country_id] ) ) {
        $someCRF = $crfArr[$row->country_id] - $locked;
    } else {
        $someCRF = 0;
    }
    if ( isset( $userArr[$row->country_id] ) ) {
        $noCRF = $userArr[$row->country_id] - ( $locked + $someCRF );
    } else {
        $noCRF = 0;
    }
    $noUsers = $row->numCentres - ( $locked + $someCRF + $noCRF );
    $data .= "['{$row->countryName}',{$noUsers},{$noCRF},{$someCRF},{$locked}],";
}
$data = rtrim( $data, ',');
echo $data;
echo <<<_END
        ]);

        var options = {
          title: 'Centre status',
          isStacked: true
        };

        var chart = new google.visualization.BarChart(document.getElementById('chart2_div'));
        
        function selectHandler() {
          var selectedItem = chart.getSelection()[0];
          if (selectedItem) {
            var country = data.getValue(selectedItem.row, 0);
            var status = selectedItem.column;
            window.location.href = 'index.php?page=sitereg&country=' + country + '&status=' + status;
          }
        }

        // Listen for the 'select' event, and call my function selectHandler() when
        // the user selects something on the chart.
        google.visualization.events.addListener(chart, 'select', selectHandler);
        
   
        chart.draw(data, options);

        
      }
    </script>
<div id="chart2_div" style="width: 900px; height: 1200px;"></div>
_END;
echo <<<_END
 <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Country', 'Unsigned CRFs', 'Signed CRFs' ],
_END;
$sql = "SELECT COUNT(link.id) as countryTotal, country.id as country_id, country.name as country_name FROM link LEFT JOIN core ON link.core_id = core.id LEFT JOIN centre ON core.centre_id = centre.id RIGHT JOIN country ON country.id = centre.country_id WHERE link.signed = 1 GROUP BY country.id ORDER BY country.name";
$result = DB::query( $sql );
$signedArr = $result->getArray('countryTotal', 'country_id');
$sql = "SELECT COUNT(link.id) as countryTotal, country.id as country_id, country.name as country_name FROM link LEFT JOIN core ON link.core_id = core.id LEFT JOIN centre ON core.centre_id = centre.id RIGHT JOIN country ON country.id = centre.country_id GROUP BY country.id ORDER BY country.name";
$result = DB::query( $sql );
$data = '';
$table = '';
foreach( $result->rows as $row ) {
    $signed = 0;
    if ( isset( $signedArr[$row->country_id]) ) {
        $signed = $signedArr[$row->country_id];
    }
    $unsigned = $row->countryTotal - $signed;
    $data .= "['{$row->country_name}',$unsigned, {$signed}],";
    if ( $unsigned > 0 ) {
        $table .= "<h3>{$row->country_name}</h3>";
        $table .= "<p>Signed = {$signed}</p>";
        $table .= "<p>Unsigned = " . $unsigned . "</p>";
        $sql = "SELECT COUNT(link.id) as countryTotal, centre.name as centreName, country.id as country_id, country.name as country_name FROM link LEFT JOIN core ON link.core_id = core.id LEFT JOIN centre ON core.centre_id = centre.id RIGHT JOIN country ON country.id = centre.country_id WHERE link.signed = 0 AND country.id = ? GROUP BY centre.id ORDER BY centre.name";
        $pA = array('i',$row->country_id);
        $unsignCentres = DB::query($sql, $pA);
        foreach( $unsignCentres->rows as $centreRow ) {
            $table .= "<p>{$centreRow->centreName} - {$centreRow->countryTotal}</p>";
        }
    }
}
$data = rtrim( $data, ',');
echo $data;
echo <<<_END
        ]);

        var options = {
          title: 'Country Totals',
          isStacked: true
        };

        var chart = new google.visualization.BarChart(document.getElementById('chart4_div'));
        
        function selectHandler() {
          var selectedItem = chart.getSelection()[0];
          if (selectedItem) {
            var country = data.getValue(selectedItem.row, 0);
            var status = selectedItem.column;
            window.location.href = 'index.php?page=sitereg&country=' + country + '&status=' + status;
          }
        }

        // Listen for the 'select' event, and call my function selectHandler() when
        // the user selects something on the chart.
        google.visualization.events.addListener(chart, 'select', selectHandler);
        
   
        chart.draw(data, options);

        
      }
    </script>
<div id="chart4_div" style="width: 900px; height: 1200px;"></div>
<div>{$table}</div>
_END;
$sql = "SELECT count(distinct(table_id)) as numUsers, date(`time`) as dateOnly FROM userAudit WHERE new_value = '1' AND field = 'loggedin' GROUP BY date(`time`) ORDER BY date(`time`)";
$userQ = DB::query($sql);
echo <<<_END
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Day', 'Users'],
_END;
$array = '';
foreach( $userQ->rows as $row ) {
    $array .= "['{$row->dateOnly}',{$row->numUsers}],";
}
$array = rtrim($array, ',');
echo $array;
echo <<<_END
        ]);

        var options = {
          title: 'Number of active users'
        };

        var chart = new google.visualization.LineChart(document.getElementById('chart3_div'));
        chart.draw(data, options);
      }
    </script>
<div id="chart3_div" style="width: 900px; height: 500px;"></div>
_END;*/
?>
