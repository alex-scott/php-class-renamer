<?php
trait TraitMe {}
class TestMe implements ArrayAccess, \Xx\Countable
{
    use TraitMe;
    public function __construct($totalPages, $currentPage=null, $urlTemplate=null, $pageVar = "p", Am_Request $request = null)
    {
    }
}

