<?php
/**
 * Description of ValidationResult
 *
 * @author Russ
 */
class ValidationResult {
    protected $_value, $_valRules, $_varType, $_error, $_data, $_page, $_fieldName;
    protected $_unit = 1;
    protected $_valid = NULL;
    public function __construct($value, ValidationRules $valRules, $data = NULL ) {
        $this->_value = $value;
        $this->_valRules = $valRules;
        $this->_varType = $valRules->getType();
        $this->_page = $valRules->getPage();
        $this->_fieldName = $valRules->getFieldName();
        $this->_data = $data;
        $this->validate();
    }
    /**
     * Check if value has been validated, if not then validate it and return 
     * if valid.  Allows clearing of valid status if needed
     * @return boolean
     */
    public function isValid() {
        $isValid = false;
        if ( !is_null( $this->_valid ) ) {
            // If already validated then return the validation status
            if ( $this->_valid ) {
                $isValid = true;
            } else {
                $isValid = false;
            }
        } else {
            // Otherwise validate and loop the function
            $this->validate();
            $isValid = $this->isValid();
        }
        return $isValid;
    }
    /**
     * Think about changing this so it returns validated types
     * Aim is to return value to insert into database
     * @return mixed
     */
    public function getValue() {
        return $this->validateType();
    }
    /**
     * Returns value inputted regardless
     * @return mixed
     */
    public function getRawValue() {
        return $this->_value;
    }
    public function getVarType() {
        return $this->_varType;
    }
    /**
     * Returns the error message, if set
     * @return string
     */
    public function getError() {
        return $this->_error;
    }
    protected function generateError( $rule ) {
        switch( $this->getVarType() ) {
            case 'date':
                $limits = explode( '/', $rule['value'] );
                switch( $rule['special'] ) {
                    case 'RELATIVE':
                        switch( $rule['operator'] ) {
                            case 'BETWEEN':
                                $this->_error = "The date must be between {$limits[0]} and {$limits[1]} years ago.";
                                break;
                            case 'BEFORE':
                                $this->_error = "The date must be more than {$limits[0]} years ago.";
                                break;
                            case 'AFTER':
                                $this->_error = "The date must be within the last {$limits[0]} years.";
                                break;
                        }
                        break;
                    case 'ABSOLUTE':
                        switch( $rule['operator'] ) {
                            case 'BETWEEN':
                                $this->_error = "The date must be between {$limits[0]} and {$limits[1]}.";
                                break;
                            case 'BEFORE':
                                $this->_error = "The date must be before {$limits[0]}.";
                                break;
                            case 'AFTER':
                                $this->_error = "The date must be after {$limits[0]}.";
                                break;
                        }
                        break;
                }
                break;
            case 'time':
                break;
            case 'number':
                $sql = "SELECT unit FROM units WHERE number = ? AND conversion = ?";
                $pA = array('sd', $this->_fieldName, $this->_unit );
                $unitSearch = DB::query($sql, $pA);
                $unit = $unitSearch->unit;
            case 'duration':
                if ( !isset( $unit ) ) {
                    $unit = 'minutes';
                }
                switch( $rule['special'] ) {
                    case 'HARD':
                    default:
                        $limits = explode( '/', $rule['value'] );
                        $limits = array_map(function($el){return round($el / $this->_unit,2,PHP_ROUND_HALF_UP);},$limits);
                        switch( $rule['operator'] ) {
                            case 'BETWEEN':
                                $this->_error = "Please enter a value between {$limits[0]} and {$limits[1]} {$unitSearch->unit}.";
                                break;
                            case 'AFTER':
                                $this->_error = "Please enter a value greater than {$limits[0]} {$unitSearch->unit}.";
                                break;
                            case 'BEFORE':
                                $this->_error = "Please enter a value less than {$limits[0]} {$unitSearch->unit}.";
                                break;
                        }                      
                        break;
                        
                }
                break;
            case 'text':
            case 'textarea':
            case 'email':
                $getErrorText = "You have entered an invalid character, please try again.";
                break;
            case 'password':
                $getErrorText = "The current password must be correct and the new passwords must match and be over 8 characters.";
                break;
       }
    }
    /**
     * Is the passed value the correct type?  If not then it must be invalid
     * return mixed Variable validates and ready to test
     */
    protected function validateType() {
        $testVal = NULL;
        $value = $this->_value;
        switch( $this->_varType ) {
            case 'number':
                // Must be an array, must be numeric
                if ( !is_array($this->_value) || 
                                !isset($this->_value['number']) ||
                                !is_numeric($this->_value['number']) ) {
                    $this->_valid = false;
                    $this->_error = 'You must enter a number here.';
                } else {
                    // Check if a unit is set otherwise, make it 1
                    if ( isset( $this->_value['unit'] ) &&
                            is_numeric( $this->_value['unit'] )) {
                        $this->_unit = $this->_value['unit'];
                    } else {
                        $this->_unit = 1;
                    }
                    // Generate the test value by multiplying by the unit
                    $testVal = $this->_value['number'] * $this->_unit;
                }
                break;
            case 'date':
                // Must be a valid date, the array much have all parts and be numeric
                // This is to ensure no errors from checkdate
                $testDate = false;
                $dateVals = array( 'day', 'month', 'year' );
				if ( is_array( $value ) ) {
					$testDate = true;
					foreach( $dateVals as $dV ) {
						if ( !isset( $value[ $dV ] ) ) {
							$testDate = false;
						} else if ( !is_numeric( $value[ $dV ] ) ) {
							$testDate = false;
						}
					}
				}
				if ( $testDate && checkdate( $value['month'], $value['day'], $value['year'] ) ) {
                    // If testdate and checkdate are true
                    // Date in ISO format
                    $testVal = "{$value['year']}-{$value['month']}-{$value['day']}";
                } else {                    
                    $this->_valid = false;
                    $this->_error = 'Please enter a valid date.';
                }
                break;
            case 'time':
                // Must be a valid time with all parts and be numeric
                $testTime = false;
                $timeVals = array( 'hour', 'minute' );
				if ( is_array( $value ) ) { // Ensure input set properly
					$testTime = true;
					foreach ( $timeVals as $tV ) {
						if ( !isset( $value[ $tV ] ) ) {
							$testTime = false;
						} else if ( !is_numeric( $value[ $tV ] ) ) {
							$testTime = false;
						}
					}
				}
				if ( $testTime && 0 <= $value['hour'] && $value['hour'] < 24 && 0 <= $value['minute'] && $value['minute'] < 60 ) {
                    $testVal = "{$value['hour']}:{$value['minute']}:00";
                } else {                    
                    $this->_valid = false;
                    $this->_error = 'Please enter a valid time.';
                }
                break;
            case 'duration':
                $testTime = false;
				$timeVals = array( 'hours', 'minutes' );
				if ( is_array( $value ) ) { // Ensure input set properly
					$testTime = true;
                    $partTime = '';
					foreach ( $timeVals as $tV ) {
						if ( !isset( $value[ $tV ]['number'] ) || $value[ $tV ]['number'] === "" ) {
							$partTime .= $tV;
						} else if ( !is_numeric( $value[ $tV ][ 'number' ] ) ) {
							$testTime = false;
						}
					}
                    if ( $partTime == 'hoursminutes')
                        $testTime = false;
                    else if ( $partTime != '' )
                        $value[$partTime]['number'] = 0;
				}
				if ( $testTime )
                    // Convert into minutes
					$testVal = $value['minutes']['number'] + $value['hours']['number'] * 60;
                else {
                    $this->_valid = false;
                    $this->_error = 'Please enter a valid duration.';
                }
                break;
            case 'yesno':
                // Can be completely validated at this point
                if ( $value == '1' || $value == '0' ) {
                    $testVal = $value;
                    $this->_valid = true;
                } else {
                    $this->_valid = false;
                    $this->_error = 'Please select Yes or No.';
                }
                break;
            case 'checkbox':
                // Must be an array of integers, if not the someone has changed the input value
                if (is_array($value)) {
                    foreach( $value as $number ) {
                        if ( !is_numeric( $number ) ) {
                            $this->_valid = false;
                            $this->_error = 'An error has occurred please try again.';
                            break 2;
                        }
                    }
                    $testVal = $value;
                } else {
                    $this->_valid = false;
                    $this->_error = 'An error has occurred please try again.'; 
                }
                break;
            case 'select':
            case 'text':
            case 'textarea':
            case 'radio':
            case 'email':
                // No type checking available for these
                $testVal = $value;
                break;            
            case 'password':
                $pattern = '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/';
                $checkValue = (array)$value;
                foreach( $checkValue as $pwd ) {
                    if (preg_match($pattern, $pwd)) {                      
                        if(strpos($pwd,$this->_data->username) !== false){
                            $this->_valid = false;
                            $this->_error = 'Your password cannot contain your username.';
                            break 2;
                        }
                        $length = strlen($pwd);
                        for( $i = 0; $i < $length - 2; $i++ ) {
                            if ( ord(substr($pwd,$i,1)) == ord(substr($pwd,$i+1,1)) && ord(substr($pwd,$i+1,1)) == ord(substr($pwd,$i+2,1))) {
                                $this->_valid = false;
                                $this->_error = 'You cannot have three consecutive characters in your password.';
                                break 3;
                            } elseif ( ord(substr($pwd,$i,1)) + 1 == ord(substr($pwd,$i+1,1)) && ord(substr($pwd,$i+1,1)) + 1 == ord(substr($pwd,$i+2,1)) ) {
                                $this->_valid = false;
                                $this->_error = 'You cannot have three sequential characters in your password.';
                                break 3;
                            }
                        }
                    } else if ( $pwd !== '' ) {
                        $this->_valid = false;
                        $this->_error = 'The password must be at least 8 characters and contain upper case, lower case and numbers.';
                        break 2;
                    }
                }
                $testVal = $value;
                break;  
        }
        return $testVal;
    }
    /**
     * 
     * @param mixed $value
     * @param array $limits
     * @param string $operator
     * @return boolean
     */
    protected function doCompare($value, $limits, $operator) {
        $doCompare = true;
        if ( isset( $limits[0] ) && !is_null( $limits[0] ) ) {
            switch( $operator ) {
                case 'BETWEEN':
                    if ( isset( $limits[1]) && !is_null($limits[1]) && 
                        ( $limits[0] > $value || $value > $limits[1] ) )
                            $doCompare = false;
                    break;
                case 'AFTER':
                    if ( $limits[0] > $value )
                        $doCompare = false;
                    break;
                case 'BEFORE':
                    if ( $limits[0] < $value )
                        $doCompare = false;
                    break;
                case 'AGE':
                    if ( abs($limits[0]) > abs($value) ) {
                        $age = $value;
                        $date = $limits[0];
                    } else {
                        $age = $limits[0];
                        $date = $value;
                    }
                    
                    $dateCheck = new DateTime();
                    $dateCheck->setTimestamp($date);
                    $now = new DateTime();
                    $now->setTimestamp($limits[1]);
                    $interval = $dateCheck->diff($now);
                    if ( $age != $interval->y )
                        $doCompare = false;
                    break;
				case 'TODAY':
					$today = date('Y-m-d');
					$new = new DateTime($today);
					$dateCheck = new DateTime();
					$dateCheck->setTimeStamp($value);
					if ( $new != $dateCheck ) {
						$doCompare = false;
					}
					break;
            }
        }
        return $doCompare;
    }
    /**
     * General Validation method - called from within
     */
    protected function validate() {
        $testVal = $this->validateType();      // First check that the type of value is right
        $this->_valid = true;
        while( !is_null($testVal) && $this->_valid !== false && $group = $this->_valRules->nextGroup() ) {
            $groupVal = true;
            // If not already validated and there is a group to validate
            $groupType = $group->getType();   // Is the group and AND or an OR?
            while( $rule = $group->nextRule() ) {
                $ruleVal = true;
                if( is_null( $rule['value']) ) continue; // Skip if nothing to validate against
                $limits = explode( '/', $rule['value'] );
                switch ( $this->_varType ) {
                    case 'number':
                    case 'duration':
                        // Only care about HARD validation for server
                        if( $rule['special'] == 'HARD' || is_null( $rule['special'] ) ) {                            
                            $ruleVal = $this->doCompare($testVal, $limits, $rule['operator']);
                        } elseif ( $rule['special'] == 'REFERENCE' ) {
                            foreach( $limits as $key => $value ) {
                                $numberLoc = explode( '-', $value );
                                $numberCheck = $this->_data->getField( $numberLoc[0], $numberLoc[1] );
                                if ( $numberCheck ) {
                                    $numberLimits[ $key ] = $rule['operator'] == 'AGE' ? strtotime($numberCheck) : $numberCheck;
                                } else {
                                    break 2;
                                }
                            }
                            $ruleVal = $this->doCompare( $testVal, $numberLimits, $rule['operator'] );
                        }
                        break;
                    case 'date':
                        $dateToTest = strtotime( $testVal );
                        if( $rule['special'] == 'RELATIVE' ) {
                            // Dates relative to current date                            
                            foreach( $limits as $key => $value ) {
								if ( is_integer( $value ) ) {
									$dateLimits[$key] = strtotime( "{$value} years" );
								} else {
									$dateLimits[$key] = strtotime( round($value*365) . " days");
								}
							}
                            $ruleVal = $this->doCompare($dateToTest, $dateLimits, $rule['operator']);
                        } else if ( $rule['special'] == 'ABSOLUTE' ) {
                            foreach( $limits as $key => $value ) 
                                $dateLimits[$key] = strtotime( $value );                                                      
                            $ruleVal = $this->doCompare($dateToTest, $dateLimits, $rule['operator']);
                        } else if ( $rule['special'] == 'REFERENCE' ) {
                            foreach( $limits as $key => $value ) {
                                $dateLoc = explode( '-', $value );                        
                                $dateCheck = $this->_data->getField( $dateLoc[0], $dateLoc[1] );                                
                                if ( $dateCheck ) {
                                    $dateLimits[$key] = is_numeric( $dateCheck ) ? $dateCheck : strtotime($dateCheck);
                                } else {                                    
                                    // One of the reference dates is missing so break without testing
                                    break 2;
                                }                                  
                            }
                            $ruleVal = $this->doCompare($dateToTest, $dateLimits, $rule['operator']);
                        }
                        break;
                    case 'time':
                        if ( $rule['special'] == 'REFERENCE' ) {
                            // Then we need to get the datetime
                            $testDate = $this->_data->getField( $this->_page, str_replace('time', 'date', $this->_fieldName) );
                            if ( $testDate ) {
                                $dateTimeToTest = strtotime("{$testDate} {$testVal}");
                                foreach( $limits as $key => $value ) {
                                    $dateLoc = explode( '-', $value );
                                    $checkDate = $this->_data->getField( $dateLoc[0], $dateLoc[1] . 'date');
                                    $checkTime = $this->_data->getField( $dateLoc[0], $dateLoc[1] . 'time');
                                    if ( $checkDate )
                                        $dateTimeLimits[$key] = strtotime("{$checkDate} {$checkTime}");
                                    else
                                        $dateTimeLimits[$key] = NULL;
                                }
                                $ruleVal = $this->doCompare($dateTimeToTest, $dateTimeLimits, $rule['operator']);
                            }
                        }
                        break;
                    case 'text':
                    case 'textarea':
                    case 'email':
                        $check = preg_match( $rule['value'], $testVal );                   
                        switch ( $rule['operator'] ) {
                            case 'EQUAL':
                                if ( !$check ) {
                                    $ruleVal = false;
                                }
                                break;
                            case 'NOT EQUAL':
                                if ( $check ) {
                                    $ruleVal = false;
                                }
                                break;
                        }
                        break;
                    case 'select':
                    case 'radio':
                        if ( $rule['operator'] == 'IN LIST' && $rule['special'] != 'FILTER' ) {
                            $checkTable = DB::clean($rule['value']);
                            if ( strpos($checkTable,'-') ) {
                                $filterBy = explode('-',$checkTable);
                                $checkTable = $filterBy[0];
                                $filterTable = $filterBy[1];
                            } else {
                                $filterTable = NULL;
                            }
                            $sql = "SELECT DISTINCT({$checkTable}.option_value) FROM {$checkTable}";
                            if ( $filterTable ) {
                                $sql .= " RIGHT JOIN {$filterTable}
                                        ON {$checkTable}.id = {$filterTable}.{$checkTable}_id";
                            }
                            $result = DB::query($sql);
                            $idList = $result->getArray('option_value');
                            if (!in_array($testVal, $idList)) {
                                $ruleVal = false;
                            }
                        } else if ( $rule['operator'] === 'NOT IN LIST' ) {
                            $excList = explode(',',$rule['value']);
                            if ( in_array($testVal, $excList ) ) {
                                $ruleVal = false;
                            }
                        } else {
                            if ( $rule['special'] == 'REFERENCE' ) {
                                $valArr = explode('-',$rule['value']);
                                if ( $valArr[0] == 'user' ) {
                                    $valNum = $_SESSION['user']->get($valArr[1]);
                                }
                                if ( $valNum > $testVal ) {
                                    $ruleVal = false;
                                }
                            }
                        }
                        break;
                    case 'checkbox':
                        $checkTable = DB::clean($rule['value']);
                        $sql = "SELECT option_value FROM {$checkTable}";
                        $result = DB::query($sql);
                        $idList = $result->getArray('option_value');
                        if ( in_array( 0, $testVal ) ) { // 0 represents the test not being done, so can be the only thing in the array
                            $validated = array( 0 );
                        } elseif ( in_array( 1, $testVal ) ) { // 1 always represents 'None of the above', so can be the only thing in the array
                            $validated = array( 1 );
                        } else {
                            foreach( $testVal as $v ) {
                                if ( in_array( $v, $idList ) ) {
                                    $validated[] = $v;				
                                }
                            }
                        }
                        $value = $validated; // Replace the array with the validated one
                        break;
                    case 'password':
                        if ( $rule['value'] == 'newPassword' ) {
                            // If it's a new password for registration
                            if ( is_array( $testVal ) && !emptyInput( $testVal[0] )
                                    && $testVal[0] == $testVal[1] ) {
                                // Ensure an array has been sent, it isn't empty and the passwords match
                                $this->_value = $testVal[1];
                            } else {
                                $ruleVal = false;
                            }
                        } else {
                            $checkPass = $this->_data->checkPassword( $testVal[0] );
                            if ( $checkPass && !emptyInput( $testVal[1] ) ) {
                                if ( $testVal[1] == $testVal[2] && $testVal[0] != $testVal[1] ) {
                                    $this->_value = $testVal[1];
                                } else {
                                    $ruleVal = false;
                                }
                            }  else if ( $checkPass ) {
                                $this->_value = $testVal[0];
                            } else {
                                $ruleVal = false;
                            }
                        }
                        break;
                  
                }
                if ( $ruleVal === false ) {
                    if ( !is_null( $rule['error'] ) ) {
                        $this->_error = $rule['error'];
                    } else {
                        $this->generateError( $rule );
                    }
                }
                if ( $groupType == 'AND' && $ruleVal === false ) {
                    // Only takes one false result to invalidate an AND
                    $groupVal = false;
                    break;
                } else if ( $groupType == 'OR' && $ruleVal === true ) {
                    // Only takes one true to validate an OR
                    $groupVal = true;
                    break;
                }
            }
            if ( $groupVal == false ) {
                // If any group is not valid then set var invalid and stop 
                // testing (as valid being false busts out of the while)
                $this->_valid = false;
            }
        }
    }
}

?>
