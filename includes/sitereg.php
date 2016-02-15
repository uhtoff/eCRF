<?php
$showSearch = true;
if ( isset( $_POST['centreSelect'] ) && is_numeric( $_POST['centreSelect'] ) ) {
    $centreEdit = new Centre( $_POST['centreSelect'] );
    if ( $centreEdit->get('name') && ( $centreEdit->getCountry() == $user->getCountry() || $user->isCentralAdmin() ) ) {
        $showSearch = false;
        echo "<h4>Edit the centre below</h4>";
        $form = new HTMLForm( 'process.php', 'post' );
        $fields = $trial->getFormFields( $page );
        $form->processFields( $fields, $centreEdit );
        if ( $user->isCentralAdmin() ) {
            $sql = "SELECT units.number, units.name FROM units WHERE number IN ( SELECT number FROM formFields
                    LEFT JOIN units ON units.number=formFields.fieldname
                    GROUP BY units.number )
                    GROUP BY units.number
                    HAVING count(units.number)>1";
            $numbers = DB::cleanQuery( $sql );
            if ( $numbers->getRows() ) {
                $defUnits = $centreEdit->getUnits();
                foreach( $numbers->rows as $row ) {
                    $input = $form->addInput('select',"units[{$row->number}]");
                    $input->addLabel("Units for {$row->name}");
                    $sql = "SELECT id, unit FROM units
                WHERE number = ?
                ORDER BY unitorder";
                    $pA = array('s',$row->number);
                    $units = DB::cleanQuery($sql,$pA);
                    $options = array();
                    foreach( $units->rows as $unitRow ) {
                        $options[$unitRow->id] = $unitRow->unit;
                    }
                    $input->addOption($options, true);
                    if ( isset($defUnits[$row->number]) ) {
                        $input->addValue($defUnits[$row->number]['units_id']);
                    }
                    $input->setMand();
                }
            }
        } else {
            $form->disableInput('sitereg-language_code');
        }
        if ( isset( $_SESSION['inputErr'] ) ) { // If any errors then add them to the form
            $form->addErrors( $_SESSION['inputErr'] );
            unset( $_SESSION['inputErr'] );
        }
        $form->addInput( 'hidden', 'centreID', $centreEdit->getID() );
        $form->addInput( 'hidden', 'page', $page );
        $form->addInput( 'hidden', 'deleteCentre', 'false' );
        $form->addInput( 'hidden', 'regUsers', $centreEdit->getNumUsers() );
        $form->addInput( 'hidden', 'toggleLock', '0' );
        $_SESSION['csrfToken'] = $token = base64_encode( openssl_random_pseudo_bytes(32));
        $form->addInput( 'hidden', 'csrfToken', $token );
        if ( $user->isCentralAdmin() ) {
            $form->addButton( 'Delete site', array('btn-danger', 'hidden'));
            if ( $centreEdit->isLocked() ) {
                $form->addButton('Unlock site', array('btn-warning','hidden') );
            } else {
                $form->addButton( 'Lock site', array('btn-warning','hidden') );
            }
        }
        $form->addCancelButton( 'index.php?page=sitereg' );
        echo $form->writeHTML();
    }
} 
if ( $showSearch ) {
    $sql = "SELECT count(case when privilege_id <= 10 then 1 else NULL end) AS localAdmin,
                count(user.id) as regUsers,
                centre.id as centreID, centre.name as centreName, country.name as countryName, datalock FROM centre
            LEFT JOIN user ON centre_id = centre.id
            LEFT JOIN country ON country_id = country.id";            
    if ( $user->isRegional() ) {
        $sql .= " WHERE centre.country_id = ? GROUP BY centre.id";
        $pA = array('i', $user->getCountry());
        $userSearch = DB::cleanQuery($sql, $pA);
    } else {
        $sql .= " GROUP BY centre.id";
        $userSearch = DB::cleanQuery($sql);
    }
    if ( isset( $_GET['country'] ) && isset( $_GET['status'] ) ) {
        $showArr = array();
        switch( $_GET['status'] ) {
            case 1:
                $sql = "SELECT COUNT( user.id ) as numUsers, centre.id as id FROM centre LEFT JOIN user ON user.centre_id = centre.id LEFt JOIN country ON country.id = centre.country_id WHERE country.name = ? GROUP BY centre.id HAVING numUsers = 0";
                $pA = array('s',$_GET['country']);
                $centreSearch = DB::query($sql, $pA);
                $showArr = $centreSearch->getArray('id');
                break;
            case 2:
                $sql = "SELECT count(DISTINCT core.centre_id) AS numCRFs, centre.id as id FROM centre LEFT JOIN core ON core.centre_id = centre.id RIGHT JOIN user ON centre.id = user.centre_id LEFT JOIN country ON centre.country_id = country.id WHERE country.name = ? GROUP BY centre.id HAVING numCRFs = 0";
                $pA = array('s',$_GET['country']);
                $centreSearch = DB::query($sql, $pA);
                $showArr = $centreSearch->getArray('id');
                break;
            case 3:
                $sql = "SELECT count(DISTINCT centre_id) AS numCRFs, "
                    . "centre.id as id FROM core "
                    . "LEFT JOIN centre ON core.centre_id = centre.id "
                    . "LEFT JOIN country ON centre.country_id = country.id "
                    . "WHERE country.name = ? AND datalock = 0 GROUP BY centre.id";
                $pA = array('s',$_GET['country']);
                $centreSearch = DB::query($sql, $pA);
                $showArr = $centreSearch->getArray('id');
                break;
            case 4:
                $sql = "SELECT centre.id as id FROM centre "
                    . "LEFT JOIN country ON centre.country_id = country.id "
                    . "WHERE infolock = 1 AND datalock = 1 AND country.name = ?";
                $pA = array('s',$_GET['country']);
                $centreSearch = DB::query($sql, $pA);
                $showArr = $centreSearch->getArray('id');
                break;
        }
    }
    if( $userSearch->getRows() ) {
        
        if ( $user->isDataEntry() ) {
            echo "<h4>This is your centre</h4>";
        } else {
            echo "<h4>Please select a centre to edit its details -</h4>";
        }
        echo "<form method=\"POST\" action=\"index.php?page=sitereg\">";
        echo "<table class=\"table table-striped table-bordered table-hover dataTable\">";
        echo "<thead><tr><th>Centre Name</th><th>Centre ID</th><th>Country</th><th>#Entered</th><th>Users</th>";
        if ( !$user->isDataEntry() ) {
            echo "<th>Select</th>";
        }
        echo "</tr></thead>";
        echo "<tbody>";
        foreach( $userSearch->rows as $row ) {
            if ( !isset($showArr) || in_array( $row->centreID, $showArr ) ) {
                $sql = "SELECT count(core.id) as numCases FROM core WHERE centre_id = ?";
                $pA = array('i', $row->centreID );
                $num = DB::query($sql, $pA);
                echo "<tr class=\"clickable";
                if ( !$row->regUsers ) {
                    echo " error";
                } elseif ( !$row->datalock ) {
                    echo " success";
                } elseif ( !$row->localAdmin ) {
                    echo " warning";
                }
                echo "\"><td>{$row->centreName}</td>
                    <td>{$row->centreID}</td>
                    <td>{$row->countryName}</td>
                    <td>{$num->numCases}</td>
                    <td>{$row->regUsers}</td>";
                if ( !$user->isDataEntry() ) {
                    echo "<td class=\"clickable\"><input type=\"radio\" name=\"centreSelect\" value=\"{$row->centreID}\"></td>";
                }
                echo "</tr>";
            }
        }
        echo "</tbody></table>";
        echo "<div class=\"form-actions\">
            <button type=\"submit\" class=\"btn btn-primary\">Edit</button> ";
        if ( $user->isCentralAdmin() ) {
            echo "<button class=\"crfs btn btn-info\">Entered CRFs</button>";
        }
        echo "</div>";
        echo "</form>";
    }
}
?>
