<?php

class Xx extends Yy
{
    function x()
    {
        return defined('AM_ADMIN') &&  AM_ADMIN;
    }
}