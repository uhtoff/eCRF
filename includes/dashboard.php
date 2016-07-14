<?php
$sql = "SELECT COUNT(core.id) as numRecruited, core.studygroup, DATE(time) as dateOnly FROM coreAudit 
        LEFT JOIN core on coreAudit.table_id = core.id 
        LEFT JOIN link ON link.core_id = core.id 
        WHERE field = 'randdatetime' AND `time` >= '2016-02-02' AND link.discontinue_id IS NULL GROUP BY dateOnly";
$result = DB::query($sql);
$startTarget = new DateTime('2016-02-02');
$stepUp = new DateTime('2016-08-01');
$endTarget = new DateTime('2018-12-01');
$dateDiff = $startTarget->diff($endTarget,true);
$months = $dateDiff->y * 12 + $dateDiff->m;
$target = 4800;
$shares = 6 + ( $months - 6 ) * 2;
$sharedRecruit = floor($target/$shares);
$targetMonth = 2;
$endOfMonthTarget = 0;
$monthTarget = 0;
echo <<<_END
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          [{type:'date', label:'Day'}, 'Recruited', 'Target' ],
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
    if ( $date > $startTarget && $targetMonth == 2 ) {
        $endOfMonthTarget = 10;
        $monthTarget = 10;
    }
    while ($recruitDate!=$date) {
        if ( $date->format('d') == 1 ) {
            if ( $date > $startTarget ) {
                if ( $targetMonth <= 8 ) {
                    $monthTarget = $targetMonth * 10;
                    $endOfMonthTarget += $monthTarget;
                    $targetMonth++;
                } else {
                    $monthTarget = 90;
                    $endOfMonthTarget += $monthTarget;
                }
            }
        }
        $d = $date->format('d');
        $m = $date->format('m');
        $y = $date->format('Y');
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN,$m, $y);
        $remainingDays = $daysInMonth-$d;
        $recruitTarget = $endOfMonthTarget - floor($monthTarget*($remainingDays/$daysInMonth));
        $array .= "[";
        $array .= "new Date({$date->format('Y')}, ";
        $array .= $date->format('m') - 1;
        $array .= ", {$date->format('d')})";
        $array .= ",{$total},{$recruitTarget}],";
        $date->modify('+1 day');
    }
    $total+=$row->numRecruited;
    if ( $date > $startTarget ) {
        $postFeb+=$row->numRecruited;
    }
    if ( $date->format('d') == 1 ) {
        if ( $date > $startTarget ) {
            if ( $targetMonth <= 8 ) {
                $monthTarget = $targetMonth * 10;
                $endOfMonthTarget += $monthTarget;
                $targetMonth++;
            } else {
                $monthTarget = 90;
                $endOfMonthTarget += $monthTarget;
            }
        }
    }
    $d = $date->format('d');
    $m = $date->format('m');
    $y = $date->format('Y');
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN,$m, $y);
    $remainingDays = $daysInMonth-$d;
    $recruitTarget = $endOfMonthTarget - floor($monthTarget*($remainingDays/$daysInMonth));
    $array .= "[";
    $array .= "new Date({$date->format('Y')}, ";
    $array .= $date->format('m') - 1;
    $array .= ", {$date->format('d')})";
    $array .= ",{$total},{$recruitTarget}],";
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
echo "<p>Target recruitment 4800 by December 2018</p>";

$records = $trial->getAllRecords();
$centreArr = array();
$recentRecruitDate = new DateTime();
$recentRecruitDate->modify('30 days ago');
foreach ($records as $record) {
    $recentRecruit = false;
    $randDate = new DateTime($record->getRandomisationDate());
    if ($randDate < $startTarget) {
        continue;
    } elseif ($randDate >= $recentRecruitDate) {
        $recentRecruit = true;
    }
    if (!isset($centreArr[$record->getCentreName()])) {
        $centreArr[$record->getCentreName()]['recruited'] = 1;
        $centreArr[$record->getCentreName()]['recent'] = 0;
    } else {
        $centreArr[$record->getCentreName()]['recruited']++;
    }
    if ($recentRecruit) {
        $centreArr[$record->getCentreName()]['recent']++;
    }
}

echo "<table class='table table-striped table-bordered dataTable'><thead><th>Centre</th><th>Num recruited</th><th>Last 30 days</th></thead><tbody>";
foreach ($centreArr as $centre => $centreData ) {
    echo "<tr><td>$centre</td><td>{$centreData['recruited']}</td><td>{$centreData['recent']}</td>";
}
echo "</tbody></table>";
?>
