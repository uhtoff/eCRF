<?php
/**
 * Data is the main data containing object for the database
 * Extends DBObject and allows setting of className directly
 */
class Data extends DBObject {
    /**
     * Data constructor
     * 
     * @param int $id Optional - if set gets data from PID = $id
     * @param string $class Option - if set then sets className directly
     */
    public function __construct($id = NULL, $class=NULL) {
        if ( $class ) {
            $this->setClassName($class);
        }
        parent::__construct($id);
    }
    /**
     * Sets the className rather than getting it from the name of the object
     * Prevents having to have a class for every data object
     * 
     * @param string $class Name class is set as in the database
     */
    public function setClassName($class) {
        $this->_className = $class;
    }
    public function getFlag($page, $fieldName, $link_id = NULL) {
        $getFlag = false;
        $sql = "SELECT flagType_id, flagText FROM flag 
            WHERE link_id = ? AND field = ?";
        $field = "{$page}-{$fieldName}";
        if (!$link_id) {
            $link_id = $_SESSION['user']->isLinked();
        }
        $pA = array('is', $link_id, $field);
        $result = DB::query($sql, $pA);
        if ( $result->getRows() ) {
            $getFlag = array('flagType' => $result->flagType_id, 
                'flagText' => $result->flagText);
        }
        return $getFlag;
    }
	public function get_from_db( $id = NULL ) {
		if ( $id ) $this->setID( $id );
		$this->getFromDB();
	}
	public function save_to_db() {
		$save_to_db = $this->saveToDB();
		return $save_to_db;
	}
}
?>