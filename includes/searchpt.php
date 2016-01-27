<?php
echo "<h3>Enter the full " . Config::get('idName') . " of the patient you'd like to enter data for</h3>";
$form = new HTMLForm( 'process.php', 'post' );
$fields = $trial->getFormFields( $page );
$form->processFields( $fields ); // Sends user in to make centre_id defaulted
if ( isset( $_SESSION['inputErr'] ) ) { // If any errors then add them to the form
	$form->addErrors( $_SESSION['inputErr'] );
	unset( $_SESSION['inputErr'] );
}
$form->addInput( 'hidden', 'page', $page );
$_SESSION['csrfToken'] = $token = base64_encode( openssl_random_pseudo_bytes(32));
$form->addInput( 'hidden', 'csrfToken', $token );
echo $form->writeHTML();
?>