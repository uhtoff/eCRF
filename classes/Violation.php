<?php

/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 07/01/2016
 * Time: 16:56
 */
class Violation extends Data
{
    public function __construct($id = NULL)
    {
        parent::__construct($id, 'Violation');
    }
    public function hasViolation()
    {
        $checkArray = array('no','low','stop','wrong');
        foreach( $checkArray as $type ) {
            if ( $this->{$type . 'cpap'} ) {
                return true;
            }
        }
        return false;
    }
    public function makeActive()
    {
        $this->active = true;
    }
    public function makeInactive()
    {
        $this->active = false;
    }
}