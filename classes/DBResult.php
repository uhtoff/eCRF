<?php
/**
 * Database return class, so it can have useful methods added
 */
Class DBResult {
    public $rows = array();
    private $counter = 0;
    /**
     * Magic method get, returns a field from the first record
     * 
     * @param string $name The fieldname you want to retrieve the value for
     * @return mixed Value from the first record of the database search
     */
	public function __get( $name ) {
		return isset($this->rows[0]->$name) ? $this->rows[0]->$name : NULL;
	}
    /**
     * Get the last auto-increment insert ID
     * 
     * @return int|boolean Either the insert ID or false
     */
    public function getIID() {
        return isset($this->insert_id) ? $this->insert_id : NULL;
    }
    /**
     * Get number of rows from SQL
     * 
     * @return int Number of rows selected, or affected by an update/insert
     */
    public function getRows() {
        return isset($this->num_rows) ? $this->num_rows : $this->affected_rows;
    }
    /**
     * Retrieve an array of a single property from the query result
     * 
     * @param string $prop Required property
     * @return array
     */
    public function getArray($prop, $key = NULL) {
        $getArray = array();
        foreach( $this->rows as $row ) {
            if ( isset( $row->$prop ) ) {
                if ( $key ) {
                    $getArray[$row->$key] = $row->$prop;
                } else {
                    $getArray[] = $row->$prop;
                }
            }
        }
        return $getArray;
    }
    /**
     * Step through object
     * 
     * @return array|boolean Next row in the result object if available
     */
    public function nextRow() {
        $nextRow = false;
        if(isset($this->rows[$this->counter])) {
            $nextRow = $this->rows[$this->counter];
            $this->counter++;
        }
        return $nextRow;
    }
    /**
     * Reset counter to start
     */
    public function reset() {
        $this->counter = 0;
    }
}
?>
