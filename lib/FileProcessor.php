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
        $stream = new TokenStream(file_get_contents($inputFile));
        $dir = dirname($outFile);
        if (!is_dir($dir))
            mkdir($dir);
        
        foreach ($this->actions as $pass => $actions)
        {
            foreach ($actions as $action)
            {
                $action->process($stream, $inputFile, $outFile, $pass);
            }
        }
        
        file_put_contents($outFile, $stream->getFileContent());
        
        return $this;
    }
}