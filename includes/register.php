<?php
echo '<h3>Register for the site</h3>';
echo '<h4>Please complete the form below and submit it, registration is not automatic and may take up to 5 working days</h4>';
$form = new HTMLForm( 'process.php', 'post' );
$fields = $trial->getFormFields( $page );
if ( isset( $_SESSION[$include] ) ) {
	$data = $_SESSION[$include];
} else {
	$data = new eCRFUser();
}
$form->processFields( $fields, $data );
if ( isset( $_SESSION['inputErr'] ) ) { // If any errors then add them to the form
	$form->addErrors( $_SESSION['inputErr'] );
	unset( $_SESSION['inputErr'] );
}
$input = $form->addInput( 'recaptcha', 'recaptcha' ); // Add reCAPTCHA
$input->addLabel( "Please complete the reCAPTCHA" );
$form->addInput( 'hidden', 'page', $page );
$_SESSION['csrfToken'] = $token = base64_encode( openssl_random_pseudo_bytes(32));
$form->addInput( 'hidden', 'csrfToken', $token );
echo $form->writeHTML();
?>