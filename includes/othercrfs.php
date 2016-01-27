<?php
if ( isset($_GET['centre']) && is_numeric($_GET['centre']) ) {
    write_search_table('all', false, false, $_GET['centre']);
} else {
    write_search_table('all', false, false);
}
?>
