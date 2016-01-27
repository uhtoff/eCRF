<?php
echo "<h4>Add a centre</h4>";
if ( isset($_SESSION['newCentre'])) {
    $centre = $_SESSION['newCentre'];
} else {
    $centre = new Centre();
}
$form = new HTMLForm( 'process.php', 'post' );
$fields = $trial->getFormFields( $page );
$form->processFields( $fields, $centre );
$form->addInput( 'hidden', 'page', $page );
$form->addCancelButton( 'index.php?page=sitereg' );
$sql = "SELECT units.number, units.name FROM units WHERE number IN ( SELECT number FROM formFields
                LEFT JOIN units ON units.number=formFields.fieldname
                GROUP BY units.number )
                GROUP BY units.number
                HAVING count(units.number)>1";
$numbers = DB::cleanQuery( $sql );
if ( $numbers->getRows() ) {
    $defUnits = $trial->getUser()->getCentreUnits();
    foreach( $numbers->rows as $row ) {
        $input = $form->addInput('select',"units[{$row->number}]");
        $input->addLabel("Units for {$row->name}");
        $sql = "SELECT id, unit FROM units
            WHERE number = ?
            ORDER BY unitorder";
        $pA = array('s',$row->number);
        $units = DB::cleanQuery($sql,$pA);
        $options = array();
        foreach( $units->rows as $unitRow ) {
            $options[$unitRow->id] = $unitRow->unit;
        }
        $input->addOption($options, true);
        $input->setMand();
    }
}
if ( isset( $_SESSION['inputErr'] ) ) { // If any errors then add them to the form
    $form->addErrors( $_SESSION['inputErr'] );
    unset( $_SESSION['inputErr'] );
}
$_SESSION['csrfToken'] = $token = base64_encode( openssl_random_pseudo_bytes(32));
$form->addInput( 'hidden', 'csrfToken', $token );
echo $form->writeHTML();
?>
