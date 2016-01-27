<?php
echo "<p class=\"lead\">Download site data</p>";
echo "<form action=\"process.php\" method=\"POST\">";
echo "<label for=\"encrypted\">Download patient identifiable data? ";
echo "<input type=\"radio\" name=\"encrypted\" value=\"0\" checked=\"checked\"> No ";
echo "<input type=\"radio\" name=\"encrypted\" value=\"1\" > Yes ";
echo "</label>";
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
