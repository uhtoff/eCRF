<?php
$records = $trial->getAllRecords();
$startTarget = new DateTime('2016-02-02');
$centreArr = array();
$incompleteArr = array();
foreach ($records as $record) {
    $randDate = new DateTime($record->getRandomisationDate());
    if ($randDate < $startTarget || $record->getCentre() != $user->getCentre()) {
        continue;
    }
    if (count($trial->checkInterimComplete($record))!=0) {
        $incompleteArr[$record->getID()]['trialid'] = $record->getField('core','trialid');
        $incompleteArr[$record->getID()]['pages'] = $trial->checkInterimComplete($record);
    }
}

if( !empty($incompleteArr) ) {
    echo "<div class=\"container well\" style=\"background-color:#FFFFFF;\">";
    echo "<h3>$caption</h3>";
    echo "<p>Click on any heading to sort by that field.</p>";
    echo '<form class="nomand" action="process.php" method="post">';
    ob_start();
    echo '<table ';
    echo '" class="table table-striped table-bordered table-hover dataTable"><thead><tr><th scope="col">' . Config::get('idName') . '</th>';
    echo '<th scope="col">Incomplete pages</th><th scope="col">Action</th></tr></thead>';
    echo "<tbody>\n";
    foreach ($incompleteArr as $link_id => $incompleteRecord) {
        echo '<tr class="clickable"><td>' , HTML::clean( $incompleteRecord['trialid'] ) , '</td>';
        echo '<td><ul>';
        foreach ($incompleteRecord['pages'] as $page) {
            echo "<li>{$page}</li>";
        }
        echo '</ul></td>';
        echo '<td class="clickable">';
        $link_id = HTML::clean($link_id);
        echo '<input class="radio" type="radio" name="searchpt-link_id" value="', $link_id, '" />';
        echo '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
        echo '<select class="action-', $link_id, '" name="searchpt-action" disabled>';
        echo '<option>No action</option>';
        echo '<option value="data">Enter or view data</option>';
        echo '<option value="ae">Record an adverse event</option>';
        echo '<option value="withdraw">Withdraw a patient</option>';
        echo '<option value="violation">Record a protocol deviation</option>';
        echo '</select>';

        echo '</td></tr>';
        echo "\n";
    }
    echo '</tbody></table><p>';
    echo "<input type=\"hidden\" name=\"page\" value=\"searchpt\">";
    $_SESSION['csrfToken'] = $token = base64_encode( openssl_random_pseudo_bytes(32));
    echo "<input type=\"hidden\" name=\"csrfToken\" value=\"{$token}\"/>";
    echo "<div class=\"form-actions\">
            <button type=\"submit\" class=\"btn btn-primary\">Select</button>
            </div>";
    ob_end_flush();
    echo '</form>';
    echo "</div>";
} else {
    if ( isset( $none ) ) {
        echo "<h3>{$none}</h3>";
    } else {
        echo "<h3>No incomplete CRFs found.</h3>";
    }
}