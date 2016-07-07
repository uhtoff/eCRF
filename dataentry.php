<?php
require_once( 'setup.php' );
session_start();

$page = NULL;

$sql = "SELECT name FROM pages WHERE type='data' AND active = 1";
$result = DB::query($sql);
$pages = array();
if ( $result->getRows() ) {
    foreach( $result->rows as $row ) {
        $pages[] = $row->name;
    }   
}
// If valid input page is passed via GET use it, if not reset
if( isset( $_GET['page'] ) && in_array( $_GET['page'], $pages ) ) $page = $_GET['page'];
else {
	header( 'Location:index.php' );
	exit();
}

if ( isset( $_SESSION['user'] ) ) {
	$user = $_SESSION['user'];
	$trial = new eCRF( $page );
	$loggedIn = $trial->addUser( $user );
	if ( !$loggedIn ) {
		$_SESSION['error'] = "Log in expired due to inactivity";
		header( "Location:index.php?expire=1" );
		exit();
	}
} else {
	$_SESSION['error'] = "Log in expired due to inactivity";
	header( "Location:index.php?expire=1" );
	exit();
}

$link_id = $trial->addRecord();

$include = $trial->checkPageLogin( $page ); // Generate correct include file, assuming user has correct privilege

if ( !$link_id || !isset( $include ) || $include != $page ) { // If include isn't set or doen't equal page then go back to index as user not validated
	$_SESSION['error'] = "An error has occurred please try again";
    header( 'Location:index.php' );
	exit();
}

$trial->writeHead();

echo "<body>";
echo "<div id=\"wrap\">";
$trial->writeNavBar();
echo '<div class="container well">';

if ( isset( $_SESSION['error'] ) && $_SESSION['error'] ) {
	echo "<div class=\"alert alert-error\">";
	echo "<button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>";
	echo "{$_SESSION['error']}";
	echo "</div>";
	unset( $_SESSION['error'] );
}

$trial->writeCore();
$trial->writeDataNav();
echo "<div class=\"tab-content\">";
echo "<div class=\"tab-pane active\">";

if ( $trial->getSubPage() ) {
    $trial->writeSubDataNav();
	echo "<div class=\"tab-content\">";
}

$form = new HTMLForm( 'adddata.php', 'post' );

if ( $page == 'signpt' ) {
    echo "<div class=\"alert alert-success\">";
    echo "<button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>";
    echo "<p>Don't forget to write the " . Config::get('idName') . " on your paper case record form.  You may need to come back and check your data.</p>";
    echo "<h4>The " . Config::get('idName') . " for this record is {$trial->getTrialID()}";
    echo "</div>";
	if ( $complete = $trial->checkAllComplete() ) {
		echo "<div class=\"alert alert-info\">";
		echo "<button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>";
		echo "<p>The following pages are incomplete -</p>";
		echo "<ul>";
		foreach( $complete as $c ) {
			echo "<li>{$c}</li>";
		}
		echo "</ul>";
		echo "</div>";
        $form->addInput('hidden', 'incomplete', '1');
	}
    if ( $comment = $trial->record->get( 'comment' ) ) {
            echo "<div class=\"alert alert-info\">";
			echo "<p>The comment attached to this record is: " . nl2br(HTML::clean($comment)) . "</p>";
            echo "</div>";
	}
	if ( $trial->record->isSigned() ) {
		if ( $trial->user->canUnsign() ) {
			$input = $form->addInput( 'yesno', 'unsignpt' );
			$input->addLabel( 'Unsign record and reopen it for editing?' );
            
        } else {
			echo "<h4>The record has been signed off, please contact the PRISM admin team if you want it unsigned.</h4>";
			$form->disableForm();
		}
	} elseif ( $trial->record->isPreSigned() ) {
        $locked = true;
		if ( $trial->user->canUnPreSign() ) {
			$input = $form->addInput( 'yesno', 'unpresignpt' );
			$input->addLabel( 'Reopen the record for editing?' );
            $locked = false;
        }
        if ( $trial->user->canSign() ) {
            $input = $form->addInput( 'yesno', 'signpt' );
            $input->addLabel( 'Sign the record off as complete and accurate?' );
            $locked = false;        
        } 
        if ( $locked && !$trial->user->isCentralAdmin() ) {
			echo "<h4>The record has been marked as complete, please contact your local admins if you want to edit it.</h4>";
			$form->disableForm();
		}        
    } else {
		$input = $form->addInput( 'textarea', 'comment', nl2br(HTML::clean($comment)) );
		$input->addLabel( 'If required add text in here to explain any omissions or errors' );
		$input->addValue( $trial->record->get( 'flag' ) );
        if ( $trial->user->canPreSign() ) {
            $input = $form->addInput( 'yesno', 'presignpt' );
            $input->addLabel( 'Mark record as complete?' );
        }
        if ( $trial->user->canSign() ) {
            $input = $form->addInput( 'yesno', 'signpt' );
            $input->addLabel( 'Sign the record off as complete and accurate?' );
            $locked = false;        
        }
	}
    if ( $trial->user->canIgnore() && isset( $_SESSION['returnTo']) && $_SESSION['returnTo']==='signedandflagged' ) {
        $form->addButton('Ignore Flags', array('btn-warning','ignoreFlags'));
    }
    if ( $trial->user->isCentralAdmin() || ( $trial->user->canDelete() && !$trial->record->isSigned() && $trial->user->getCentre() == $trial->record->getCentre() ) ) {
        $form->addButton('Delete Record', array('btn-danger','deleteRecord'));
    }
} elseif ( $page == 'audit' ) {
    $creator = new eCRFUser($trial->record->get('firstuser'));
    $dt = splitDateTime($trial->record->get('created'));
    $time = $dt['time'];
    $date = convertDate( $dt['date'] );
    echo "<p>Record created on {$date} at {$time} by {$creator}</p>";
    $audit = $trial->record->getAuditData();
    echo "<p>Click on the arrows after the entries to expand and view changes made on each user session.</p>";
    echo "<ul class=\"checklist\">";
    $counter = 0;
    foreach( $audit as $session ) {
        if ( isset( $session['audit'] )) {
            $auditUser = new eCRFUser($session['user_id']);
            $dt = splitDateTime($session['startTime']);
            $time = $dt['time'];
            $date = convertDate( $dt['date'] );
            echo "<li>Accessed by {$auditUser} from {$session['userip']} on 
                {$date} at {$time} <i data-toggle=\"collapse\" 
                href=\"#collapse{$counter}\" class=\"list-toggle icon-expand\"></i></label></li>";
            echo "<ul id=\"collapse{$counter}\" class=\"collapse\">";
            foreach( $session['audit'] as $row ) {
                if ( $row->field == 'complete' ) continue;
                $field = $trial->record->getFieldData($row->tableName, $row->field);
				if ( is_null($field) ) {
					continue;
				}
                $dt = splitDateTime($row->time);
                $time = $dt['time'];
                $sql = "SELECT value FROM formVal WHERE formFields_id = ? AND operator = 'IN LIST' ORDER BY groupNum";
                $pA = array('i', $field->id);
                $ruleSearch = DB::query($sql, $pA);
                if ( $ruleSearch->getRows() ) {
                    $rule = $ruleSearch->value;
                } else {
                    $rule = NULL;
                }
                if( $field->encrypted ) {
                    $td = new Encrypt($user->getKey());
                    $row->old_value = $td->decrypt($row->old_value);
                    $row->new_value = $td->decrypt($row->new_value);
                }
                if ( $row->old_value ) {                  
                    $ov = $trial->record->displayFieldValue($row->old_value, $field->type, $rule );
                    if ( $field->type == 'checkbox' ) {
                        echo "<li>{$field->labelText} had {$ov} removed at {$time}</li>";
                    } else {
                        $nv = $trial->record->displayFieldValue($row->new_value, $field->type, $rule );
                        echo "<li>{$field->labelText} changed from {$ov} to {$nv} at {$time}</li>";
                    }
                } else {
                        $nv = $trial->record->displayFieldValue($row->new_value, $field->type, $rule );
                        if ( $field->type == 'checkbox' ) {
                            echo "<li>{$field->labelText} had {$nv} added at {$time}</li>";
                        } else {
                            echo "<li>{$field->labelText} set to {$nv} at {$time}</li>";
                        }

                }
            }
            echo "</ul>";
        }
        $counter++;
    }
    echo "</ul>";
} else {
    $page = $trial->getPage();
	$data = $trial->record; // Get data object from trial record
    $fields = $trial->getFormFields( $page ); // Get fields from DB
    $form->addID( 'dataEntry' );
    $form->processFields( $fields, $data, $trial->getUser()->getCentreUnits(), $user->getLanguage() ); // Create form from fields and data object
	if ( isset( $_SESSION['inputErr'] ) ) { // If any errors then add them to the form
		$form->addErrors( $_SESSION['inputErr'] );
		unset( $_SESSION['inputErr'] );
	}
    
    if ( $page === 'core' ) {
        $form->makeReadOnly();
    } 
	if ( ( $trial->record->isSigned() || $trial->record->isPreSigned() ) ) {
		$form->disableForm();
	}
}

$form->addClass( 'crf' ); 
$form->addInput( 'hidden', 'page', $trial->getPage() );
$form->addInput( 'hidden', 'link_id', $link_id );
$_SESSION['csrfToken'] = $token = base64_encode( openssl_random_pseudo_bytes(32));
$form->addInput( 'hidden', 'csrfToken', $token );

$centre = new Centre($trial->record->getCentre());   
if ( $centre->isLocked() && !$trial->user->isCentralAdmin() ) {
    $form->disableForm();
}

if ( $centre->getID() !== $user->getCentre() && !$trial->user->isCentralAdmin() ) {
    $form->disableForm();
}

if ( $page !== 'audit' ) {
    echo $form->writeHTML();
}
if ( $trial->getSubPage() ) {
	echo "</div>";
}
echo "</div>";
echo "</div>";
echo "</div>";
if ( $page != 'signpt' ) {
echo <<<_END
<div id="flagForm" class="modal hide fade">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3>Add a data flag</h3>
  </div>
  <form class="modal-form nomand nocheck" data-async action="processAjax.php" method="post">
  <div class="modal-body">
	<input type="hidden" name="flag-field" value="" />
	<input type="hidden" name="page" value="{$page}" />
	<input type="hidden" name="request" value="addFlag" />
	<p>What flag would you like to add to <span></span>?</p>
_END;
$sql = "SELECT id, name, textarea FROM flagType ORDER BY id";
$result = DB::query( $sql );
foreach( $result->rows as $row ) {
	$textarea = HTML::clean( $row->textarea );
	$id = HTML::clean( $row->id );
	$name = HTML::clean( $row->name );
	echo "<label class=\"radio\"><input type=\"radio\" name=\"flag-flagType_id\" textarea=\"{$textarea}\" value=\"{$id}\" />{$name}</label>";
}
	echo <<<_END
  </div>
  <div class="modal-footer">
    <a href="#" data-dismiss="modal" class="btn nocheck">Close</a>
    <button type="submit" class="btn">Submit</button>
	</div>
	</form>
</div>
_END;
} else {
echo <<<_END
<div id="deleteForm" class="modal hide fade">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3>Confirm record deletion</h3>
  </div>
  <form class="modal-form nomand nocheck" action="#" method="post">
  <div class="modal-body">
    <p>
    <label>Reason for deleting record:</label>
    <textarea class="input-xlarge" name="deleteReason"></textarea>
    </p>
    <p>
    <label>Please confirm your password</label>
    <input type="password" name="passwordConfirm" />
    </p>
  <div class="modal-footer">
    <button type="submit" class="btn btn-primary nocheck">Submit</button>
    <a href="#" data-dismiss="modal" class="btn btn-cancel nocheck">Cancel</a>
	</div>
	</form>
</div>
_END;
}
if ( $page == 'postop' ) {
    echo <<<_END
    <div id="mortModal" class="modal hide fade">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3>Vital status at Discharge</h3>
  </div>
  <div class="modal-body">
    <p>Please confirm that the patient died</p>
  </div>
  <div class="modal-footer">
    <a href="#" data-dismiss="modal" class="btn btn-primary nocheck">Confirm</a>
    <a href="#" data-dismiss="modal" class="btn btn-cancel nocheck">Cancel</a>
  </div>
</div>
_END;
}
echo "<div id=\"push\"></div>";
echo '</div>';

$trial->writeFooter();
?>