<?php
echo "<p class=\"lead\">Lock your site for data entry</p>";
$centre = new Centre($user->getCentre());
if ( !$trial->checkComplete('siteinfo',$centre) ) {
    echo "<p>";
    echo "Please go to Admin -> Site Information and complete the one-time hospital information form there, this must be done before you can submit your completed data.";
    echo "</p>";
} else {
    $sql = "SELECT count(link.id) AS numCRF
				FROM link 
					INNER JOIN core ON link.core_id = core.id 
					INNER JOIN centre ON core.centre_id = centre.id  
                WHERE centre.id = ?  
                    AND signed = 0";
    $pA = array('i',$centre->getID());
    $result = DB::cleanQuery($sql, $pA);
    if ( $result->numCRF > 0 ) {
        echo "<p>You have {$result->numCRF} CRFs submitted by your site that have not been signed off as complete and correct.  Please use the Worklist tabs above to find these CRFs and sign them off.</p>";
    } else {
        $sql = "SELECT count( link.id ) as numFlagged
				FROM link 
					INNER JOIN core ON link.core_id = core.id 
					INNER JOIN centre ON core.centre_id = centre.id 
                    LEFT JOIN flag ON link.id = flag.link_id 
                WHERE centre.id = ? 
                    AND signed = 1
                    AND (( link.comment IS NOT NULL AND link.comment != '' ) 
                    OR flag.id IS NOT NULL )";
        $pA = array('i',$centre->getID());
        $result = DB::query($sql, $pA);
        echo "<p>All the CRFs for your hospital have been signed";
        if ( $result->numFlagged ) {
            echo " (though {$result->numFlagged} still have flags suggesting incomplete or incorrect data)";
        }
        echo ". If all your patients have been entered, you can now lock your data.</p>";
        echo "<p>By clicking 'Agree and lock data', you are confirming that the 
            data for your hospital are complete and correct. Once you do this 
            your data will be locked and you will not be able to make further 
            changes. You will then be able to download and check your data on a 
            spreadsheet. If you identify any errors after your data have been 
            locked then we have an SOP for unlocking sites available 
                <a href=\"/docs/ISOSSiteUnlock.pdf\" target=\"_blank\">here</a>.
            You can contact us with any queries at 
                <a href=\"mailto:data@isos.org.uk?subject=Data unlocking enquiry\">data@isos.org.uk</a>.</p>";
        echo "<form action=\"process.php\" method=\"POST\">";
        echo "<input type=\"hidden\" name=\"lockSite\" value=\"1\"/>";
        echo "<input type=\"hidden\" name=\"page\" value=\"locksite\"/>";
        $_SESSION['csrfToken'] = $token = base64_encode( openssl_random_pseudo_bytes(32));
        echo "<input type=\"hidden\" name=\"csrfToken\" value=\"{$token}\"/>";
        echo "<div class=\"form-actions\">
            <button type=\"submit\" class=\"btn btn-primary\">Agree and Lock data</button>
            </div>";
        echo "</form>";
    }
}
?>
