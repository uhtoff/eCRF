<?php
$sql = "SELECT COUNT(table_id) as crfCount, DATE(new_value) AS dateOnly FROM linkAudit WHERE field = 'created' GROUP BY dateOnly";
$result = DB::query( $sql );
$sql = "SELECT COUNT(table_id) as crfCount, DATE(time) AS dateOnly FROM linkAudit WHERE field = 'signed' GROUP BY dateOnly";
$signed = DB::query( $sql );
$signedArr = $signed->getArray('crfCount', 'dateOnly');
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
    $total += $row->crfCount;
    $signedNum = 0;
    if ( isset( $signedArr[$row->dateOnly]) ) {
        $signedNum = $signedArr[$row->dateOnly];
    }
    $signedTotal += $signedNum;
    $array .= "['{$row->dateOnly}',{$row->crfCount}, $signedNum, $total, $signedTotal],";
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
$sql = "SELECT count(link.id) as centreTotal, centre_id FROM link LEFT JOIN core ON link.core_id = core.id GROUP BY core.centre_id";
$result = DB::query($sql);
$centreArr = $result->getArray('centreTotal', 'centre_id');
$sql = "SELECT eligible, name, id FROM centre WHERE id != 1 ORDER BY name";
$result = DB::query($sql);
echo "<table class=\"table table-striped\">";
echo "<caption><h3>Eligible patients by centre</h3></caption>";
echo "<thead>";
echo "<tr><th>Centre</th><th>Number of eligible patients</th><th>Number of CRFs entered</th></tr>";
echo "</thead><tbody>";
$total = 0;
foreach ( $result->rows as $row ) {
    if ( !$row->eligible ) {
        echo "<tr class=\"error\">";
    } elseif ( isset( $centreArr[$row->id] ) ) {
        if ( $centreArr[$row->id] > $row->eligible ) {
            echo "<tr class=\"info\">";
        } elseif ( abs( $centreArr[$row->id] - $row->eligible ) < 5 ) {
            echo "<tr class=\"success\">";
        } else {
            echo "<tr class=\"warning\">";
        }
    } else {
        echo "<tr class=\"warning\">";
    }
    $total += $row->eligible;
    echo "<td>{$row->name}</td>";
    echo "<td>";
    if ( $row->eligible ) {
        echo $row->eligible;
    } else {
        echo '0';
    }
    echo "</td>";
    echo "<td>";
    if ( isset( $centreArr[$row->id] ) ) {
        echo $centreArr[$row->id];
    } else {
        echo "0";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</tbody></table>";
echo "<p>{$total} patients in total</p>";
?>
