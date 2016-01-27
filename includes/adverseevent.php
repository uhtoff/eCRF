<?php
$trialID = $trial->record->getData('core')->get('trialid');

echo '<h3>Record an adverse event for patient ID ', $trialID, '</h3>';
echo "<br/>";

$form = new HTMLForm( 'adddata.php', 'post' );
$fields = $trial->getFormFields( $page );
$form->processFields( $fields );
$form->addInput( 'hidden', 'page', $page );
$form->addInput( 'hidden', 'link_id', $trial->addRecord());
$form->addInput( 'hidden', 'return', 'adverseevent' );
$form->addCancelButton( 'index.php' );
$_SESSION['csrfToken'] = $token = base64_encode( openssl_random_pseudo_bytes(32));
$form->addInput( 'hidden', 'csrfToken', $token );
echo $form->writeHTML();