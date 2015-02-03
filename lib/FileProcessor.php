<?php

namespace PhpClassRenamer;

class FileProcessor
{
    protected $actions = array();
    
    function addAction(Action\AbstractAction $action, $pass = 0)
    {
        $this->actions[$pass][] = $action;
        return $this;
    }
    
    function processFile($inputFile, $outFile)
    {
        $out = $this->process(file_get_contents($inputFile), $inputFile, $outFile);
        $dir = dirname($outFile);
        if (!is_dir($dir))
            mkdir($dir);
        file_put_contents($outFile, $out);
        return $this;
    }
    
    function process($inputSource, $filename, $outFile)
    {
        $stream = new TokenStream($inputSource);
        foreach ($this->actions as $pass => $actions)
        {
            foreach ($actions as $action)
            {
                $action->process($stream, $filename, $outFile, $pass);
            }
        }
        return $stream->getFileContent();
    }
}