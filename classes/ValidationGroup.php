<?php
/**
 * Description of ValidationGroup
 *
 * @author Russ
 */
class ValidationGroup {
    protected $_type;
    protected $_counter = 1;
    protected $_ruleNum = 1;
    protected $_rules = array();
    public function __construct($type){
        $this->_type = $type;
    }
    public function addRule($value,$operator,$special,$errorMessage){
        $this->_rules[$this->_ruleNum++] = array( 'value' => $value,
                                'operator' => $operator,
                                'special' => $special,
                                'error' => $errorMessage );
    }
    public function getType() {
        return $this->_type;
    }
    public function reset() {
        $this->_counter = 1;
    }
    public function nextRule() {
        $nextRule = NULL;
        if ( isset( $this->_rules[$this->_counter] ) ) {
            $nextRule = $this->_rules[$this->_counter];
            $this->_counter++;
        }
        return $nextRule;
    }
}

?>
