<?php
echo '<h3>Site information</h3>';
$form = new HTMLForm( 'process.php', 'post' );
$fields = $trial->getFormFields( $page );
$data = new Centre( $user->getCentre() );
$form->processFields( $fields, $data );
if ( isset( $_SESSION['inputErr'] ) ) { // If any errors then add them to the form
	$form->addErrors( $_SESSION['inputErr'] );
	unset( $_SESSION['inputErr'] );
}
$form->disableInput('siteinfo-name');
$form->addInput( 'hidden', 'page', $page );
$_SESSION['csrfToken'] = $token = base64_encode( openssl_random_pseudo_bytes(32));
$form->addInput( 'hidden', 'csrfToken', $token );
echo $form->writeHTML();
?>