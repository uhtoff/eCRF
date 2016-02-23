<?php
class HTMLInput extends HTMLObject {
	protected $type;
	protected $name;
	protected $value;
	protected $options;
	protected $pH;
	protected $currSize;
	protected $mandatory;
	protected $counter = 1;
	protected $attribs = array();
	protected $language = 'en';
	public function __construct( $type, $name, $value = NULL, $language = NULL ) { // All inputs have to at least have a name, type and value though the value could be null
		$this->addName( $name );
		if ( $language ) $this->language = $language;
		$this->addType( $type );
		if ( !is_null($value) ) {
			$this->addValue( $value );
		}
	}
	public function addType( $value ) { // Type of entry, capture special types in here
		$this->type = $value;
		switch ( $value ) {
			case 'yesno': // Simple yes/no radio choice
				$this->type = 'radio';
				$sql = "SELECT option_value, option_text FROM yesno WHERE language_code = 'en' ORDER BY option_order";
				$result = DB::query($sql);
				if ( $this->language != 'en' ) {
					$sqlLang = "SELECT option_value, option_text
							FROM yesno
							WHERE language_code = ?";
					$pALang = array( 's', $this->language );
					$resultLang = DB::query( $sqlLang, $pALang, NULL, NULL, 'option_value' );
				}
				foreach ( $result->rows as $row ) {
					if ( isset($resultLang->rows[$row->option_value] ) ) {
						$row = $resultLang->rows[$row->option_value];
					}
					$this->addOption( $row->option_text, $row->option_value );
				}
				// $this->addOption( array( 1 => 'Yes', 0 => 'No' ), true );
				break;
			case 'date': // Standard date entry, consisting of 3 text input objects
				$this->subInput['day'] = new HTMLInput( 'numberInput', $this->getName() . '[day]' );
				$this->subInput['day']->addID( $this->getName() );
				$this->subInput['day']->addPH( 'dd' );
				$this->subInput['day']->setSize( 'input-mini' );
				$this->subInput['day']->addAttrib('min','1');
				$this->subInput['day']->addAttrib('max','31');
				$this->subInput['month'] = new HTMLInput( 'numberInput', $this->getName(). '[month]' );
				$this->subInput['month']->addPH( 'mm' );
				$this->subInput['month']->setSize( 'input-mini' );
				$this->subInput['month']->addAttrib('min','1');
				$this->subInput['month']->addAttrib('max','12');
				$this->subInput['year'] = new HTMLInput('numberInput', $this->getName(). '[year]' );
				$this->subInput['year']->addPH( 'yyyy' );
				$this->subInput['year']->setSize( 'input-mini' );
				$this->subInput['year']->addAttrib('min','1800');
				$this->subInput['year']->addAttrib('max','2020');
				$this->addHelp( '(dd/mm/yyyy)' );
				break;
			case 'time': // Standard time entry consisting of 2 text input objects
				$this->subInput['hour'] = new HTMLInput( 'numberInput', $this->getName(). '[hour]' );
				$this->subInput['hour']->addID( $this->getName() );
				$this->subInput['hour']->addPH( 'hh' );
				$this->subInput['hour']->setSize( 'input-mini' );
				$this->subInput['hour']->addAttrib('min','0');
				$this->subInput['hour']->addAttrib('max','24');
				$this->subInput['minute'] = new HTMLInput('numberInput', $this->getName(). '[minute]' );
				$this->subInput['minute']->addPH( 'mm' );
				$this->subInput['minute']->setSize( 'input-mini' );
				$this->subInput['minute']->addAttrib('min','0');
				$this->subInput['minute']->addAttrib('max','60');
				$this->addHelp( '(hh:mm)' );
				break;
			case 'number': // Mix of number and units
				$this->subInput['number'] = new HTMLInput( 'numberInput', $this->getName(). '[number]' );
				$this->subInput['number']->addID( $this->getName() );
				$this->subInput['number']->setSize( 'input-mini' );
				$this->subInput['unit'] = new HTMLInput( 'select', $this->getName(). '[unit]' );
				$this->subInput['unit']->setSize( 'input-small' );				
				break;
			case 'duration': // hh:mm format, stored in minutes in db
				$sI = $this->subInput['hours'] = new HTMLInput( 'number', $this->getName(). '[hours]' );
				$sI->addID( $this->getName() );
				$sI->addUnit( 'hours' );
				$sI = $this->subInput['minutes'] = new HTMLInput( 'number', $this->getName(). '[minutes]' );
				$sI->addUnit( 'minutes' );
				break;
			case 'select':
				$this->setSize( 'input-xlarge' );
				break;
			case 'checkbox':
				$this->addValue = array();
				break;
			case 'confirmation':
				$this->addOption('I confirm the statement', true);
				break;
			default:
				$this->setSize( 'input-medium' );
				break;
		}
	}
	public function getType() {
		return $this->type;
	}
	public function setMand() {
		$this->mandatory = 1;
		$this->addClass( 'mandatory' );
		if ( isset( $this->subInput ) ) {
			foreach( $this->subInput as $sub ) {
				$sub->setMand();
			}
		}
	}
	public function isMand() {
		return $this->mandatory;
	}
	public function addName( $name ) { // Inputs need an id to link with a label, if no other ID is set then their ID = their name
		$this->name = $name;
		if ( empty( $this->id ) ) {
			$this->addID( $name );
		}
	}
	public function getName() {
		if ( $this->name ) {
			return $this->name;
		} else {
			return false;
		}
	}
	public function writeName() {
		if ( $this->name ) {
			$html = "name=\"{$this->clean( $this->name )}";
			if ( $this->type == 'checkbox' ) {
				$html .= "[]";
			}
			$html .= "\"";
			return $html;
		}
	}
	public function addClass( $class, $propagate = false ) {
        if ( $class === 'disabled' ) {
            $this->disableInput();
        } elseif ( $class === 'readonly' ) {
            $this->makeReadOnly();
        } else {
            if ( $propagate && isset( $this->subInput ) ) {
                foreach( $this->subInput as $input ) {
                    $input->addClass( $class, true );
                }
            }
            parent::addClass( $class );
        }
	}
	public function addValue( $value ) {
		if ( !is_null( $value ) ) {
		switch ( $this->type ) {
			case 'checkbox':
				if ( is_array( $value ) ) {
					if ( is_array( $this->value ) ) {
						$this->value = array_merge( $this->value, $value );
					} else {
						$this->value = $value;
					}
				} else {
					$this->value[] = $value;
				}
				break;
			case 'date': // Presumes an ISO date
				$this->value = $value;
				if ( $value == 'today' ) {
					$value = date( 'Y-m-d' );
				}
				$split = explode( '-', $value );
				if ( isset( $split[2] ) ) {
					$this->subInput['day']->addValue( $split[2] );
					$this->subInput['month']->addValue( $split[1] );
					$this->subInput['year']->addValue( $split[0] );
				}
				break;
			case 'time': // Presumes an hh:mm
				$this->value = $value;
				$split = explode( ':', $value );
				$this->subInput['minute']->addValue( $split[1] );
				$this->subInput['hour']->addValue( $split[0] );
				break;
			case 'number': // Mix of value and units
				$this->value = $value;
				$unitConv = $this->subInput['unit']->getValue();
				if ( !$unitConv ) {
					$unitConv = 1;
				}
                $value = round( $value / $unitConv, 2);
				$this->subInput['number']->addValue( $value );
				break;
			case 'duration': // Special type of number value added in minutes
				$this->value = $value;
				$hours = (int)($value/60);
				$minutes = $value % 60;
				$this->subInput['hours']->addValue($hours);
				$this->subInput['minutes']->addValue($minutes);
				break;
			case 'password': // No adding default values for password fields
				break;
            case 'radio':
                $this->value = (int)$value;
                break;
			default:
				if ( !is_array( $value ) ) {
					$this->value = $value;
				}
				break;
		}
		}
	}
	public function removeValue( $v = NULL ) {
		if ( $this->type == 'checkbox' ) {
			if ( $v ) {
				$index = array_search( $v, $this->value );
				unset( $this->value[ $index ] );
			} else {
				unset( $this->value );
			}
		} else {
			unset( $this->value );
		}
	}
	public function writeValue() {
		if ( !is_null($this->value) ) {
			$html = "value=\"{$this->clean( $this->value )}\"";
			return $html;
		}
	}
	public function getValue() {
		$getValue = NULL;
		if ( isset( $this->value ) ) {
			$getValue = $this->value;
		}
		return $getValue;
	}
	public function setSize( $class ) {
		if ( $this->currSize ) {
			$this->removeClass( $this->currSize );
		}
		$this->currSize = $class;
		$this->addClass( $class );
	}
	public function addUnit( $options, $value = 1 ) {
		$this->subInput['unit']->addOption( $options, $value );
        $this->subInput['unit']->addValue(1);
	}
	public function addPH( $v ) {
		$this->pH = $v;
	}
	public function writePH() {
		if ( $this->pH ) {
			$html = "placeholder=\"{$this->clean( $this->pH )}\"";
			return $html;
		}
	}
	public function writeID() { // Produce html ID, checkboxes and radio need addition to ensure uniqueness
		if ( ( $this->type == 'checkbox' || $this->type == 'radio' ) && $this->id ) {
			$oldid = $this->id;
			$this->id .= "_{$this->counter}";
			$this->counter++;
			$html = parent::writeID();
			$this->id = $oldid;
		} else {
			$html = parent::writeID();
		}
		return $html;
	}
	public function addOption( $options, $value = NULL ) { // Can add an option, or an array of options and values, $value == true if to keep array keys of $options
		if ( is_array( $options ) ) {
			foreach( $options as $k => $v ) {
				if ( is_array( $value ) ) {
					$this->options[ $value[ $k ] ] = $v;
				} else if ( $value === true ) {
					$this->options[ $k ] = $v;
				} else {
					$this->options[] = $v;
				}
			}
		} else {
			if ( !is_null( $value ) ) {
				$this->options[ $value ] = $options;
			} else {
				$this->options[] = $options;
			}
		}
	}
	public function numOptions() {
		if ( is_array( $this->options ) ) {
			return count( $this->options );
		} elseif ( is_null( $this->options ) ) {
            return 0;
        } else {
			return 1;
		}
	}
	public function addLabel( $text ) {
		$this->labelText = $text;
	}
	public function writeLabel() { // Writes label for form control
		$html = false;
		if ( isset( $this->labelText ) ) {			
			if ( $this->type == 'heading' ) {
                $html = "<label class=\"strong control-label ";
                if ( $this->hasClass( 'centre' ) ) $html .= "text-center";
                $html .= "\">{$this->labelText}</label>";	
			} else {
                // Writes HTML for labelText
                $label = new HTMLLabel( $this->labelText, $this->id ); // instantiate at this point to ensure id available
                $label->addClass( 'control-label' );
                $html = $label->writeHTML();
            }			
		}
		return $html;
	}
	public function setError( $error, $errorText = NULL ) {
		$this->error = $error;
		if ( $error == 'warning' ) {
			$this->addHelp( 'This is a mandatory field.' );
		} else if ( $error == 'error' ) {
//			if ( $errorType ) {
                $this->addHelp( $errorText );
//            } else {
//                $errorText = $this->getErrorText( $errorType );
//                $this->addHelp( $errorText );
//            }
		} else if ( $error == 'error_value' ) {
			$this->error = 'error';
			$this->addHelp( 'The entered value was invalid and can\'t replace the current value.' );
		}
	}
	public function getError() {
		$getError = false;
		if ( $this->hasError() ) {
			$getError = $this->error;
		}	
		return $getError;
	}
//	public function getErrorText( $errorType = NULL ) {
//		$getErrorText = NULL;
//		$name = $this->getName();
//		$split = explode( '-', $name );
//		$sql = "SELECT type, rule1, rule2, error1, error2, unit FROM formFields 
//            LEFT JOIN units ON fieldName = number 
//            WHERE pages_name = ? AND fieldName = ?";
//		$pA = array( 'ss', $split[0], $split[1] );
//        $result = DB::query( $sql, $pA );
//		if ( $result->getRows() ) {
//			switch( $result->type ) {
//				case 'date':
//                    if( $errorType == 2 ) {
//                        $getErrorText = $result->error2;
//                    } else {
//                        $minmax = explode( '/', $result->rule1 );
//                        $getErrorText = "Please enter a valid date between {$minmax[0]} and {$minmax[1]} years ago.";
//                    }
//					break;
//				case 'time':
//					if( $errorType == 2 ) {
//                        $getErrorText = $result->error2;
//                    } else {
//                        $minmax = explode( '/', $result->rule1 );
//                        $getErrorText = "Please enter a valid time using the 24-hour clock.";
//                    }
//					break;
//				case 'number':
//					$minmax = explode( '/', $result->rule1 );
//                    $getErrorText = "Please enter a number between {$minmax[0]} and {$minmax[1]} {$result->unit}.";
//					break;
//				case 'duration':
//					$minmax = explode( '/', $result->rule1 );
//					$getErrorText = "Please enter a value between {$minmax[0]} and {$minmax[1]} minutes.";
//					break;
//				case 'text':
//				case 'textarea':
//					$getErrorText = "This value cannot be accepted, please try again.";
//					break;
//				case 'password':
//					$getErrorText = "The current password must be correct and the new passwords must match and be over 8 characters.";
//					break;
//			}
//		}
//		return $getErrorText;
//	}
	public function hasError() {
		$hasError = false;
		if ( isset( $this->error ) ) {
			$hasError = true;
		}
		return $hasError;
	}
	public function addHelp( $text ) {
		$this->helpText = $text;
	}
	public function showHelp() {
		$showHelp = false;
		if ( isset( $this->helpText ) ) {
			$showHelp = true;
		}
		return $showHelp;
	}
	public function isHidden() {
		$isHidden = false;
		if ( $this->getType() == 'hidden' || $this->getType() == 'data' ) {
			$isHidden = true;
		}
		return $isHidden;
	}
	public function writeHelp() {
		$html = "<span class=\"help-inline";
        if ( $this->hasError() ) $html .= " error";
        $html .= "\">{$this->clean( $this->helpText )}</span>";
		return $html;
	}
	public function disableInput() {
		$this->disabled = true;
		if ( isset( $this->subInput ) ) {
			foreach( $this->subInput as $input ) {
				$input->disableInput();
			}
		}
	}
    public function makeReadOnly() {
        $this->readOnly = true;
        if ( isset( $this->subInput ) ) {
			foreach( $this->subInput as $input ) {
				$input->makeReadOnly();
			}
		}
    }
	public function writeState() {
        $states = '';
		if ( isset( $this->disabled ) ) {
			$states .= 'disabled = disabled ';
		}
        if ( isset( $this->readOnly ) ) {
            $states .= 'readonly = readonly ';
        }
        return $states;
	}
	public function addAttrib($name, $value, $propagate = false )
	{
		$this->attribs[$name] = $value;
		if ( $propagate && isset( $this->subInput ) ) {
			foreach( $this->subInput as $input ) {
				$input->addAttrib( $name, $value, true );
			}
		}
	}
	public function writeAttribs()
	{
		$html = '';
		foreach( $this->attribs as $attrib => $value ) {
			$html .= "{$attrib}={$value} ";
		}
		return $html;
	}		
	public function writeControl() { // Writes form control itself
		$html = "\n";
		switch( $this->type ) {
			case 'text':
				$html = "<input type=\"text\" {$this->writeID()} {$this->writeName()} {$this->writeClasses()} {$this->writeValue()} {$this->writePH()} {$this->writeState()} {$this->writeAttribs()}/>";
				break;
			case 'numberInput':
				$html = "<input type=\"number\" {$this->writeID()} {$this->writeName()} {$this->writeClasses()} {$this->writeValue()} {$this->writePH()} {$this->writeState()} {$this->writeAttribs()}/>";
				break;
			case 'password':
				$html = "<input type=\"password\" {$this->writeID()} {$this->writeName()} {$this->writeClasses()} {$this->writeValue()} {$this->writePH()} {$this->writeState()} {$this->writeAttribs()}/>";
				break;
            case 'email':
				$html = "<input type=\"email\" {$this->writeID()} {$this->writeName()} {$this->writeClasses()} {$this->writeValue()} {$this->writePH()} {$this->writeState()} {$this->writeAttribs()}/>";
				break;
            case 'file':
				$html = "<input type=\"file\" {$this->writeID()} {$this->writeName()} {$this->writeClasses()} {$this->writeValue()} {$this->writePH()} {$this->writeState()} {$this->writeAttribs()}/>";
				break;
			case 'hidden':
			case 'data':
				$html = "<input type=\"hidden\" {$this->writeID()} {$this->writeName()} {$this->writeClasses()} {$this->writeValue()} {$this->writeAttribs()}/>";
				break;
			case 'textarea':
				$html = "<textarea rows=\"3\" {$this->writeID()} {$this->writeName()} {$this->writeClasses()} {$this->writeState()} {$this->writeAttribs()}>{$this->clean( $this->value )}</textarea>";
				break;
			case 'select':
				if ( $this->numOptions() > 1 ) {
					$html = "<select {$this->writeID()} {$this->writeName()} {$this->writeClasses()} {$this->writeState()} {$this->writeAttribs()}>";
					if ( !isset( $this->options[0] ) && !strpos( $this->getName(), '[unit]' ) ) { // Select an option if no zero option set and not a unit field
						$html .= "\n\t";
						$html .= "<option value=\"\">";
						$html .= "Select an option";
						$html .= "</option>";
					}
					foreach( $this->options as $v => $o ) {
						$html .= "\n\t";
						$html .= "<option value=\"{$this->clean($v)}\"";
						if ( $this->value == $v ) {
							$html .= " selected=\"selected\"";
						}
						$html .= ">{$this->clean($o)}</option>";
					}
					$html .= "\n</select>";
				} else {
					foreach( $this->options as $v => $o ) {
						$html = "<input type=\"hidden\" {$this->writeID()} {$this->writeName()} {$this->writeClasses()} {$this->writeAttribs()} value=\"{$this->clean($v)}\"/>";
						$html .= $this->clean($o);
					}
				}
				break;
			case 'radio':
				foreach( $this->options as $v => $o ) {
					$html .= "\n";
					$html .= "<label class=\"radio inline\">";
					$html .= "\n\t";
					$html .= "<input type=\"radio\" {$this->writeID()} {$this->writeName()} {$this->writeClasses()} {$this->writeAttribs()} value=\"{$this->clean($v)}\" ";
                    if ( $v === $this->value ) {
						$html .= "checked=\"checked\" ";
					}
					$html .= "/ {$this->writeState()}>{$this->clean($o)}";
					$html .= "\n";
					$html .= "</label>&nbsp;&nbsp;&nbsp;";
				}
				break;
			case 'checkbox':
			case 'confirmation':
				foreach( $this->options as $v => $o ) {
					$html .= "\n";
					$html .= "<label class=\"checkbox\">";
					$html .= "\n\t";
					$html .= "<input type=\"checkbox\" {$this->writeID()} {$this->writeName()} {$this->writeClasses()} {$this->writeAttribs()} value=\"{$this->clean($v)}\" ";
					if ( is_array( $this->value ) && in_array( $v, $this->value ) ) {
						$html .= "checked=\"checked\" ";
					}
					$html .= " {$this->writeState()}/>{$this->clean($o)}";
					$html .= "\n";
					$html .= "</label>";
				}
				break;
			case 'date':
				$html .= $this->subInput['day']->writeControl();
				$html .= ' / ';
				$html .= $this->subInput['month']->writeControl();
				$html .= ' / ';
				$html .= $this->subInput['year']->writeControl();
				break;
			case 'time':
				$html .= $this->subInput['hour']->writeControl();
				$html .= ' : ';
				$html .= $this->subInput['minute']->writeControl();
				break;
			case 'number':
				$html .= "\n";
				if ( $this->subInput['unit']->numOptions() == 1 ) {
                    if (!is_null(reset($this->subInput['unit']->options))){
                        $html .= "<div class=\"input-append\">";
                        $html .= "\n\t";
                        $html .= $this->subInput['number']->writeControl();
                        $html .= "\n\t";
                        $html .= "<span class=\"add-on\">";
                        $html .= $this->subInput['unit']->writeControl();
                        $html .= "</span>";
                        $html .= "</div>";
                    } else {
                        $html .= $this->subInput['number']->writeControl();
                        $html .= $this->subInput['unit']->writeControl();
                    }
				} elseif ( $this->subInput['unit']->numOptions() > 1 ) {
					$html .= $this->subInput['number']->writeControl();
					$html .= "\n";
					$html .= $this->subInput['unit']->writeControl();
				} else {
                    $html .= $this->subInput['number']->writeControl();
                }
				break;
			case 'duration':
				$html .= $this->subInput['hours']->writeControl();
				$html .= $this->subInput['minutes']->writeControl();
				break;
			case 'recaptcha':
//				$path = $_SERVER['DOCUMENT_ROOT'];
//				require_once( $path . '/addons/recaptchalib.php');
//				$publickey = "6Lc_XusSAAAAABTg7LnvK0KkCMqcJslS1WSnUG9f"; // you got this from the signup page
				$html .= '<div class="g-recaptcha" data-sitekey="6LcEFQ8TAAAAAAh13DbGyLt_IGk_VFvyQu_DLeJE"></div>';
				break;
            case 'multiple':
                $html .= "<button {$this->writeName()} class=\"btn addMultiple\">Add</button>";
                break;
		}
		return $html;
	}
}
?>
