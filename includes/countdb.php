<?php
echo "<p class=\"lead\">Download all data</p>";
echo "<form action=\"process.php\" method=\"POST\">";
echo "<label for=\"country_id\">Select the country to download: ";
echo "<select name=\"country_id\">";
$sql = "SELECT id, name FROM country";
$countries = DB::query( $sql );
foreach ( $countries->rows as $row ) { 
    echo "<option value=\"$row->id\">$row->name</option>";
}
echo "</select>";
echo "</label>";
echo "<label for=\"encrypted\">Download patient identifiable data? ";
echo "<input type=\"radio\" name=\"encrypted\" value=\"0\" checked=\"checked\"> No ";
echo "<input type=\"radio\" name=\"encrypted\" value=\"1\" > Yes ";
echo "</label>";
//echo "<p><a href=\"/docs/Key.xlsx\">Download data dictionary</a></p>";
echo "<div class=\"confirmText\">";
echo "<p>In order to download patient identifiable data you must re-enter your password below.";
echo "This confirms your identity and your agreement that the data will not be stored or transmitted in an unencrypted form.</p>";
echo "<label for=\"password\">Confirm your password: ";
echo "<input id=\"password\" type=\"password\" name=\"password\"/>";
echo "</label>";
echo "</div>";
echo "<input type=\"hidden\" name=\"page\" value=\"{$page}\"/>";
$_SESSION['csrfToken'] = $token = base64_encode( openssl_random_pseudo_bytes(32));
echo "<input type=\"hidden\" name=\"csrfToken\" value=\"{$token}\"/>";
echo "<div class=\"form-actions\">";
echo "<button type=\"submit\" class=\"btn btn-primary\">Get Data</button>";
echo "</div>";
echo "</form>";
?>