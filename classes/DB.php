<?php  
    /**
     *  Class to manage database use, holds handlers and user/pass details
     *  Manages queries and transactions
     */
Class DB {
	private static $dbh = array(), $dbhost = "localhost";
	private static $dbprefix = ""; // prefix for databases on server
	private static $userhash = array();
	private static $dbname;
    /**
     * Initialises the userhash from a username/password table
     * 
     * @param string $db Database holding the user table
     * @param string $table Table with columns db, username, password
     * @param string $user Username for the user database
     * @param string $pass Password for the user database
     */
	public static function init( $db, $table, $user, $pass ) {
        // Set the database to the user DB
        DB::setDB($db, $user, $pass);
        // Clean the table name to prevent injection
        $table = DB::clean( $table );
        $sql = "SELECT db, username, password FROM {$table}";
        $result = DB::query( $sql );  
        
        // If valid result received then loop through and add users
        foreach ( $result->rows as $row ) {
            DB::addUser( self::$dbprefix . $row->db, $row->username, $row->password );
        }
	}
    /**
     * Static method to set the current database
     * 
     * Can be sent with just the name of the database (excluding the server
     * prefix if applicable), will check to see if the user has already been
     * set, if it hasn't then will see if a username and password has been
     * sent and use those, if no user credentials available then returns
     * false.
     * 
     * @param string $db Name of the database (excluding prefix)
     * @param string $user Username of database user (optional - excl prefix)
     * @param string $pass Password for database user (optional)
     * @return boolean Success of setting DB
     */
	public static function setDB( $db, $user = NULL, $pass = NULL, $host = 'localhost' ) {
		self::$dbhost = $host;
		$db = self::$dbprefix . $db;
        // Check to see if that DB already set
        if( self::getDB() === $db ) {
            $setDB = self::getDBh();
        // Else see if username and password already set
        } elseif( array_key_exists( $db, self::getUsers() ) ) {
            self::$dbname = $db;
            $setDB = self::getDBh();
        // Else if new username and password sent, store them
		} elseif ( $user && $pass ) {
            self::addUser($db, $user, $pass);
            self::$dbname = $db;
            $setDB = self::getDBh();
        // Else set return to false as DB can't be set
        } else {
            $setDB = false;
		}
		return $setDB;
	}
    /**
     * Returns name of selected database
     * 
     * @return boolean|string Name of currently selected database or false 
     * if none selected
     */
	public static function getDB() {
        // Check to see if DB is set and return appropriately
		if( self::$dbname ) {
			return self::$dbname;
		} else {
			return false;
		}
	}
    /**
     * Sets the database prefix to be applied to all users and databases
     * 
     * @param string $prefix Prefix to be set
     * @return boolean true if successfully set
     */
	public static function setPrefix( $prefix ) {
		$pattern = '/\w+_$/';
        // Checks it only has valid characters and ends with _
		if( preg_match( $pattern, $prefix ) ) {
			self::$dbprefix = $prefix;
			$setPrefix = true;
		} else {
			$setPrefix = false;
		}
		return $setPrefix;
	}
    /**
     * Retrieve the username/password hash
     * 
     * @return array hash with username and passwords
     */
	private static function getUsers() {
        // Return user hash
        return self::$userhash;
	}
    /**
     * Add a user to the username/password hash
     * 
     * @param string $db Database the user can access
     * @param string $user Username to use
     * @param string $pass Password to use
     */
	private static function addUser( $db, $user, $pass ) {
        self::$userhash[$db]['username'] 
                = self::$dbprefix . $user;
        self::$userhash[$db]['password'] 
                = $pass;       
	}
    /**
     * Get the mysqli database handler
     * 
     * This will see if a handler for the requested database is already in
     * self::$dbh and if not then attempt to generate it from the username
     * hash
     * 
     * @param string $db Database name, if not already set (usually one off
     * call) 
     * @return boolean|\mysqli Database handler
     */
	private static function getDBh( $db = NULL ) {
        // Initialise return value
        $getDBh = false;
        if ( $db ) {
            $dbname = self::$dbprefix . $db;
        } else {
            $dbname = self::getDB();
        }
        $userhash = self::getUsers();
        // See if handler already initialised, if so retrieve it
		if( isset( self::$dbh[ $dbname ] ) ) {
            $getDBh = self::$dbh[ $dbname ];
        // Else check that username/pass available for the database
        } elseif($userhash && array_key_exists($dbname, $userhash)) { 
            // Initialise new database connection if one doesn't exist
            $getDBh = self::$dbh[ $dbname ] = new mysqli (
                    self::$dbhost, 
                    self::$userhash[ $dbname ][ "username" ], 
                    self::$userhash[ $dbname ][ "password" ], 
                    $dbname );
            if (mysqli_connect_error()) {
                die( "Unable to make mysqli databse connection" );
            }
            // Set basic database environment variables
            $getDBh->query( "SET NAMES 'utf8'" );
			$getDBh->query( "SET SESSION time_zone='+00:00'" );
			$getDBh->set_charset('utf8');
		}
		return $getDBh;
	}
    /**
     * Approximate bind type for entered variable, try to avoid
     * 
     * @param mixed $value Variable to get bind type of
     * @return string Bind type
     */
	public static function bindType( $value ) {
        // Switch to pick up 'i' and 'd'
        // Default is 's'
		switch( gettype( $value ) ) {
			case "integer":
			case "boolean":
				$type = "i";
				break;
			case "double":
				$type = "d";
				break;
            default:
                $type = "s";
                break;
		}
		return $type;
	}
    /**
     * Fixes passing by reference requirement
     * 
     * @param array $arr Array to turn into reference array
     * @return array Reference array
     */
	private static function refValues( &$arr ) {
		$refValues = array();
        // Run through the array and create the reference array
		foreach( $arr as $key => $value ) {
			$refValues[ $key ] = &$arr[ $key ];
		}
		return $refValues;
	}
    /**
     * A function to generate an HTML safe query result
     * Parameters as per DB::query 
     * 
     * @param type $sql
     * @param type $paramArray
     * @param type $db
     * @return type
     */
    public static function cleanQuery( $sql, $paramArray = NULL, $db = NULL ) {
        return self::query($sql, $paramArray, $db, TRUE);
    }
    /**
     * Main query generator
     * 
     * @param string $sql String of SQL code to execute
     * @param array $paramArray Array of parameters to bind, bind types as 
     * first element of the array. Can send array of arrays, in which case
     * it will be treated as a transaction.
     * @param string $db Optional, send if a different database is to be used
     * for just this query.
     * @return boolean|\DBResult Returns false if query fails, otherwise result
     * object
     */
	public static function query( $sql, $paramArray=NULL, $db=NULL, $auto_clean = NULL, $index = NULL ) {
        // For function flexibility can send dbname as 2nd parameter
        $log = false;
        if ( $log )
            Timer::start();
		if ( $paramArray && !is_array( $paramArray ) ) {
			$db = $paramArray;
			$paramArray = NULL;
		}
        // Get database handler
		$dbh = self::getDBh( $db );
        
        // If query fails to initialise then print error message
		if( !$dbh || !$stmt = $dbh->prepare( $sql ) ) {
			echo "<p>DB failure on database " . self::getDB() . 
                    " with query $sql";
            if ( $paramArray ) {
                "and parameters - ";
                print_r( $paramArray );
            }
            echo "</p>";
            echo "<p>With error message - ";
            echo $dbh->error;
            echo "</p>";
            echo "<pre>";
            var_dump( debug_backtrace() );
            var_dump( $db );
            echo "</pre>";
			exit();
		}
        $affRows = 0;
        // Bind parameters if supplied
		if ( $paramArray ) {
            // Allows an array of parameters to be applied to the same prepared
            // statement
            // Turns off autocommit for 10x speed!
			if ( is_array( $paramArray[0] ) ) {
                self::begin();
				foreach ( $paramArray as $pA ) {
                    self::bindParam($stmt, $pA);
                    $affRows += $stmt->affected_rows;
				}
                self::commit();
            // Single set of parameters only
			} else {
                self::bindParam($stmt, $paramArray);
                $affRows += $stmt->affected_rows;
			}
        // No parameters, just execute the sql
		} else {
			$stmt->execute();
            $affRows += $stmt->affected_rows;
		}
		$meta = $stmt->result_metadata();
        
		$result = new DBResult();
        // If no metadata then INSERT, UPDATE etc. so return id data
		if( !$meta ) {  
			$result->affected_rows = $affRows;
            // Insert ID of last inserted if multiple done
            $result->insert_id = $stmt->insert_id; 
        // If metadata then retrieve and store results
		} else { 
			$stmt->store_result();
            // If no results from the SELECT return false
			$result->num_rows = $stmt->num_rows;
			$params = array();
			$row = array();
			$result->rows = array();
            // Get field names from meta
			while ( $field = $meta->fetch_field() ) { 
				$params[] = &$row[ $field->name ];
			}
			$meta->close();
			call_user_func_array( array( $stmt, 'bind_result' ), $params );
            // Create result object as $result->rows[]->fieldnames
			while ( $stmt->fetch() ) { 
				$rowobj = new StdClass();
				foreach( $row as $key => $value ) {
                    if ( $auto_clean ) {
                        $value = HTML::clean($value);
                    }
					$rowobj->$key = $value;
				}
				if ( $index ) {
					$result->rows[$rowobj->$index]=$rowobj;
				} else {
					$result->rows[] = $rowobj;
				}
			}
			$stmt->free_result();
		}
		$stmt->close();
        if ( $log ) {
            $time = Timer::result();
            if ( self::getDB() != 'dbusers' ) {
                $logsql = "INSERT INTO dbLog ( `sql`, params, `time`, `dbfn` ) VALUES ( ?, ?, ?, 'DB' )";
                $pA = array( $sql, serialize($paramArray), $time );
                DBP::query( $logsql, $pA, 'ecrf' );
            }
            Timer::stop();
        }
		return $result;
	}
    /**
     * Turn off auto committing of statements
     */
	public static function begin() {
		self::getDBh()->autocommit(false);
	}
    /**
     * Rollback any uncommitted statements and turn on autocommit
     */
	public static function rollback() {
		self::getDBh()->rollback();
		self::getDBh()->autocommit(true);
	}
    /**
     * Commit all statements and turn autocommit back on
     */
	public static function commit() {
		self::getDBh()->commit();
		self::getDBh()->autocommit(true);
	}
    /**
     * Perform a number of queries as a single transaction - DOES NOT WORK!
     * 
     * @param array $sqlArray SQL statements to perform
     * @param array $pAArray Parameters for the above statements
     * @return boolean|\DBResult Returns last result if queries successful
     */
	public static function transaction( array $sqlArray, array $pAArray ) {
        $transaction = false;
       // Check equal numbers of statements and parameters have been sent
        if (count($sqlArray) === count($pAArray)) {
            self::begin();
            // Loop through arrays
            for ( $i = 0; $i < count( $sqlArray ); $i++ ) {
                $result = DB::query( $sqlArray[$i], $pAArray[$i] );
                // If query unsuccessful then rollback and stop executing
                if( !$result ) {
                    self::rollback();
                    break;
                }
            }
            // If there is a valid result object
            if ( $result ) {
                self::commit();
                $transaction = $result;
            }
        }
		return $transaction;
	}
    /**
     * Escape ready for mySQL database
     * 
     * @param mixed $dirty Output to escape
     * @return mixed Escaped output
     */
	public static function clean( $dirty ) {
        // Retrieve handler
		$dbh = self::getDBh();
        // If magic_quotes set will need to stripslashes first
		if( get_magic_quotes_gpc() ) stripslashes( $dirty );
		$clean = $dbh->real_escape_string( $dirty );
		return $clean;
	}

    private static function bindParam($stmt, $pA) {
        $method = new ReflectionMethod('mysqli_stmt', 'bind_param'); 
		$method->invokeArgs( $stmt, self::refValues( $pA ) );
        $stmt->execute();
    }
}
?>