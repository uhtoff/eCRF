<?php
/**
 * Description of ValidationRules
 *
 * @author Russ
 */
class ValidationRules {
    private $_counter, $_firstGroup;
    protected $_groups, $_varType, $_fieldName, $_page;
    public function addRulesFromDB( $rules ) { 
        foreach( $rules as $row ) {
            if ( !isset( $this->_varType ) ) {
                $this->_varType = $row->varType;
            }
            if ( !isset( $this->_page ) ) {
                $this->_page = $row->page;
            }
            if ( !isset( $this->_fieldName ) ) {
                $this->_fieldName = $row->fieldName;
            }
            if ( !isset( $this->_firstGroup ) ) {
                $this->_firstGroup = $row->groupNum;
            } else {
                if ( $row->groupNum < $this->_firstGroup ) {
                    $this->_firstGroup = $row->groupNum;
                }
            }
            if ( !isset( $this->_groups[$row->groupNum]) ) {
                $this->_groups[$row->groupNum] = new ValidationGroup($row->groupType);
            }
            $this->_groups[$row->groupNum]->addRule($row->value,$row->operator,$row->special,$row->errorMessage);
        }
        $this->_counter = $this->_firstGroup;
    }
    public function getType() {
        return $this->_varType;
    }
    public function getPage() {
        return $this->_page;
    }
    public function getFieldName() {
        return $this->_fieldName;
    }
    public function reset() {
        $this->_counter = $this->_firstGroup;
    }
    public function nextGroup() {
        $nextGroup = NULL;
        if ( isset( $this->_groups[$this->_counter] ) ) {
            $nextGroup = $this->_groups[$this->_counter];
            $this->_counter++;
        }
        return $nextGroup;
    }
    
}
?>
