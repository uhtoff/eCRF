<?php
$sql = "SELECT COUNT(table_id) as crfCount, DATE(new_value) AS dateOnly FROM linkAudit WHERE field = 'created' GROUP BY dateOnly";
$result = DB::query( $sql );
$sql = "SELECT COUNT(table_id) as crfCount, DATE(time) AS dateOnly FROM linkAudit WHERE field = 'signed' GROUP BY dateOnly";
$signed = DB::query( $sql );
$signedArr = $signed->getArray('crfCount', 'dateOnly');
if ( isset( $trial->user ) ) {
    $sql = "SELECT COUNT(table_id) as crfCount, DATE(new_value) AS dateOnly FROM linkAudit LEFT JOIN link ON link.id = linkAudit.table_id LEFT JOIN core ON link.core_id = core.id LEFT JOIN centre ON core.centre_id = centre.id WHERE field = 'created' AND country_id = ? GROUP BY dateOnly";
    $pA = array('i', $trial->getUser()->getCountry() );
    $country = DB::query($sql,$pA);
    $countryArr = $country->getArray('crfCount', 'dateOnly');
}
if ( isset($countryArr)) {
echo <<<_END
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Day', 'Created', 'Created in your country', 'Signed', 'Total', 'Your country\'s total', 'Signed Total'],
_END;
$array = '';
$signedTotal = $total = $countryTotal = 0;
foreach( $result->rows as $row ) {
    $total += $row->crfCount;
    $signedNum = 0;
    $countryNum = 0;
    if ( isset( $signedArr[$row->dateOnly]) ) {
        $signedNum = $signedArr[$row->dateOnly];
    }
    if ( isset( $countryArr[$row->dateOnly] ) ) {
        $countryNum = $countryArr[$row->dateOnly];
    }
    $signedTotal += $signedNum;
    $countryTotal += $countryNum;
    $array .= "['{$row->dateOnly}',{$row->crfCount}, $countryNum, $signedNum, $total, $countryTotal, $signedTotal],";
}
$array = rtrim($array, ',');
echo $array;
} else {
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
}
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
?>

