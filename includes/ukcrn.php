<?php
echo "<p class=\"lead\">Download UKCRN Portfolio data</p>";
echo "<form action=\"process.php\" method=\"POST\">";
echo "<p>Click the button below to get a csv of the UKCRN data</p>";
echo "<input type=\"hidden\" name=\"page\" value=\"{$page}\"/>";
$_SESSION['csrfToken'] = $token = base64_encode( openssl_random_pseudo_bytes(32));
echo "<input type=\"hidden\" name=\"csrfToken\" value=\"{$token}\"/>";
echo "<div class=\"form-actions\">";
echo "<button type=\"submit\" class=\"btn btn-primary\">Get Data</button>";
echo "</div>";
echo "</form>";
?>