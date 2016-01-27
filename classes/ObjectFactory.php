<?php
/**
 * Description of ObjectFactory
 *
 * @author Russ
 */
class ObjectFactory {
    private static $tables = array();
    private static $details = array();
    private static $objects = array();
    public static function getTableDetails($className) {
        if ( !isset( self::$tables[$className])) {
            $sql = "SELECT id, tableName, pKey FROM classes WHERE name = ?";
            $pA = array('s', $className);
            self::$tables[$className] = DB::query($sql, $pA);
            
        }
        return self::$tables[$className];
    }
    public static function getDetails($className) {
        if ( !isset( self::$details[$className])) {
            $sql = "SELECT name, subTableId, multiple, 
                encrypted, audit, readonly, autoload 
                FROM classFields WHERE classes_id = ?";
            $pA = array('i', self::$tables[$className]->id);
            self::$details[$className] = DB::query($sql, $pA);
        }
        return self::$details[$className];
    }
}
?>
