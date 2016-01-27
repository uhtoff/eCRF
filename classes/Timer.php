<?php
/**
 * Timer script
 */
Class Timer {
    /**
     * Start time
     * @staticvar double
     */
	private static $start;
    /**
     * @staticvar double End time
     */
	private static $end;
    /**
     * Call to start timer
     * @static
     */
	public static function start() {
		self::$start = microtime( TRUE );
	}
    /**
     * Call to stop timer
     * @static
     */
	public static function stop() {
		self::$end = microtime( TRUE );
	}
    /**
     * Call to stop timer and show time elapsed, can chain the call to
     * give multiple interval times as resets the start value
     * @static
     */
	public static function show() {
        self::stop();
		$duration = self::$end - self::$start;
        $show = "<br />\n";
		$show .= $duration * 1000;
		$show .= " msec<br />\n";
        self::start();
        return $show;
	}
    /**
     * Call to return just the number of msec
     * @return double
     */
    public static function result() {
        self::stop();
        $duration = self::$end - self::$start;
        self::start();
        return $duration * 1000;
    }
}
?>