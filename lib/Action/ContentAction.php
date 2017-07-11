<?php
namespace PhpClassRenamer\Action;

/* 
 * Replace content in output file (based on regex, etc.)
 * Run when going to put the file
 */
class ContentAction extends AbstractAction
{
    protected $regexes = array();
    protected $replaces = array();
    
    function addRegex($search, $replace)
    {
        $this->regexes[] = array($search, $replace);
        return $this;
    }
    function addReplace($search, $replace)
    {
        $this->replaces[] = array($search, $replace);
        return $this;
    }
    
    function processContent($content, $fn = null)
    {
        foreach ($this->replaces as $r)
            $content = str_replace($r[0], $r[1], $content);
        foreach ($this->regexes as $r)
            $content = preg_replace($r[0], $r[1], $content);
        return $content;
    }
}
