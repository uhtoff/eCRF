<?php

/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 07/01/2016
 * Time: 16:56
 */
class AdverseEvent extends Data
{
    public function __construct($id = NULL)
    {
        parent::__construct($id, 'AdverseEvent');
    }
    public function hasAdverseEvent()
    {
        return true;
    }
    public function makeActive()
    {
        $this->active = true;
    }
    public function makeInactive()
    {
        $this->active = false;
    }
    public function isActive()
    {
        return $this->active;
    }
}