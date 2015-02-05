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
    
    function addFile($inputFile, $outFile)
    {
        $this->files[$inputFile] = array(
            'in' => $inputFile,
            'out' => $outFile,
            'stream' => new TokenStream(file_get_contents($inputFile)),
        );
    }
    
    function process()
    {
        foreach ($this->actions as $pass => $actions)
        {
            foreach ($this->files as & $rec)
            {
                foreach ($actions as $action)
                {
                    $action->process($rec['stream'], $rec['in'], $rec['out'], $pass);
                }
            }
        }
    }    
    
    function getFileContent($inputFn)
    {
        return $this->files[$inputFn]['stream']->getFileContent();
    }
    
    function storeFiles()
    {
        foreach ($this->files as & $rec)
        {
            $dir = dirname($rec['out']);
            if (!file_exists($dir))
                mkdir($dir, 0777, true);
            file_put_contents($rec['out'], $rec['stream']->getFileContent());
        }
    }
    
    function processString($inputSource, $filename, $outFile)
    {
        return $stream->getFileContent();
    }
}