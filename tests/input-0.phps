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
        
    }
    
    function run(array $xx)
    {
        return null;
    }
}

class Am_Record_Xx extends Am_Record
{
    function run2(Am_Record $r, array $x, $y)
    {
        
    }
}