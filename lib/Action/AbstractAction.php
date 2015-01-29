<?php

namespace PhpClassRenamer\Action;

use PhpClassRenamer\TokenStream;


abstract class AbstractAction
{
    /* @var $changer ClassNameChanger */
    protected $changer;
    function __construct(\PhpClassRenamer\ClassNameChanger $changer)
    {
        $this->changer = $changer;
    }
    function process(TokenStream $stream, $inputFn, $outputFn, $pass = 0)
    {
    }
}

