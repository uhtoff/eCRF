<?php
$page = "core";

echo '<h3>Randomise a test patient:</h3>';
echo "<br/>";

$form = new HTMLForm( 'addcore.php', 'post' );
$fields = $trial->getFormFields( $page );

$data = $trial->record->getData( $page );

if ( is_null($data->get('trialid'))) {
    $id = $trial->generateTrialID();
	$data->set( 'trialid', $id );
}

$_SESSION['newTrialID'] = $data->get('trialid');

if( !isset( $data->centre_id ) ) $data->centre_id = $user->getCentre(); // Default centre id = user's own centre

// Remove all non-local centres from centre_id options

foreach( $fields['core-centre_id']['options'] as $key => $value ) {
    if ( $data->centre_id != $key ) {
        unset( $fields['core-centre_id']['options'][$key] );
    }
}

$form->processFields( $fields, $data );
if ( isset( $_SESSION['inputErr'] ) ) {
	$form->addErrors( $_SESSION['inputErr'] );
	unset( $_SESSION['inputErr'] );
}
$form->addInput( 'hidden', 'page', $page );
$form->addInput( 'hidden', 'return', 'addpt' );
$_SESSION['csrfToken'] = $token = base64_encode( openssl_random_pseudo_bytes(32));
$form->addInput( 'hidden', 'csrfToken', $token );
echo $form->writeHTML();
?>