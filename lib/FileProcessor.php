<?php

namespace PhpClassRenamer;

class FileProcessor
{
    protected $actions = array();
    
    protected $ignoreVariableClass = array();
    
    function addAction(Action\AbstractAction $action, $pass = 0)
    {
        $this->actions[$pass][] = $action;
        return $action;
    }
    
    function ignoreVariableClass($cl)
    {
        if (is_array($cl))
            $this->ignoreVariableClass = array_merge($this->ignoreVariableClass, $cl);
        else
            $this->ignoreVariableClass[] = $cl;
        return $this;
    }
    
    function addFile($inputFile, $outFile)
    {
        $this->files[$inputFile] = array(
            'in' => $inputFile,
            'out' => $outFile,
            'stream' => new TokenStream(file_get_contents($inputFile), $inputFile, $this->ignoreVariableClass),
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
    
    function getFilename($inputFn)
    {
        return $this->files[$inputFn]['stream']->getFilename();
    }
    
    function storeFiles($outDir)
    {
        $errors = array();
        foreach ($this->files as & $rec)
        {
            $files = $rec['stream']->getFilesAndContent();
            foreach ($files as $fn => $content)
            {
                $fn = $outDir . DIRECTORY_SEPARATOR . $fn;
                $dir = dirname($fn);
                if (!file_exists($dir)) 
                    mkdir($dir, 0755, true);
                file_put_contents($fn, $content);
                $output = $exit = null;
                exec("/usr/bin/php -l " . escapeshellarg($fn), $output, $exit);
                if ($exit)
                    $errors[$fn] = $output;
            }
        }
        return $errors;
    }
    
    function processString($inputSource, $filename, $outFile)
    {
        return $stream->getFileContent();
    }
}