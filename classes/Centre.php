<?php
/**
 * Description of Centre
 *
 * @author Russ
 */
class Centre extends DBObject {
    //put your code here
    public function isLocked() {
        return $this->datalock === 1;
    }
    public function getUnits()
    {
        $getCentreUnits = array();
        $sql = "SELECT centreUnits.id AS id, number, units_id, conversion FROM centreUnits
                LEFT JOIN units
                ON units_id = units.id
                WHERE centre_id = ?";
        $centre_id = $this->getID();
        $pA = array('s', $centre_id);
        $currUnits = DB::query($sql, $pA);
        foreach ($currUnits->rows as $row) {
            $getCentreUnits[$row->number] = array(
                'id' => $row->id,
                'number' => $row->number,
                'units_id' => $row->units_id,
                'conversion' => $row->conversion
            );
        }
        return $getCentreUnits;
    }
    public function getPIName() {
        $sql = "SELECT user.id as userID from user
              WHERE centre_id = ? AND privilege_id = 10
              ORDER BY id
              LIMIT 1";
        $pA = array('i',$this->getID());
        $result = DB::query($sql,$pA);
        foreach( $result->rows as $row ) {
            if ( $row->userID ) {
                $pi = new eCRFUser($row->userID);
                return $pi;
            }
        }

    }
    protected function getUsers() {
        $sql = "SELECT user.id as userID FROM user
            WHERE centre_id = ?";
        $pA = array('i',$this->getID());
        $result = DB::query($sql, $pA);
        return $result->getArray('userID');
    }
    public function getNumUsers() {
        $sql = "SELECT count(user.id) as regUsers FROM centre 
            LEFT JOIN user ON centre.id = user.centre_id
            WHERE centre.id = ?
            GROUP BY centre.id";
        $pA = array('i',$this->getID());
        $result = DB::query($sql, $pA);
        return $result->regUsers;
    }
    public function getCountry() {
        return $this->country_id;
    }
    public function deleteCentre() {
        $users = $this->getUsers();
        foreach ( $users as $user ) {
            $delUser = new eCRFUser($user);
            $delUser->deleteUser();
        }
        $this->deleteFromDB();
    }
    public function toggleLock() {
        $this->datalock = $this->datalock ? 0 : 1;
        $this->saveToDB();
    }
    public function lockSite() {
        $this->datalock = 1;
        $this->infolock = 1;
        $this->saveToDB();
    }
}

?>
