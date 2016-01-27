<?php
$sql = "SELECT units.number, units.name FROM units WHERE number IN ( SELECT number FROM formFields 
                LEFT JOIN units ON units.number=formFields.fieldname 
                GROUP BY units.number )
                GROUP BY units.number
                HAVING count(units.number)>1";
$numbers = DB::cleanQuery( $sql );
if ( $numbers->getRows() ) {
    echo <<<_END
    <h3>Please select default units for your site:</h3>
    <form action="process.php" method="POST" class="nomand nocheck form-horizontal">
        <input type="hidden" name="page" value=$include>
_END;
    $defUnits = $trial->getUser()->getCentreUnits();
    foreach( $numbers->rows as $row ) {
        echo "<div class=\"control-group\">";
        echo "<label class=\"control-label\" for=\"{$row->number}\">{$row->name}: </label>";
        echo "<div class=\"controls\">";
        echo "<select class=\"input-small\" id=\"{$row->number}\" name=\"{$row->number}\">";
        $sql = "SELECT id, unit FROM units 
            WHERE number = ? 
            ORDER BY unitorder";
        $pA = array('s',$row->number);
        $units = DB::cleanQuery($sql,$pA);
        foreach( $units->rows as $unitRow ) {
            echo "<option value={$unitRow->id}";
            if ( $defUnits && $defUnits[$row->number]['units_id'] == $unitRow->id ) {
                echo " selected=\"selected\" ";
            }
            echo ">{$unitRow->unit}</option>";
        }
        echo "</select>";
        echo "</div>";
        echo "</div>";
    }
    $_SESSION['csrfToken'] = $token = base64_encode( openssl_random_pseudo_bytes(32));
    echo "<input type=\"hidden\" name=\"csrfToken\" value=\"{$token}\"/>";
    	echo "<div class=\"form-actions\">
		<button type=\"submit\" class=\"btn btn-primary\">Submit</button>

		</div>";
    echo "</form>";
} else {
    echo "<h3>This study uses no values with multiple units.</h3>";
}
?>
