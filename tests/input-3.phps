<?php
trait Am_Trait_Me {}
class Am_Test_Me implements ArrayAccess, \Xx\Countable
{
    use Am_Trait_Me;
    public function __construct($totalPages, $currentPage=null, $urlTemplate=null, $pageVar = "p", Am_Request $request = null)
    {
    }
}

