<?php

namespace PhpClassRenamer;

class FileProcessor
{
    protected $actions = array();
    protected $files = array();
    protected $warningHandler;
    
    function setWarningHandler(callable $func)
    {
        $this->warningHandler = $func;
    }
    
    function cleanActions()
    {
        $this->actions = array();
        return $this;
    }
    
    function cleanFiles()
    {
        $this->files = array();
        return $this;
    }
    
    function addAction(Action\AbstractAction $action, $pass = 0)
    {
        $this->actions[$pass][] = $action;
        return $action;
    }
    
    function addFile($inputFile, $outFile)
    {
        $this->files[$inputFile] = array(
            'in' => $inputFile,
            'out' => $outFile,
            'stream' => new TokenStream(file_get_contents($inputFile), $inputFile, $this->warningHandler),
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
                    if (!$action instanceof Action\ContentAction)
                        $action->process($rec['stream'], $rec['in'], $rec['out'], $pass);
                }
            }
        }
    }   
    
    function runContentActions($content, $fn = null)
    {
        foreach ($this->actions as $pass => $actions)
            foreach ($actions as $action)
            {
                if ($action instanceof Action\ContentAction)
                {
                    $content = $action->processContent($content, $fn);
                }
            }
        return $content;
    }
    
    function getFileContent($inputFn)
    {
        return $this->runContentActions($this->files[$inputFn]['stream']->getFileContent(),
            $inputFn);
    }
    
    function getFilenames()
    {
        return array_keys($this->files);
    }
    
    function getFilename($inputFn)
    {
        return $this->files[$inputFn]['stream']->getFilename();
    }
    
    /** @return \PhpClassRenamer\TokenStream */
    function getFileTokenStream($inputFn)
    {
        return $this->files[$inputFn]['stream'];
    }
    
    function storeFiles($outDir, $verbose = false)
    {
        $errors = array();
        foreach ($this->files as $inputFn => & $rec)
        {
//            if (preg_match('#\bRecord.php$#', $inputFn))
//                print_r($rec['stream']->dumpTokens(1));
//            
            $files = $rec['stream']->getFilesAndContent();
            foreach ($files as $fn => $content)
            {
                $content = $this->runContentActions($content, $inputFn);
                
                $fn = $outDir . DIRECTORY_SEPARATOR . $fn;
                $dir = dirname($fn);
                if (!file_exists($dir)) 
                    mkdir($dir, 0755, true);
                if ($verbose)
                    echo "Storing $fn\n";
                file_put_contents($fn, $content);
                $output = $exit = null;
                exec("/usr/bin/php -l " . escapeshellarg($fn) . " 2>&1", $output, $exit);
                if ($exit)
                    $errors[$fn] = $output;
            }
        }
        return $errors;
    }
    
    function storeFilesWithoutRename($outDir)
    {
        $errors = array();
        foreach ($this->files as $inputFn => & $rec)
        {
            $content = $rec['stream']->getFileContent();
            $content = $this->runContentActions($content, $inputFn);
            $fn = $rec['out'];
            $dir = dirname($fn);
            if (!file_exists($dir)) 
                mkdir($dir, 0755, true);
            file_put_contents($fn, $content);
            $output = $exit = null;
            exec("/usr/bin/php -l " . escapeshellarg($fn), $output, $exit);
            if ($exit)
                $errors[$fn] = $output;
        }
        return $errors;        
    }
    
    function processString($inputSource, $filename, $outFile)
    {
        return $this->runContentActions($stream->getFileContent(), $filename);
    }
}
