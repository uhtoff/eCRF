<?php
//$test = true;
//
//if ( !$test || $_SESSION['user']->getID() == 11 ) {
//	echo '<form action="process.php" method="POST">';
//    echo "<input type=\"hidden\" name=\"page\" value=\"dldb\">";
//	echo '<p>Please select which fields you\'d like to download.</p>';
//	
//    $sql = "SELECT formFields.id AS fieldID, labelText, pages_name, 
//                pages.label as pageLabel, fieldName, formFields.type, rule1 
//            FROM formFields 
//                LEFT JOIN pages ON pages.name = pages_name 
//            WHERE pages.dataName = 'Record' 
//                AND formFields.type NOT IN ('heading','data') 
//            ORDER BY pageOrder, entryorder";
//    $fields = DB::query($sql);
//    $currentPage = "";
//    $newPage = false;
//    $counter = 0;
//    echo "<ul class=\"checklist\">";
//    foreach( $fields->rows as $row ) {
//        if ( $currentPage != $row->pageLabel ) {
//            $currentPage = $row->pageLabel;
//            $newPage = true;
//        }
//        if ( $newPage ) {
//            // If not the first newPage then close out the page ul
//            if ( $counter != 0 ) {
//                echo "</ul>";
//            }
//            $counter++;
//            echo "<li><label class=\"checkbox\"><input type=\"checkbox\" class=\"master group{$counter}\" checked=\"checked\"/> {$currentPage} <i data-toggle=\"collapse\" href=\"#collapse{$counter}\" class=\"list-toggle icon-expand\"></i></label></li>";
//            echo "<ul id=\"collapse{$counter}\" class=\"collapse\">";
//            
//            $newPage = false;
//        }
//        echo "<li><label class=\"checkbox\"><input type=\"checkbox\" name=\"fields[]\" value=\"{$row->fieldID}\" class=\"group{$counter}\" checked=\"checked\"/> {$row->labelText}</label></li>";
//    }
//    echo "</ul>";
//    echo "</ul>";
//    HTML::submit( 'Download' );
//	echo '</form>';
//} else {
//	echo '<h2>Downloads currently disabled</h2>';
//}
echo "<p class=\"lead\">Download all data</p>";
echo "<form action=\"process.php\" method=\"POST\">";
echo "<label for=\"encrypted\">Download patient identifiable data? ";
echo "<input type=\"radio\" name=\"encrypted\" value=\"0\" checked=\"checked\"> No ";
echo "<input type=\"radio\" name=\"encrypted\" value=\"1\" > Yes ";
echo "</label>";
echo "<p><a href=\"/docs/Key.xlsx\">Download data dictionary</a></p>";
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