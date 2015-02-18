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
            'stream' => new TokenStream(file_get_contents($inputFile), $inputFile),
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
        //echo $rec['stream']->dumpTokens();
    }    
    
    function getFileContent($inputFn)
    {
        return $this->files[$inputFn]['stream']->getFileContent();
    }
    
    function getFilename($inputFn)
    {
        return $this->files[$inputFn]['stream']->getFilename();
    }
    
    function storeFiles($outDir)
    {
        foreach ($this->files as & $rec)
        {
            $fn = $rec['stream']->getFilename();
            if (!$fn)
                $fn = $rec['out'];
            else
                $fn = $outDir . '/' . $fn;
            $dir = dirname($fn);
            if (!file_exists($dir))
                mkdir($dir, 0777, true);
            file_put_contents($fn, $rec['stream']->getFileContent());
        }
    }
    
    function processString($inputSource, $filename, $outFile)
    {
        return $stream->getFileContent();
    }
}