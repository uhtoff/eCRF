<?php
require_once( 'setup.php' );
session_start();

try {
    if ( isset( $_POST['page'] ) && ctype_alnum( $_POST['page'] ) ) { // If someone tries to send something odd then return to index
        $page = $_POST['page'];
    } else {
        throw new Exception ( "The page to edit hasn't been properly selected, please reload the page you are on." );
    }
    
    if ( $_POST['request'] == 'getValidation' ) {
        $sql = "SELECT value, operator, groupNum, groupType, special, "
            . "errorMessage, CONCAT_WS( '-', pages_name, fieldName ) AS label, "
            . "type FROM formVal "
            . "LEFT JOIN formFields ON formFields.id = formFields_id "
            . "WHERE pages_name = ? "
            . "ORDER BY label, groupNum";
        $pA = array( 's', $page );
        $result = DB::query( $sql, $pA );
        if ( $result->getRows() ) {
            $validArr = $rules = array();
            $currLabel = NULL;
            foreach ( $result->rows as $row ) {
                if ( !$currLabel ) {
                    $currLabel = $row->label;
                    $currType = $row->type;
                } else if ( $currLabel != $row->label ) {
                    $validArr[ $currLabel ] = array( 'type' => $currType, 'rules' => $rules );
                    $rules = array();
                    $currLabel = $row->label;
                    $currType = $row->type;
                }
                if ( !isset( $rules[$row->groupNum]['type'] ) ) {
                    $rules[$row->groupNum]['type'] = $row->groupType;
                }
                $rules[$row->groupNum][] = array(
                    'value'=>$row->value,
                    'special'=>$row->special,
                    'operator'=>$row->operator,
                    'message'=>$row->errorMessage); 
                
            }
            $validArr[ $currLabel ] = array( 'type' => $currType, 'rules' => $rules );
            echo json_encode( $validArr );
        } else {
            throw new Exception( "No validation data available." );
        }                
    } else {

        if ( $_POST['request']=='countryList') {
            if ( isset( $_POST['country'] ) ) {
                $sql = "SELECT id FROM centre WHERE country_id = ?";
                $pA = array('i', $_POST['country']);
                $result = DB::query($sql, $pA);
                $countryArr = $result->getArray('id');
                echo json_encode( $countryArr );
                exit();
            }
        } elseif ( $_POST['request'] == 'emailList' ) {
            $sql = "SELECT email FROM user LEFT JOIN centre ON user.centre_id = centre.id";
            $whereArr = array();
            $params = array();
            if ( isset($_POST['privilege']) && $_POST['privilege'] ) {
                $whereArr[] = 'privilege_id=?';
                $params[] = $_POST['privilege'];
            }
            if ( isset($_POST['country']) && $_POST['country'] ) {
                $whereArr[] = 'centre.country_id=?';
                $params[] = $_POST['country'];
            }
            if ( isset($_POST['centre']) && $_POST['centre'] ) {
                $whereArr[] = 'centre_id=?';
                $params[] = $_POST['centre'];
            }
            if ( !empty( $whereArr ) ) {
                $sql .= " WHERE ";
                $sql .= implode(' AND ',$whereArr);
                $numParam = count($params);
                $paramType = str_pad('',$numParam,'i');
                array_unshift($params,$paramType);
                $result = DB::query($sql, $params);
            } else {
                $result = DB::query($sql);
            }
            $emailArr = array();
            if ( $result->getRows() ) {
                $emailArr = $result->getArray('email');
            }
            echo json_encode($emailArr);
            exit();
        }
        
        $trial = new eCRF( $page );

        if ( isset( $_SESSION['user'] ) ) {
            $user = $_SESSION['user'];
            $loggedIn = $trial->addUser( $user );
            if ( !$loggedIn ) {
                throw new Exception( "Your session has timed out, please log in again.", 1 );
            }
        } else {
            throw new Exception( "Your session has timed out, please log in again.", 1 );
        }
        
    	if ( !$trial->addRecord() ) {  // Bind a record to the trial, if it fails then throw exception
    		throw new Exception( "No record is linked with this user, please try selecting a different record." );
    	}

        if ( $trial->user->isLocal() ) { 
            if ( $trial->record->getCentre() != $trial->user->getCentre() ) { // Ensure that a 'local' user isn't trying to manipulate someone else's record
                throw new Exception( "You have tried to manipulate a record from another centre." );
            }
        }
        
        if ( $trial->checkPageLogin( $page ) ) { // Check that the user has the privilege to access this page
            
            switch( $_POST['request'] ) {
                case 'addFlag':
                    $flag = $trial->addFlagInput( $_POST ); // Add form input to create a new flag
                    if ( $flag ) { // If a new flag was produced, return it					
                        echo json_encode( array( "message" => "Flag successfully added", "flag" => $flag ) );
                    } else { // Otherwise throw an error
                        throw new Exception( "Flag form not completely filled in, please try again." );
                    }
                    break;
                case 'clearFlag':
                    $flag = new Flag( $_POST['flag_id'] ); // Retrieve flag from DB and delete it
                    $flag->deleteFromDB();
                    echo json_encode( array( "message" => "Flag cleared" ) );
                    break;
                case 'getFlags': // Select 
                    
                    $sql = "SELECT field, id AS flag_id FROM flag 
                        WHERE pages_name = ? AND link_id = ?";
                    $id = $trial->user->isLinked();
                    $pA = array( 'si', $page, $id );				
                    $flagArr = array();
                    $result = DB::query( $sql, $pA );
                    foreach( $result->rows as $row ) {
                        $flagArr[$row->field] = new Flag( $row->flag_id );
                    }
                    echo json_encode( $flagArr );
                    break;
                              
            }
        } else {
            throw new Exception( "User not validated for this data." );
        }
    }

} catch ( Exception $e ) {
	echo json_encode( array( "message" => $e->getMessage(), "code" => $e->getCode() ) );
}
?>