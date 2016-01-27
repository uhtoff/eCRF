<?php
/**
 * Description of Config
 *
 * @author Russ
 */
class Config {
   private static $registry;

   /**
   *
   */
   private function __construct() {}

   /**
   *
   */
   public static function get($key)
   {
      if (isset(self::$registry[$key])) return self::$registry[$key];
      else return FALSE;
   }

   /**
   *
   */
   public static function set($key, $value, $overwrite = FALSE)
   {
      // Does the variable already exist?
      if (isset(self::$registry[$key]) && $overwrite === FALSE) 
         throw new Exception();

      self::$registry[$key] = $value;
   }
}
?>
