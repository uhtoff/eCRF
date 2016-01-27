<?php
class HTMLForm extends HTMLObject {
	protected $method;
	protected $action;
	protected $inputs = array();
    protected $_buttons = array();
    protected $_cancel = NULL;
	public function __construct( $action, $method, $enctype = NULL ) {
		if ( $action ) {
			$this->addAction( $action );
		}
		if ( $method ) {
			$this->addMethod( $method );
		}
        if ( $enctype ) {
            $this->addEncType( $enctype );
        }
		$this->addClass( 'form-horizontal' );
	}
	public function addMethod( $method ) { // Only two valid methods for a form
		if ( $method == 'get' || $method == 'post' ) {
			$this->method = $method;
		}
	}
	public function addAction( $action ) {
		$this->action = $action;
	}
    public function addEncType( $enctype ) {
        $this->encType = $enctype;
    }
    public function writeEncType() {
        if( isset( $this->encType ) ) {
            $html = "enctype=\"{$this->clean($this->encType)}\"";
            return $html;
        }
        
    }
	public function addInput( $type, $name, $value = NULL, $label = NULL, $class = NULL, $language = NULL ) { // Adds input to form
		$input = new HTMLInput( $type, $name, $value, $language );
		if ( $label ) $input->addLabel( $label );
		if ( $class ) $input->addClass( $class );
		$this->inputs[] = $input;
		return $input;
	}
	public function addInputClass( $name, $class ) { // Loops through inputs to add class to relevant input
		foreach( $this->inputs as $input ) {
			if ( $input->getName() == $name ) {
				$input->addClass( $class );
				break;
			}
		}
	}
	public function addInputLabel( $name, $label ) { // Loops through inputs to add label to relevant input
		foreach( $this->inputs as $input ) {
			if ( $input->getName() == $name ) {
				$input->addLabel( $label );
				break;
			}
		}
	}
	public function addInputValue( $name, $value ) { // Loops through inputs to add value to relevant input
		foreach( $this->inputs as $input ) {
			if ( $input->getName() == $name ) {
				$input->addValue( $value );
				break;
			}
		}
	}
    public function disableInput( $name ) {
        $this->addInputClass( $name, 'disabled' );
    }
    public function readOnlyInput( $name ) {
        $this->addInputClass( $name, 'readonly' );
    }
    public function addButton( $text, $class = NULL ) {
        $this->_buttons[$text] = $class;
    }
    public function addCancelButton( $text ) {
        $this->_cancel = $text;
    }
	public function writeActions() {
		$html = "<div class=\"form-actions\">";
		if ( isset( $this->disabled ) || isset( $this->readonly ) ) {
			$html .= "<p>This page can not currently be edited.</p>";
		} else {
			$html .= "<button type=\"submit\" class=\"btn btn-primary\">Submit</button> ";
		}
        foreach( $this->_buttons as $text => $classes ) {
            $html .= "<button type=\"button\" class=\"btn ";
            foreach( $classes as $class ) {
                $html .= "{$class} ";
            }
            $html .= "\">{$text}</button> ";
        }
        if ( $this->_cancel ) {
            $html .= "<a href=\"{$this->_cancel}\">
                        <button type=\"button\" class=\"btn\">
                            Cancel
                        </button>";
        }
		$html .= "</div>";
		return $html;
	}

	public function processFields( $fields, $data = NULL, $defUnits = NULL, $language = 'en' ) {
		$a = new ArrayIterator( $fields );
		foreach( $a as $name => $details ) {
			$input = $this->addInput( $details['type'], $name, NULL, NULL, NULL, $language );
			$page = substr($name, 0, strpos($name, "-")); // Split out class and name from input field
			$name = substr($name, strpos($name, "-") + 1);
            if ( isset( $details['size']) ) {
                $input->setSize($details['size']);
            }
            if ( isset( $details['class']) ) {
                $input->addClass( $details['class'], true );
            }
			$input->addLabel( $details['label'] );
			if ( isset( $details['options'] ) ) {
				$input->addOption( $details['options'], true );
			}
			if ( isset( $details['unit'] ) ) {
				foreach( $details['unit'] as $unit=>$unitDetails ) {
					if ( isset($defUnits[$name]) ) {
						$unitConv = $defUnits[$name]['conversion'];
					} else {
						$unitConv = 1;
					}
					if ( $unitDetails['conversion'] == $unitConv ) {
						$input->addUnit(array($unitDetails['conversion'] => $unit), true);
//						$unitDecimal[$unitDetails['conversion']] = $unitDetails['decimals'];
						$input->subInput['unit']->addValue( $unitConv );
						$step = pow(10,0-($unitDetails['decimals']));
						$input->subInput['number']->addAttrib('step',$step);
					}
				}
//                if ( isset($defUnits[$name]) ) {
//                    $input->subInput['unit']->addValue( $defUnits[$name]['conversion'] );
//					$step = pow(10,0-($unitDecimal[$defUnits[$name]['conversion']]));
//					$input->subInput['number']->addAttrib('step',$step);
//                } else {
//                    $input->subInput['unit']->addValue( 1 );
//					$step = pow(10,0-($unitDecimal['1.0000']));
//					$input->subInput['number']->addAttrib('step',$step);
//                }
			}
            // Added after units set so that conversions can be done
            if ( $data ) {
                $newValue = NULL;
                if ( !is_null($data->getField($page,$name)) ) {
                    $newValue = $data->getField($page,$name);
                }
                $input->addValue( $newValue );
			}
            if ( !$input->getValue() && isset( $details['default']) ) {
                $input->addValue( "{$details['default']}" );
            }
			if ( $details['toggle'] ) {
				if ( strpos( $details['toggle'], '_' ) ) {
					$toggle = explode( '_', $details['toggle'] );
					$input->addClass( "toggle_{$toggle[0]}", true );
					$input->addAttrib( "data-toggle", $toggle[1], true );
				} else {
					$input->addClass( "toggle_{$details['toggle']}", true );
					$input->addAttrib( "data-toggle", 1, true );
				}
			}
			if ( $details['mandatory'] ) {
				$input->setMand();
			}
			if ( $details['readonly'] ) {
				$input->makeReadOnly();
			}
		}
	}
	public function addErrors( $error ) { // Takes the error Session variable and loops through the inputs to add error state
		foreach( $this->inputs as $input ) {
			if ( isset( $error[$input->getName()] ) ) {
                $errorVal = $error[$input->getName()]['value'];
                $errorMessage = $error[$input->getName()]['error'];
				if ( emptyInput( $input->getValue() ) ) {
					if ( $errorVal !== false ) $input->addValue( $errorVal );
					$input->setError( 'error', $errorMessage );
				} else {
					$input->setError( 'error_value' );
				}
			} else if ( $input->isMand() && emptyInput( $input->getValue() ) ) {
				$input->setError( 'warning' );
			}		
		}
	}
	public function disableForm() {
		$this->disabled = true;
		$this->addClass( 'signed' );
		foreach( $this->inputs as $input ) {
			$input->disableInput();
		}
	}
    public function makeReadOnly() {
        $this->readonly = true;
        foreach( $this->inputs as $input ) {
			$input->makeReadOnly();
		}
    }
	public function writeHTML( $justFields = FALSE ) {
        $html = '';
        if ( !$justFields ) {
            $html = "<form action=\"{$this->clean( $this->action )}\" method=\"{$this->clean( $this->method )}\" ";
            $html .= "{$this->writeID()} ";
            $html .= "{$this->writeClasses()} ";
            $html .= "{$this->writeEncType()} ";
            $html .= " >";
        }
		foreach( $this->inputs as $input ) {
			$html .= "\n\t";		
			$html .= "<div class=\"control-group";
			if ( $input->hasError() ) {
				$html .= " {$input->getError()}";
			}
			if ( $input->isHidden() ) {
				$html .= " hidden";
			}
			$html .= "\">";
			
			$html .= "\n\t\t";
			$html .= $input->writeLabel();
			$html .= "\n\t\t";
//			if ( $input->getType() != 'heading' ) {
				$html .= "<div class=\"controls\">";
			
				$html .= "\n\t\t\t";
				$html .= $input->writeControl();
				if ( $input->showHelp() ) {
					$html .= $input->writeHelp();
				}
				$html .= "\n\t\t";
				$html .= "</div>";
				$html .= "\n\t";
//			}
			$html .= "</div>";
		}
		$html .= "\n";
        if ( !$justFields ) {
            $html .= $this->writeActions();
            $html .= "\n</form>";
        }
		return $html;
	}
}

?>
