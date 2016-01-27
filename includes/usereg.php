<?php
$showSearch = true;
if ( isset( $_POST['userSelect'] ) && is_numeric( $_POST['userSelect'] ) ) {
    $userEdit = new eCRFUser( $_POST['userSelect'] );
    if ( $userEdit->get('email') && $userEdit->getPrivilege() >= $user->getPrivilege() ) {
        $showSearch = false;
        echo "<h4>Edit the user's details below</h4>";
        $form = new HTMLForm( 'process.php', 'post' );
        $fields = $trial->getFormFields( $page );
        $form->processFields( $fields, $userEdit );
        if ( isset( $_SESSION['inputErr'] ) ) { // If any errors then add them to the form
            $form->addErrors( $_SESSION['inputErr'] );
            unset( $_SESSION['inputErr'] );
        }
        $centre = new Data( $userEdit->getCentre(), 'Centre' );
        $form->addInputValue( 'usereg-country', $centre->get('country_id'));
        $form->addInput( 'hidden', 'userID', $userEdit->getID() );
        $form->addInput( 'hidden', 'page', $page );
        $form->addInput( 'hidden', 'deleteUser', 'false' );
        $form->addButton( 'Delete', array('btn-danger', 'hidden'));
        $form->addCancelButton( 'index.php?page=usereg' );
        $_SESSION['csrfToken'] = $token = base64_encode( openssl_random_pseudo_bytes(32));
        $form->addInput( 'hidden', 'csrfToken', $token );
        echo $form->writeHTML();
    }
} 
if ( $showSearch ) {
    $sql = "SELECT *, user.id as userID, centre.name as centreName, country.name as countryName, privilege.name as privilegeName, privilege_id FROM user
        LEFT JOIN centre ON centre_id = centre.id
        LEFT JOIN country ON country_id = country.id
        LEFT JOIN privilege ON privilege_id = privilege.id";
    if ( $user->isLocal() ) {
        $sql .= " WHERE centre.id = ?";
        $pA = array('i', $user->getCentre());
        $userSearch = DB::cleanQuery($sql, $pA);
    } elseif ( $user->isRegional() ) {
        $sql .= " WHERE centre.country_id = ?";
        $pA = array('i', $user->getCountry());
        $userSearch = DB::cleanQuery($sql, $pA);
    } else {
        $userSearch = DB::cleanQuery($sql);
    }    
    if( $userSearch->getRows() ) {
        if ( $user->isDataEntry() ) {
            echo "<h4>These are the users currently registered at your centre</h4>";
        } else {
            echo "<h4>Please select a user to edit their details -</h4>";
        }
        echo "<form method=\"POST\" action=\"index.php?page=usereg\">";
        echo "<table class=\"table table-striped table-bordered table-hover dataTable\">";
        echo "<thead><tr><th>Name</th><th>Centre</th><th>Country</th><th>Privilege</th>";
        if ( !$user->isDataEntry() ) {
            echo "<th>Select</th>";
        }
        echo "</tr></thead>";
        echo "<tbody>";
        foreach( $userSearch->rows as $row ) {
            echo "<tr class=\"clickable\"><td>{$row->forename} {$row->surname}</td>
                <td>{$row->centreName}</td>
                <td>{$row->countryName}</td>
                <td>{$row->privilegeName}</td>";
            if ( !$user->isDataEntry() ) { 
                echo "<td";
                if ( $user->getPrivilege() <= $row->privilege_id ) echo " class=\"clickable\"><input type=\"radio\" name=\"userSelect\" value=\"{$row->userID}\"";
                echo "></td>";
            }
            echo "</tr>";
        }
        echo "</tbody></table>";
	echo "<div class=\"form-actions\">
		<button type=\"submit\" class=\"btn btn-primary\">Edit</button>

		</div>";
        echo "</form>";
    }
}
?>
