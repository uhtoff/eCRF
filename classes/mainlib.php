<?php
class Flag extends Data {
	public function isComplete() {
		$isComplete = true;
		if ( emptyInput( $this->flagType_id ) || ( $this->flagType_textarea == true && emptyInput( $this->flagText ) ) ) {
			$isComplete = false;
		}
		return $isComplete;
	}
    public function getFieldName() {
		$fieldName = substr($this->get('field'), strpos($this->get('field'), "-") + 1);
        return $fieldName;
    }
}

function write_error( $table, $column, $type, $validation ) {
	$error = "";
	if( $type == "number" && preg_match( "/^\d+\.?\d*\/\d+\.?\d*$/", $validation ) ) {
		$valid = explode( '/', $validation );
		$error = "Please enter a value between {$valid[0]} and {$valid[1]}";
		$sql = "SELECT unit FROM units WHERE unitorder = 1 AND number = ?";
		$pA = array( 's', $column );
        $result = DB::query( $sql, $pA );
		if( $result->getRows() ) {
			$error .= " {$result->unit}";
		}
	} else {
		$sql = "SELECT error FROM labels WHERE tablename=? AND columnname=?";
		$pA = array( 'ss', $table, $column );
		$result = DB::query( $sql, $pA );
		$error = $result->error;
	}
	echo '<br /><strong class="error">' , HTML::clean( $error ) , '</strong>';
}

function write_search_table( $type, $acc = false, $active = false, $centre = NULL ) {
	$user = $_SESSION['user'];
    $pA = array();
	// Central admin and above allowed to see whole dataset, local users just local // WHen this is moved, remember to fix it!
    switch( $type ) {
        case 'yourcrfs':
            $caption = "Your CRFs";
            $sql = "SELECT link.id AS link_id, 
					centre.name AS name, 
					core.trialid AS trialid, 
					link.signed AS signed,
                    link.presigned AS presigned,
                    link.lastmod AS lastmod
				FROM link 
					INNER JOIN core ON link.core_id = core.id 
					INNER JOIN centre ON core.centre_id = centre.id 
                WHERE firstuser = ?";
            $pA = array('i', $user->getID() );
            $result = DB::query($sql, $pA);
            break;
        case 'sitecrfs':
            $caption = "All CRFs entered by your site";
            $sql = "SELECT link.id AS link_id, 
					centre.name AS name, 
					core.trialid AS trialid, 
					link.signed AS signed,
                    link.presigned AS presigned,
                    link.lastmod AS lastmod,
					centre.id AS centre_id,
					MIN( coreAudit.time ) AS time_entered
				FROM link 
					INNER JOIN core ON link.core_id = core.id 
					INNER JOIN centre ON core.centre_id = centre.id
					LEFT JOIN coreAudit ON coreAudit.table_id = core.id
                WHERE centre.id = ?
                GROUP BY link.id";
            $pA = array('i', $user->getCentre() );
            $result = DB::query($sql, $pA);
            break;
        case 'siteunsigncrfs':
            $caption = "Completed CRFs for you to sign";
            $none = 'There are no unflagged CRFs for you to sign';
            $sql = "SELECT link.id AS link_id, 
					centre.name AS name, 
					core.trialid AS trialid, 
					link.signed AS signed,
                    link.presigned AS presigned,
                    link.lastmod AS lastmod,
                    link.comment AS comment
				FROM link 
					INNER JOIN core ON link.core_id = core.id 
					INNER JOIN centre ON core.centre_id = centre.id 
                    LEFT JOIN flag ON link.id = flag.link_id 
                WHERE centre.id = ? 
                    AND presigned = 1 
                    AND signed = 0
                    AND ( link.comment IS NULL OR link.comment = '' ) 
                    AND flag.id IS NULL";
            $pA = array('i', $user->getCentre() );
            $result = DB::query($sql, $pA);
            break;
        case 'siteflaggedcrfs':
            $caption = "Completed CRFs that have been flagged as requiring checking before signing";
            $none = 'There are no flagged CRFs for you to sign';
             $sql = "SELECT link.id AS link_id, 
					centre.name AS name, 
					core.trialid AS trialid, 
					link.signed AS signed,
                    link.presigned AS presigned,
                    link.lastmod AS lastmod,
                    link.comment AS comment
				FROM link 
					INNER JOIN core ON link.core_id = core.id 
					INNER JOIN centre ON core.centre_id = centre.id 
                    LEFT JOIN flag ON link.id = flag.link_id 
                WHERE centre.id = ? 
                    AND presigned = 1 
                    AND signed = 0
                    AND (( link.comment IS NOT NULL AND link.comment != '' ) 
                    OR flag.id IS NOT NULL )
                GROUP BY link.id";
            $pA = array('i', $user->getCentre() );
            $result = DB::query($sql, $pA);
            break;
        case 'signedandflagged':
            $caption = "Signed CRFs that are flagged as having incomplete data";
            $none = 'There are no signed and flagged CRFs';
             $sql = "SELECT link.id AS link_id, 
					centre.name AS name, 
					core.trialid AS trialid, 
					link.signed AS signed,
                    link.presigned AS presigned,
                    link.lastmod AS lastmod,
                    link.comment AS comment
				FROM link 
					INNER JOIN core ON link.core_id = core.id 
					INNER JOIN centre ON core.centre_id = centre.id 
                    LEFT JOIN flag ON link.id = flag.link_id 
                WHERE signed = 1
                    AND ignored = 0
                    AND (( link.comment IS NOT NULL AND link.comment != '' ) 
                    OR flag.id IS NOT NULL )";
             if ( $user->isRegional() ) $sql .= " AND centre.id = ?";
             $sql .=  " GROUP BY link.id";
             if ( $user->isRegional() ) {
                $pA = array('i', $user->getCentre() );
                $result = DB::query($sql, $pA);
             } else {
                 $result = DB::query( $sql );
             }
            break;
        case 'incompletecrfs':
            $caption = "Incomplete CRFs from your site";
            $none = 'There are no incomplete CRFs from your site';
             $sql = "SELECT link.id AS link_id, 
					centre.name AS name, 
					core.trialid AS trialid, 
					link.signed AS signed,
                    link.presigned AS presigned,
                    link.lastmod AS lastmod
				FROM link 
					INNER JOIN core ON link.core_id = core.id 
					INNER JOIN centre ON core.centre_id = centre.id 
                WHERE centre.id = ? AND 
                    presigned = 0";
            $pA = array('i', $user->getCentre() );
            $result = DB::query($sql, $pA);
            break;
		case 'countrycrfs':
			if ( !($user->isCentralAdmin() || $user->isRegionalAdmin() ) ) {
				exit('Please select another option');
			}
            $caption = "All CRFs from your country";
            $sql = "SELECT link.id AS link_id, 
					centre.name AS name, 
					core.trialid AS trialid, 
					link.signed AS signed,
                    link.presigned AS presigned,
                    link.lastmod AS lastmod,
                    country.name AS country,
					centre.id AS centre_id,
					MIN( coreAudit.time ) AS time_entered
				FROM link 
					INNER JOIN core ON link.core_id = core.id 
					INNER JOIN centre ON core.centre_id = centre.id
                    INNER JOIN country ON centre.country_id = country.id
                    LEFT JOIN coreAudit ON coreAudit.table_id = core.id
				WHERE country.id = ?
				GROUP BY link.id";
			$centre = new Centre($user->getCentre());
            $pA = array('i',$centre->get('country_id'));
            $result = DB::query($sql, $pA);
            break;
        case 'all':
			if ( !$user->isCentralAdmin() ) {
				exit('Please select another option');
			}
            $caption = "All CRFs";
            if ( $centre ) {
                $caption .= " from centre {$centre}";
            }
             $sql = "SELECT link.id AS link_id, 
					centre.name AS name, 
					core.trialid AS trialid, 
					link.signed AS signed,
                    link.presigned AS presigned,
                    link.lastmod AS lastmod,
                    country.name AS country,
					centre.id AS centre_id,
					MIN( coreAudit.time ) AS time_entered
				FROM link 
					INNER JOIN core ON link.core_id = core.id 
					INNER JOIN centre ON core.centre_id = centre.id
                    INNER JOIN country ON centre.country_id = country.id
                    LEFT JOIN coreAudit ON coreAudit.table_id = core.id ";
             if ( $centre ) {
                $sql .= "WHERE centre.id = ? ";
                 $sql .= "GROUP BY link.id";
            $pA = array('i',$centre);
            $result = DB::query($sql, $pA);
            } else {
                 $sql .= "GROUP BY link.id";
                $result = DB::query($sql);
                }
            break;
    }
    if ( $type == 'recent' ) {
		$sql = "SELECT link.id AS link_id, 
					centre.name AS name, 
					core.trialid AS trialid, 
					link.signed AS signed,
                    link.presigned AS presigned
				FROM link 
					INNER JOIN core ON link.core_id = core.id 
					INNER JOIN centre ON core.centre_id = centre.id ";
        switch( $user->getPrivilege() ) {
            case 10:
                $sql .= "WHERE centre_id=" . DB::clean( $user->getCentre() ) . " ";
                break;
            case 15:
                $sql .= "WHERE centre_id=" . DB::clean( $user->getCentre() ) . " AND signed = 0 ";
                break;
        }
		$sql .= "ORDER BY lastmod DESC, centre_id 
						LIMIT 0, 10";
        $caption = 'Recently entered patients';
    } elseif ( $type == 'unsigned' ) {
        $sql = "SELECT *, link.id AS link_id FROM link INNER JOIN core ON link.core_id = core.id INNER JOIN centre ON core.centre_id = centre.id WHERE centre_id=" . DB::clean( $user->getCentre() ) . " AND signed = 0 ORDER BY lastmod DESC, centre_id";
        $caption = 'Incomplete CRFs from your site (max 10)';
        $none = 'No incomplete CRFs found';
    } elseif ( $type == 'signedsite' ) {
        $sql = "SELECT *, link.id AS link_id FROM link INNER JOIN core ON link.core_id = core.id INNER JOIN centre ON core.centre_id = centre.id WHERE centre_id=" . DB::clean( $user->getCentre() ) . " AND signed = 1 ORDER BY lastmod DESC, centre_id";
        $caption = 'Unflagged CRFs for you to sign (max 10)';
        $none = 'No unflagged CRFs for you to sign found';
    } elseif ( $type == 'unsigneduser' ) {
        $sql = "SELECT *, link.id AS link_id FROM link INNER JOIN core ON link.core_id = core.id INNER JOIN centre ON core.centre_id = centre.id WHERE centre_id=" . DB::clean( $user->getCentre() ) . " AND signed = 0 AND firstuser = " . DB::clean( $user->getID() ) . " ORDER BY lastmod DESC, centre.id";
        $caption = 'Your incomplete CRFs (max 10)';
         $none = 'No incomplete CRFs of yours found';
    } elseif ( $type == 'flagged' ) {
		$sql = "SELECT *, link.id AS link_id, COUNT( flag.id ) AS name FROM flag LEFT JOIN link ON flag.link_id = link.id INNER JOIN core ON core.id = link.core_id WHERE core.centre_id = " . DB::clean( $user->getCentre() ) . " AND signed = 1 GROUP BY link.id ORDER BY lastmod DESC";
		$caption = 'Flagged CRFs from your site (max 10)';
         $none = 'No flagged CRFs for you to sign found';
	}
//    $result = DB::query( $sql );
	if( $result->getRows() ) {
		echo "<div class=\"container well\" style=\"background-color:#FFFFFF;\">";
        if ( $acc ) { 
            echo "<div class=\"accordion-group\">";
            echo "<div class=\"accordion-heading\">";
            if ( $active ) {
                $class = 'active';
                $collapse = 'in';
            } else {
                $collapse = $class = '';
            }
            echo "<a class=\"accordion-toggle {$class}\" data-toggle=\"collapse\" data-parent=\"#{$acc}\" href=\"#{$type}\">";
            echo $caption;
            echo "</a>";
            echo "</div>\n";
            echo "<div id=\"{$type}\" class=\"accordion-body collapse {$collapse}\">";
            echo "<div class=\"accordion-inner\">";
        } else {
            echo "<h3>$caption</h3>"; 
        }
        echo "<p>Click on any heading to sort by that field.</p>";
		echo '<form class="nomand" action="process.php" method="post">';
        ob_start();
		echo '<table id="searchTable';
        if ( $type == 'all' ) echo "All";
        echo '" class="table table-striped table-bordered table-hover"><thead><tr><th scope="col">' . Config::get('idName') . '</th><th scope="col">Centre</th>';
        if ( $type == 'all' ) echo '<th scope="col">Country</th>';
        echo '<th scope="col">Date Entered</th><th scope="col">Completed?</th><th scope="col">Signed?</th><th scope="col">Action</th><th scope="col">Last modified</th></tr></thead>';
		echo "<tbody>\n";
		for( $i = 0; $i < $result->num_rows; $i++ ) {
			echo '<tr class="clickable"><td>' , HTML::clean( $result->rows[$i]->trialid ) , '</td><td>' , HTML::clean( $result->rows[$i]->name ) , '</td>';
            if ( $type == 'all' ) echo "<td>{$result->rows[$i]->country}</td>";
            echo "<td>{$result->rows[$i]->time_entered}</td>";
            echo '<td>';
			echo $result->rows[$i]->presigned == 1 ? 'Yes' : 'No';
            echo '</td><td >';
            echo $result->rows[$i]->signed == 1 ? 'Yes' : 'No';
			echo '</td><td class="clickable">';
            $link_id = HTML::clean($result->rows[$i]->link_id);
            echo '<input class="radio" type="radio" name="searchpt-link_id" value="', $link_id, '" />';
            echo '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
            echo '<select class="action-', $link_id, '" name="searchpt-action" disabled>';
            echo '<option>No action</option>';
            if ( !$result->rows[$i]->signed ) {
				if ( $user->isCentralAdmin() || ( $user->getCentre() == $result->rows[$i]->centre_id ) ) {
					echo '<option value="data">Enter data</option>';
					echo '<option value="ae">Record an adverse event</option>';
					echo '<option value="withdraw">Withdraw a patient</option>';
                    echo '<option value="violation">Record a protocol deviation</option>';
				} elseif ( $user->isRegionalAdmin() ) {
					echo '<option value="data">View record</option>';
				}
            } else {
                echo '<option value="data">View record</option>';
                if ( $user->canUnsign() && ( $user->isCentralAdmin() || ( $user->getCentre() == $result->rows[$i]->centre_id ) ) ) {
                    echo '<option value="unsign">Unsign and edit record</option>';
                }
            }

            echo '</select>';

			echo '</td><td>';
            echo HTML::clean( $result->rows[$i]->lastmod );
            echo '</td></tr>';
			echo "\n";
		}
		echo '</tbody></table><p>';
		echo "<input type=\"hidden\" name=\"page\" value=\"searchpt\">";
        if ( $type == 'siteunsigncrfs' ) {
            echo "<input type=\"hidden\" name=\"sign\" value =\"1\">";
        }
        $_SESSION['csrfToken'] = $token = base64_encode( openssl_random_pseudo_bytes(32));
        echo "<input type=\"hidden\" name=\"csrfToken\" value=\"{$token}\"/>";
        echo "<div class=\"form-actions\">
            <button type=\"submit\" class=\"btn btn-primary\">Select</button>
            </div>";
        ob_end_flush();
		echo '</form>';
        if ( $acc ) {
            echo "</div>\n";
            echo "</div>\n";
            echo "</div>\n";
        }
		echo "</div>";
	} else {
        if ( isset( $none ) ) {
            echo "<h3>{$none}</h3>";
        } else {
            echo "<h3>No records found.</h3>";
        }
    }
}

function write_user_table( $search, $caption ) {
	$i = 1;
	echo "<table class=\"table table-striped table-bordered table-hover dataTable\"><caption><h4>{$caption}</h4></caption><thead><tr>";
    echo '<th scope="col" class="span3">Name</th><th scope="col" class="span4">Email</th><th scope="col" class="span4">Centre</th><th scope="col" class="span1">Select</th></tr></thead>';
	echo "\n";
	echo "<tbody>";
	foreach ( $search->rows as $row ) {
		echo '<tr><td>', HTML::clean( $row->forename ) , ' ' , HTML::clean( $row->surname );
		echo '</td><td>', HTML::clean( $row->email ), '</td><td>' , HTML::clean( $row->centre_name ) , '</td><td>';
		echo '<input type="checkbox" name="useradm_id[]" value="' , HTML::clean( $row->userid ) , '" />';
		echo '</td></tr>';
		echo "\n";
	}
	echo "</tbody></table>";
}
?>