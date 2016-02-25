<?php
echo '<h3>Email investigators</h3>';
$form = new HTMLForm( 'process.php', 'post' );
$fields = $trial->getFormFields( $page );
$form->processFields( $fields );
if ( isset( $_SESSION['inputErr'] ) ) { // If any errors then add them to the form
    $form->addErrors( $_SESSION['inputErr'] );
    unset( $_SESSION['inputErr'] );
}
echo $form->writeHTML(false, true);
echo "<div class='emailAddresses well'></div>";
?>