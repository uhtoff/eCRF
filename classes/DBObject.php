<?php
/**
 * @abstract class to manage retrieving, saving and creating objects from a 
 * database.
 * 
 * Class names held in `classes` table in database -
 *      name - class name
 *      tableName - table holding class fields
 *      pkey - pkey of the above table (usually id)
 * 
 * Properties held in `classFields` table in database -
 *      classes_id - id from `classes`
 *      name - property name
 *      subTableId - does field come from another table, if so this holds the
 * pkey
 *      multiple - can field hold multiple values
 *      encrypted - is field encrypted
 *      audit - should property be audited
 *      readonly - is field readonly
 *      autoload - should field be loaded when class instantiated
 * 
 */
abstract class DBObject {
    /*
      Class to manage retrieving, saving and creating objects from a database
      Properties
      $_multiple = array of fields with multiple data points
      $_encrypted = encrypted fields
      $_fieldList = a list of properties to get from the database, arranged as an array of tableNames
      $_pKey = primary keys to search by in the tables
     */
/**
 *
 * @var type 
 */
    protected $_multiple = array();
    protected $_audit = array();
    protected $_readonly = array();
    protected $_autoFill = true;
    protected $_validate = false;
    protected $_encrypted = array();
    protected $_fieldList, $_table, $_id, $_pKey;
    protected $_autoload = array();
    protected $_inSubTable = array();
    protected $_subTableId = array();
    protected $_db = NULL;
    protected $_class = array();
    protected $_className;
/**
 * 
 * @param type $id
 * @param type $table
 * @return type
 */
    public static function getName($id, $table, $field = NULL) {
        if ( !$field ) {
            $field = 'name';
        }
        $getName = NULL;
        $table = DB::clean($table);
        $sql = "SELECT {$field} FROM {$table} WHERE id = ?";
        $pA = array('s', $id);
        $result = DB::query($sql, $pA);
        $getName = $result->{$field};
        return $getName;
    }

    public function __construct($id = NULL) {
        // Get basic class information from database and store in object
//        $sql = "SELECT id, tableName, pKey FROM classes WHERE name = ?";
        if ( !$this->_className ) {
            $this->_className = get_class($this);
        }
//        $pA = array('s', $this->_className);
        $result = ObjectFactory::getTableDetails($this->_className);
        if ( $result->getRows() ) {
            // Store class information in protected fields
            if(!isset($this->_table))
                $this->_table = DB::clean($result->tableName);
            $this->_pKey = DB::clean($result->pKey);
            // Get fields from database
//            $sql = "SELECT name, subTableId, multiple, 
//                encrypted, audit, readonly, autoload 
//                FROM classFields WHERE classes_id = ?";
//            $pA = array('i', $result->id);
//            $result = DB::query($sql, $pA, $this->_db);
            $result = ObjectFactory::getDetails($this->_className);
            $this->addFields($result);
        }
        if ($id)
            $this->setID($id);
        // If autoFill is true then get data from the DB (outside ID if so can
        // retrieve a blank record)
        if ($this->_autoFill)
            $this->getFromDB();
    }
    protected function addFields( DBResult $result ) {
        foreach($result->rows as $row) {
            $name = DB::clean($row->name);
            // Creates list of field names
            $this->_fieldList[] = $name;
            if ($row->multiple) {
                // Creates a list of properties which will hold 
                // multiple values (for many-to-many relations)
                $this->_multiple[] = $name;
            }
            // List of encrypted fields
            if ($row->encrypted) {
                $this->_encrypted[] = $name;
            }
            // List of fields to get when using getFromDB
            if ($row->autoload) {
                $this->_autoload[] = $name;
            }
            // List of fields to audit
            if ($row->audit) {
                $this->_audit[] = $name;
            }
            // List of readonly fields
            if ($row->readonly) {
                $this->_readonly[] = $name;
            }
            // Setting for fields from secondary tables
            if ($row->subTableId) {
                // List of secondary table fields
                $this->_inSubTable[] = $name;
                // Field name for the 2ary id must be in the format 
                // 'table'_'id'
                $sTSplit = explode('_', $row->subTableId, 2);
                // The field conatining the 2ary table id
                $this->_subTableId[$name] = $row->subTableId;
                // List of primary keys
                $this->_pKeyList[$name] = $sTSplit[1];
                // Table to look for
                $this->_tableList[$name] = $sTSplit[0];
            }
        }
    }
    public function getClass() {
        return get_class($this);
    }
    protected function getFieldList() {
        return $this->_fieldList;
    }
    protected function isEncrypted( $field ) {
        $iE = false;
        if ( in_array( $field, $this->_encrypted ) ) {
            $iE = true;
        }
        return $iE;
    }
// Getters - mostly protected, though getID is public
    public function get($prop) {
        $p = NULL;
        // isset is faster, but property_exists needed to return NULL values
        if (isset($this->$prop) || property_exists($this, $prop)) {
            $p = $this->$prop;
            // Is it a property that isn't auto loaded, but is a potential field
        } else if ( $this->getFieldList() && in_array($prop, $this->getFieldList())) {
            // Retrieve just that field from the database
            $this->getFromDB($prop);
            // Recurse to fill the value
            $p = $this->get($prop);
            $this->{"old_{$prop}"} = $p;
        }
        return $p;
    }
    public function getField( $page, $field ) {
        return $this->get( $field );
    }
    public function getID($field = NULL) {
        $getID = NULL;
        // To tolerate getting either a field or an array of fields
        if (is_array($field)) {
            $field = $field[0];
        }
        if ($field && isset($this->_subTableId[$field])) {
            $getID = $this->get($this->_subTableId[$field]);
        } else {
            $getID = $this->_id;
        }
        return $getID;
    }

    protected function getTable($field = NULL) {
        $getTable = false;
        // To tolerate getting either a field or an array of fields
        if (is_array($field)) {
            $field = $field[0];
        }
        if ($field && isset($this->_tableList[$field])) {
            $getTable = $this->_tableList[$field];
        } else {
            $getTable = $this->_table;
        }
        return $getTable;
    }

    public function getPKey($field = NULL) {
        $getPKey = NULL;
        // To tolerate getting either a field or an array of fields
        if (is_array($field)) {
            $field = $field[0];
        }
        if ($field && isset($this->_pKeyList[$field])) {
            $getPKey = $this->_pKeyList[$field];
        } else {
            $getPKey = $this->_pKey;
        }
        return $getPKey;
    }
    public function delete( $prop ) {
        if ( !$this->isReadonly($prop) ) {
            if( in_array($prop, $this->_multiple) ) {
                $this->$prop = array();
            }
            else {
                $this->$prop = NULL;              
            }
            $this->saveToDB( $prop );
        }
    }
// Setters with generic set at the end
    public function set($prop, &$value) {
        $set = true;
        // Check to see if the property is read only
        if (!$this->isReadonly($prop)) {
            $this->$prop = $value;
            // If the set item is one which had subTables hanging off it, 
            // then get the values
            if (in_array($prop, $this->_subTableId)) {
                foreach ($this->_inSubTable as $field) {
                    if ($this->_subTableId[$field] == $prop) {
                        $this->getFromDB($field);
                    }
                }
            }
        } else {
            $set = false;
        }
        return $set;
    }

    public function setID($id, $field = NULL) {
        if ($field && $this->inSubTable($field)) {
            $this->{$this->_subTableId[$field]} = $id;
            $this->saveToDB($this->_subTableId[$field]);
        } else {
            $this->_id = $id;
        }
        return $id;
    }

    public function inMultiple($field = NULL) { // Fn to check to see if a field should be audited
        $inMultiple = false;
        if (is_array($field)) { // To tolerate getting either a field or an array of fields
            $field = $field[0];
        }
        if ($field && in_array($field, $this->_multiple)) {
            $inMultiple = true;
        }
        return $inMultiple;
    }

    public function inAudit($field = NULL) { // Fn to check to see if a field should be audited
        $inAudit = false;
        if (is_array($field)) { // To tolerate getting either a field or an array of fields
            foreach( $field as $value ) {
                if ( in_array($value, $this->_audit)) {
                    $inAudit = true;
                    break;
                }
            }
        }
        if ($field && in_array($field, $this->_audit)) {
            $inAudit = true;
        } else if (!$field && $this->_audit) {
            $inAudit = true;
        }
        return $inAudit;
    }
    // Fn to check to see if a field is readonly, returns false if record is new
    public function isReadonly($field) {
        $inReadonly = false;
        if (is_array($field)) { // To tolerate getting either a field or an array of fields
            $field = $field[0];
        }
        if (in_array($field, $this->_readonly) && $this->getID()) {
            $inReadonly = true;
        }
        return $inReadonly;
    }
    // Fn to check to see if a field is in a secondary table
    protected function inSubTable($field) {
        $inSubTable = false;
        if (is_array($field)) { // To tolerate getting either a field or an array of fields
            $field = $field[0];
        }
        if (in_array($field, $this->_inSubTable)) {
            $inSubTable = true;
        }
        return $inSubTable;
    }

// Fn to remove the table name from a 2ary table field in order to retrieve 
// from it's own table
    protected function removeTable($field) {
        $getField = NULL;
        if ($this->inSubTable($field)) {
            $table = $this->getTable($field);
            $getField = str_replace("{$table}_", "", $field);
        } else {
            $getField = $field;
        }
        return $getField;
    }

    private function insertRow($table = NULL) {
        // Add a new row to the relevant table, returning the new pKey id
        if (!$table) {
            $table = $this->getTable();
        }
        $sql = "INSERT INTO {$table} () VALUES ()";
        $result = DB::query($sql, $this->_db);
        return $result->getIID();
    }

    /**
     * NOTE - Will currently try to do a query even if there isn't an id to query
     * 
     * @param type $prop
     */
    public function getFromDB($prop = NULL) {
        // Populate object with data from DB
        if ($prop) {
            $request = array($prop);
        } else {
            $request = $this->_autoload;
        }
        $multiFields = $selectFields = array();
        if ( $request ) {
            foreach ($request as $fieldName) {
                // List for those requiring multiple values
                if (in_array($fieldName, $this->_multiple)) {
                    $multiFields[$this->getTable($fieldName)][] = $fieldName;
                // Create list of required fields, split by table
                } else if (in_array($fieldName, $this->getFieldList())) {
                    $selectFields[$this->getTable($fieldName)][] = $fieldName;
                }
            }
        }
        if ($selectFields) {
            foreach ($selectFields as $table => $fields) {
                if ( $this->getID() ) {
                    $queryFields = array();
                    $pKey = $this->getPKey($fields); // get pKey name
                    foreach ($fields as $field) {
                        $queryFields[] = $this->removeTable($field);
                    }
                    $qF = implode(', ', $queryFields);
                    $sql = "SELECT {$qF} FROM `{$table}` WHERE `{$pKey}` = ?";
                    $pA = array('i', $this->getID($fields)); // get correct ID
                    $result = DB::query($sql, $pA, $this->_db);
                    if ( $result->getRows() ) {
                        // populate object, fields and 'old' fields so we know what 
                        // is currently in there
                        foreach ($fields as $fieldName) {
                            // Remove the subTable from the field to query the table
                            $field = $this->removeTable($fieldName);
                            $this->$fieldName = 
                                    $this->{'old_' . $fieldName} = 
                                            $result->$field;
                        }
                    } 
                } else {
                    foreach ($fields as $fieldName) {
                        $this->$fieldName = $this->{'old_' . $fieldName} = NULL;
                    }
                }
            }
        }
        // Process any many-to-many relationships
        if ($multiFields) {
            foreach ($multiFields as $table => $fields) {
                foreach ($fields as $fieldName) {
                    // Remove the subTable from the field to query the table
                    $field = $this->removeTable($fieldName);
                    $this->$field = $this->{'old_' . $field} = array();
                    $refTable = $field;
                    $refTableID = DB::clean($refTable . '_id');
                    $jnTable = DB::clean($table . $refTable);
                    $tableID = DB::clean($table . '_id');
                    $sql = "SELECT $refTableID FROM $jnTable 
					WHERE $tableID = ? 
					ORDER BY $refTableID";
                    $pA = array('i', $this->getID($field));
                    if ($result = DB::query($sql, $pA, $this->_db)) {
                        foreach ($result->rows as $row) {
                            $this->{$field}[] =
                                    $this->{'old_' . $field}[] =
                                    $row->$refTableID;
                        }
                    }
                }
            }
        }
        if ($this->_encrypted) {
            $this->decrypt_obj();
        }
    }

    protected function decrypt_obj() {
        foreach ($this->_encrypted as $e) {
            if (isset($this->{$e}) && !empty($this->{$e})) {
                if (!isset($td))
                    $td = new Encrypt($_SESSION['user']->getKey());
                $this->{$e} = $this->{'old_' . $e} = $td->decrypt($this->{$e});
            }
        }
    }

    protected function encrypt_obj() {
        foreach ($this->_encrypted as $e) {
            if (isset($this->{$e}) && !empty($this->{$e})) {
                if (!isset($td))
                    $td = new Encrypt($_SESSION['user']->getKey());
                $this->{$e} = $td->encrypt($this->{$e});
            }
        }
    }

    public function deleteFromDB($removeAudit = false, $auditDelete = true) {
        if (isset($_SESSION['user']))
            $user_id = $_SESSION['user']->getID(); // Get id for audit purposes
        else
            $user_id = NULL;
        if (isset($_SERVER['REMOTE_ADDR']))
            $userip = $_SERVER['REMOTE_ADDR'];
        else
            $userip = NULL;
        if ($this->getID()) {
            $table = $this->getTable();
            $sql = "DELETE FROM {$table} WHERE {$this->getPKey()} = ?";
            $pA = array('i', $this->getID());
            DB::query($sql, $pA, $this->_db);
            foreach( $this->_multiple as $field ) {
                $refTable = $field;
                $jnTable = DB::clean($table . $refTable);
                $tableID = DB::clean($table . '_id');
                $sql = "DELETE FROM {$jnTable} WHERE {$tableID} = ?";
                $pA = array( 'i', $this->getID() );
                DB::query($sql, $pA, $this->_db);
            }
            if ( $this->inAudit() && $removeAudit ) {
                $rmAudit = "DELETE FROM `{$table}Audit` WHERE table_id = ?";
                $rmAuditPA = array('i',$this->getID());
                DB::query($rmAudit,$rmAuditPA);
            }
            if ($this->inAudit() && $auditDelete) { // If data to audit then audit
                $auditSql = "INSERT INTO `{$table}Audit` ( type, table_pkey, table_id, field, user_id, userip, old_value, new_value ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )";
                $auditPA = array("ssisisss", "DELETE", $this->getPKey(), $this->getID($table), NULL, $user_id, $userip, NULL, NULL);
                DB::query($auditSql, $auditPA);
            }
        }
    }

    /**
     * Returns ID of newly saved object
     * @param null $prop
     * @return integer
     */
    public function saveToDB($prop = NULL) {
        // Save data back to the database
        if (isset($_SESSION['user']))
            $user_id = $_SESSION['user']->getID(); // Get id for audit purposes
        else
            $user_id = NULL;
        if (isset($_SERVER['REMOTE_ADDR']))
            $userip = $_SERVER['REMOTE_ADDR'];
        else
            $userip = NULL;
        $updateFields = $updateValues = $auditPA = array();
        if ($prop) {
            $output = array($prop);
        } else {
            $output = $this->getFieldList();
        }
        if ( $output ) {
            foreach ($output as $field) { // Run through field list
                if (!property_exists($this, $field) /*|| $this->isReadonly($field)*/) { // If that property isn't set then move to next one, or it's set to be readonly
                    continue;
                }
                if (!$this->getID($field)) { // If id not set, then insert new row to save object into		
                    $this->setID($this->insertRow($this->getTable($field)), $field);
                }
                if (in_array($field, $this->_multiple)) { // Pick off the multiple result arrays
                    $table = $this->getTable($field);
                    $refTable = $field;
                    $refTableID = DB::clean($refTable . '_id');
                    $jnTable = DB::clean($table . $refTable);
                    $tableID = DB::clean($table . '_id');
                    $sqlAdd = "INSERT INTO $jnTable 
                            ( $tableID, $refTableID ) 
                            VALUES ( ?, ? )";
                    $sqlDel = "DELETE FROM $jnTable 
                            WHERE $tableID = ? AND 
                                $refTableID = ?";
                    $pAAdd = $pADel = array();
                    if ($this->inAudit($field)) { // If data to audit then audit
                        $auditSql[$table] = "INSERT INTO `{$table}Audit` 
                        ( type, table_pkey, table_id, field, user_id, userip, old_value, new_value ) 
                        VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )";
                    }
                    // If old field not set then initialise to empty array (avoid error)
                    if (!isset($this->{"old_{$field}"})) {
                        $this->{"old_{$field}"} = array();
                    }
                    // Run array_diff both ways to get the changes
                    $entryToAdd = array_diff($this->$field, $this->{'old_' . $field});
                    $entryToDel = array_diff($this->{'old_' . $field}, $this->$field);
                    if ($entryToAdd) {
                        // Create a pA for each new entry
                        foreach ($entryToAdd as $value) {
                            $pAAdd[] = array('ii', $this->getID($field), $value);
                            if ($this->inAudit()) {
                                $auditPA[$this->getTable($field)][] =
                                        array('ssisisii', 'ADD',
                                            $this->getPKey($field),
                                            $this->getID($field),
                                            $field, $user_id, $userip, NULL, $value);
                            }
                        }
                    }
                    if ($entryToDel) {
                        // Create a pA for each deleted entry
                        foreach ($entryToDel as $value) {
                            $pADel[] = array('ii', $this->getID($field), $value);
                            if ($this->inAudit()) {
                                $auditPA[$this->getTable($field)][] =
                                        array('ssisisii', 'DELETE',
                                            $this->getPKey($field),
                                            $this->getID($field),
                                            $field, $user_id, $userip, $value, NULL);
                            }
                        }
                    }
                    if ($pAAdd) {
                        DB::query($sqlAdd, $pAAdd, $this->_db);
                    }
                    if ($pADel) {
                        DB::query($sqlDel, $pADel, $this->_db);
                    }
                } else { // All other fields
                    if (!isset($this->{"old_{$field}"})) { // If old field not set then initialise to NULL
                        $this->{"old_{$field}"} = NULL;
                    }
                    if ( ( is_null($this->$field) || is_null($this->{'old_' . $field}) || $this->$field != $this->{'old_' . $field} )
                                && !( is_null($this->$field) && is_null($this->{'old_' . $field}) ) ) { // Determine which fields have been changed at all (may need to be changed !=)
                        $updateFields[$this->getTable($field)][] = $field;
                    }
                }
            }
        }
         // Encrypt object after field comparison done to prevent updating multiple times
        if ($this->_encrypted) {
            $this->encrypt_obj();
        }
        if ($updateFields) { // Then construct SQL if there are any fields needing updating
            foreach ($updateFields as $table => $fields) {
                
                if ($this->inAudit($fields)) { // If data to audit then audit
                    $auditSql[$table] = "INSERT INTO `{$table}Audit` 
					( type, table_pkey, table_id, field, user_id, userip, old_value, new_value ) 
					VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )";
                    
                }
                $queryFields = array();
                $pKey = $id = NULL;
                if (!$pKey)
                    $pKey = $this->getPKey($fields);
                if (!$id)
                    $id = $this->getID($fields);
                $pABind = '';
                foreach ($fields as $field) {
                    $queryFields[] = $this->removeTable($field);
                    $updateValues[$table][] = $this->$field;
                    $type = DB::bindType($this->$field);
                    $pABind .= $type;
                    if ( $this->inAudit($field)) {
                        if (!isset($this->{'old_' . $field}))
                            $change = "NEW";
                        elseif (is_null($this->$field))
                            $change = "DELETE";
                        else
                            $change = "UPDATE";
                        $auditPA[$table][] = array("ssisis{$type}{$type}", $change, $this->getPKey($field), $this->getID($field), $field, $user_id, $userip, $this->{'old_' . $field}, $this->$field);
                    }
                    $this->{'old_' . $field} = $this->$field;
                }
                $qF = implode(' = ?, ', $queryFields); // Field list for updating
                $qF .= ' = ?';
                $sql = "UPDATE `{$table}` SET {$qF} WHERE `{$pKey}` = ?";
                 // And create parameter array (potentially get the bind types from validation)
                $pABind .= DB::bindType($id);
                array_unshift($updateValues[$table], $pABind);
                array_push($updateValues[$table], $id);
                if (!DB::query($sql, $updateValues[$table], $this->_db)) {
                    die("DB Update failed with {$sql}.");
                }
            }
        }
        if ($auditPA) {
            foreach ($auditPA as $table => $aPA) {
                DB::query($auditSql[$table], $aPA, $this->_db);
            }
        }
        $saveToDB = $this->getID(); // Return the ID number
        if ($this->_encrypted) {
            $this->decrypt_obj();  // In case the object needs further work doing on it, decrypt it again for ongoing use (prevents double encryption)
        }
        return $saveToDB;
    }

}

/*
  SQL for auditTable

  CREATE TABLE  `perioper_novap`.`flagAudit` (
  `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
  `type` VARCHAR( 15 ) DEFAULT NULL ,
  `table_pkey` VARCHAR( 50 ) DEFAULT NULL ,
  `table_id` INT( 11 ) DEFAULT NULL ,
  `field` VARCHAR( 100 ) DEFAULT NULL ,
  `old_value` VARCHAR( 1000 ) DEFAULT NULL ,
  `new_value` VARCHAR( 1000 ) DEFAULT NULL ,
  `user_id` INT( 11 ) DEFAULT NULL ,
  `userip` VARCHAR( 15 ) DEFAULT NULL ,
  `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
  PRIMARY KEY (  `id` )
  ) ENGINE = innoDB DEFAULT CHARSET = utf8;

  SQL for classes
  
  CREATE TABLE `classes` (
 `id` smallint(6) NOT NULL AUTO_INCREMENT,
 `name` varchar(25) NOT NULL,
 `tableName` varchar(25) DEFAULT NULL,
 `pKey` varchar(25) NOT NULL DEFAULT 'id',
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8

  SQL for classFields
  
 CREATE TABLE `classFields` (
 `id` mediumint(9) NOT NULL AUTO_INCREMENT,
 `classes_id` smallint(6) NOT NULL,
 `name` varchar(25) NOT NULL,
 `subTableId` varchar(25) DEFAULT NULL,
 `multiple` tinyint(1) DEFAULT '0',
 `encrypted` tinyint(1) NOT NULL DEFAULT '0',
 `audit` tinyint(1) NOT NULL DEFAULT '1',
 `readonly` tinyint(1) NOT NULL DEFAULT '0',
 `autoload` tinyint(1) NOT NULL DEFAULT '1',
 PRIMARY KEY (`id`),
 KEY `classes_id` (`classes_id`),
 CONSTRAINT `classfields_ibfk_1` FOREIGN KEY (`classes_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8

 */
?>