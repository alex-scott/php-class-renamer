<?php

namespace PhpClassRenamer\Action;

use PhpClassRenamer\TokenStream;


abstract class AbstractAction
{
    /* @var $changer ClassNameChanger */
    protected $changer;
    protected $callbacks = array();
    
    function __construct(\PhpClassRenamer\ClassNameChanger $changer)
    {
        $this->changer = $changer;
    }
    function process(TokenStream $stream, $inputFn, $outputFn, $pass = 0)
    {
    }
    function addCallback(callable $func)
    {
        $this->callbacks[] = $func;
    }
    function runCallbacks(\PhpClassRenamer\Token $token, $pos, $oldContent, & $newContent, \PhpClassRenamer\TokenStream $stream)
    {
        $cancel = false;
        foreach ($this->callbacks as $callback)
        {
            $callback($token, $pos, $oldContent, $newContent, $stream,  $cancel);
            if ($cancel)
                return false;
        }
        return true;
    }
}

