<?php
/**
 * Description of Record
 *
 * @author Russ
 */
class Record extends DBObject {
    private $objects; 
// To manage the link table - plan to add a currUser field to lock the record, if so then to get rid of last user/last mod as audit table now present
	 public function preSignRecord() {
		if ( $this->presigned == 0 ) {
			$this->presigned = 1;
			$this->presignedby = $this->lastuser;
			$this->saveToDB();
		}
	}
	public function unPreSignRecord() {
		if ( $this->presigned == 1 ) {
			$this->presigned = 0;
			$this->saveToDB();
		}
	}
    public function signRecord() {
		if ( $this->signed == 0 ) {
			$this->signed = 1;
			$this->signedby = $this->lastuser;
			$this->saveToDB();
		}
	}
	public function unsignRecord() {
		if ( $this->signed == 1 ) {
			$this->signed = 0;
			$this->saveToDB();
		}
	}
    public function ignoreFlag() {
        $this->ignored = 1;
        $this->ignoredby = $this->lastuser;
        $this->saveToDB();
    }
    public function isPreSigned() {
        $isPreSigned = false;
		if ( $this->presigned == 1 ) {
			$isPreSigned = true;
		}
		return $isPreSigned;
    }
	public function isSigned() {
		$isSigned = false;
		if ( $this->signed == 1 ) {
			$isSigned = true;
		}
		return $isSigned;
	}
	public function addUser( eCRFUser $user ) {
		$id = $user->getID();
		$time = date('Y-m-d H:i:s');
		if ( !isset($this->firstuser) ) {
			$this->set( 'firstuser', $id );
			$this->set( 'created', $time );
		}
		$this->set( 'lastuser', $id );
		$this->set( 'lastmod', $time );
	}
	public function checkDuplicate() {
		$checkDuplicate = false;
		$sql = "SELECT link.id AS id FROM link INNER JOIN core ON link.core_id = core.id WHERE centre_id = ? AND trialid = ?";
		$core = $this->objects['core'];
		$pA = array( 'is', $core->get( 'centre_id' ), $core->get( 'trialid' ) );
        $result = DB::query( $sql, $pA );
		if ( $result->getRows() > 0 ) {
			if ( !$this->getID() ) { // If linkid not yet set then any match will suggest a duplicate
				$checkDuplicate = true;
			} else { // Else go through results (should only be one...) and make sure that the linkID is the same as the current
				foreach( $result->rows as $row ) {
					if ( $row->id != $this->getID() ) {
						$checkDuplicate = true;
					}
				}
			}
		}
		return $checkDuplicate;
	}

    /**
     * @param string $page
     * @return Data|bool
     */
	public function getData( $page ) {
        $getData = false;
		if ( !isset( $this->objects[$page] ) ) {
			$sql = "SELECT class FROM pages WHERE name = ?";
			$pA = array( 's', $page );
            $result = DB::query( $sql, $pA );
			if ( $result->getRows() ) {
				if ( $class = $result->class ) {
                    // @TODO Fix this shit
                    if ( $page == 'oneyearit' ) {
                        $page = 'oneyear';
                    }
					$this->objects[$page] = new Data( NULL, $class );
					$pKey = $this->objects[$page]->getPKey();
					if ( isset( $this->{$page . '_' . $pKey} ) ) {
						$this->objects[$page]->setID( $this->{$page . '_' . $pKey} );
					}
                    $this->objects[$page]->getFromDB();
				}
			}
		}
        if ( isset( $this->objects[$page] ) ) {
            $getData = $this->objects[$page];
        }
		return $getData;
	}
    public function getAllData() {
        $sql = "SELECT name FROM pages WHERE type = 'data' AND active = 1";
        $result = DB::query( $sql );
        foreach ( $result->rows as $row ) {
            $this->getData($row->name);
        }
    }
    public function deleteAllData($userID, $reason = NULL, $removeAudit = false, $auditDelete = true) {
        $sql = "INSERT INTO deleted ( core_trialid, reason, user_id ) VALUES ( ?, ?, ? )";
        $pA = array('ssi', $this->getData('core')->get( 'trialid' ), $reason, $userID );
        DB::query( $sql, $pA );
        $sql = "SELECT name FROM pages WHERE type = 'data' AND active = 1 AND class IS NOT NULL";
        $result = DB::query( $sql );
        foreach ( $result->rows as $row ) {
            $data = $this->getData($row->name)->deleteFromDB($removeAudit,$auditDelete);
        }
        $this->deleteFromDB($removeAudit,$auditDelete);
    }
    public function getAuditData() {
        $userSessions = array();
        $sql = "SELECT type, user_id, userip, time FROM userAudit 
            WHERE field = 'link_id' AND ( new_value = ? OR old_value = ? )";
        $pA = array( 'ii', $this->getID(), $this->getID() );
        $result = DB::query($sql, $pA);
        $counter = 0;
        if ( $result->getRows() ) {
            foreach( $result->rows as $row ) {
                if ( $row->type == "DELETE" ) {
                    $userSessions[$counter]['endTime'] = $row->time;
                    $counter++;
                } else {
                $userSessions[$counter] = array(
                    'user_id' => $row->user_id,
                    'userip' => $row->userip,
                    'startTime' => $row->time);
                }
            }
        }
        $this->getAllData();
        foreach( $this->objects as $key => $value ) {
            $tables[] = array(
                    'table' => "{$key}",
                    'auditTable' => "{$key}Audit",
                    'key' => $this->objects[$key]->getID() );
        }
        $tables[] = array(
            'table' => "link",
            'auditTable' => "linkAudit",
            'key' => $this->getID()
        );
        foreach( $userSessions as $key => $session ) {
            $start = $end = NULL;
            if ( isset( $session['startTime'] ) ) {
                $start = $session['startTime'];
                if ( isset( $session['endTime'] ) ) {
                    $end = $session['endTime'];
                } else {
                    $end = 'NOW()';
                }
                $sql = "SELECT * FROM ( ";
                $sqlUnion = array();
                foreach( $tables as $table ) {
                    if ( isset( $table['key']) ) {
                        $sqlUnion[] = "SELECT *, '{$table['table']}' as tableName 
                            FROM {$table['auditTable']} 
                            WHERE table_id = {$table['key']} 
                                AND ( time >= '{$start}' AND time <= " . ( $end === 'NOW()' ? $end : "'{$end}'" ) . " )";
                    }
                }
                $sql .= implode( ' UNION ', $sqlUnion );
                $sql .= ") a ORDER BY time";
                $result = DB::query($sql);
                if ( $result->getRows() ) {
                    $userSessions[$key]['audit'] = $result->rows;
                }
            }
        }
        return $userSessions;
        
    }
    public function getFieldData( $pages_name, $field ) {
        $sql = "SELECT classFields.encrypted as encrypted, formFields.labelText as labelText, formFields.type as type, formFields.id as id FROM formFields
            LEFT JOIN pages ON formFields.pages_name = pages.name
            LEFT JOIN classes ON pages.class = classes.name
            LEFT JOIN classFields ON classes.id = classFields.classes_id AND formFields.fieldName = classFields.name
            WHERE pages_name = ? AND fieldName = ?";
        $pA = array( 'ss', $pages_name, $field );
        $result = DB::query( $sql, $pA );
        if ( $result->getRows() ) {
            return $result;
        }
        
    }
    public function displayFieldValue( $value, $type, $rule ) {
        $displayFieldValue = $value;
        switch( $type ) {
            case 'yesno':
                $displayFieldValue = $value == 1 ? 'Yes' : 'No';
                break;
            case 'select':
            case 'checkbox':
            case 'radio':
                $sql = "SELECT option_text FROM {$rule} WHERE option_value = ? AND language_code = 'en'";
                $pA = array( 'i', $value );
                $result = DB::query($sql, $pA);
                if ( $result->getRows() ) {
                    $displayFieldValue = $result->option_text;
                }
                break;
            case 'username':
                $user = new eCRFUser($value);
                $displayFieldValue = $user;
                break;
            case 'date':
                $displayFieldValue = convertDate($value);
                break;
            case 'datetime':
                $dt = splitDateTime($value);
                $time = $dt['time'];
                $date = convertDate( $dt['date'] );
                $displayFieldValue = $date . ' at ' . $time;
        }
        return $displayFieldValue;
    }
    public function getAuditTable( $table, $id ) {
        $sql = "SELECT * FROM {$table}Audit WHERE table_id = ? ORDER BY time";
        $pA = array( 'i', $id );
        $result = DB::query($sql, $pA);
        if ( $result->getRows() ) {
            foreach( $result->rows as $row ) {
                
            }
        }
    }
    public function getField( $page, $field ) {
        $object = $this->getData($page);
        if ( $object ) {
            return $object->get($field);
        }
        
    }
    public function isFieldEncrypted( $page, $field ) {
        $object = $this->getData($page);
        if ( $object ) {
            return $object->isEncrypted($field);
        }
    }
    public function getCentre() {
		return $this->getField('core','centre_id');
	}
	public function saveToDB( $prop = NULL ) {
        if ( isset( $this->objects ) ) {
            foreach( $this->objects as $page => $object ) {
                /** @var $object DBObject */
                $this->{$page . '_' . $object->getPKey()} = $object->saveToDB();
            }
        }
		return parent::saveToDB( $prop );
	}
}
?>
