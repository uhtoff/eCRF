<?php
echo '<h3>Update your details</h3>';
echo '<h4>If you wish to change your password, provide your current password and confirm the new password</h4>';

$form = new HTMLForm( 'process.php', 'post' );
$fields = $trial->getFormFields( $page );
$form->processFields( $fields, $user );
if ( isset( $_SESSION['inputErr'] ) ) { // If any errors then add them to the form
	$form->addErrors( $_SESSION['inputErr'] );
	unset( $_SESSION['inputErr'] );
}
$form->addInput( 'hidden', 'page', $page );
$_SESSION['csrfToken'] = $token = base64_encode( openssl_random_pseudo_bytes(32));
$form->addInput( 'hidden', 'csrfToken', $token );
echo $form->writeHTML();
?>