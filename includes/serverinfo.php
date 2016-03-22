<?php
$sql = "SELECT VERSION() as mysql_version, NOW() as mysql_time, @@global.time_zone as global_tz, @@session.time_zone as session_tz, @@character_set_database as charset, @@collation_database as collation";
$mysql_settings = DB::query($sql);
echo "<p>mySQL version is {$mysql_settings->mysql_version}</p>";
echo "<p>PHP version is " . phpversion() . "</p>";
echo "<hr/>";
echo "<p>mySQL clock set to : " . $mysql_settings->mysql_time . "</p>";
echo "<p>mySQL global TZ : " . $mysql_settings->global_tz . "</p>";
echo "<p>mySQL session TZ : " . $mysql_settings->session_tz . "</p>";
$php_time = time();
echo "<p>php time() gives : " . $php_time . "</p>";
echo "<p>php date() gives : " . date("Y-m-d H:i:s", $php_time) . "</p>";
echo "<p>php gmdate() gives : " . gmdate("Y-m-d H:i:s", $php_time) . "</p>";
echo "<p>php timezone is : " . date_default_timezone_get() . "</p>";
echo "<hr/>";
echo "<p>mySQL charset : " . $mysql_settings->charset . "</p>";
echo "<p>mySQL collation : " . $mysql_settings->collation . "</p>";
echo "<p>php charset : " . mb_internal_encoding() . "</p>";

?>
