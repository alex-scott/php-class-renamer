<?php

/**
 * a test class
 * Am_Record
 */
class Am_Record 
{
    /*
     * 
     */
    function __construct(Am_Record_Xx $xx) {
        $b = new self;
        $c = new Am_Record();
        throw new Exception;
    }
    
    function run(array $xx)
    {
        $x = (new Am_Table_Xx);
        return null;
    }
}

class Am_Table_Xx { }

class Am_Record_Xx extends Am_Record
{
    function run2(Am_Record $r, array $x, $y, Iterator $it)
    {
        $c = new $this->x;
    }
}

class Aa extends Am_Table_Xx { }