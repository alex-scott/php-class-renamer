<?php
namespace PhpClassRenamer\Action;

/* 
 * Replace content in output file (based on regex, etc.)
 * Run when going to put the file
 */
class ContentAction extends AbstractAction
{
    protected $regexes = array();
    
    function addRegex($search, $replace)
    {
        $this->regexes[] = array($search, $replace);
    }
    
    function processContent($content, $fn = null)
    {
        foreach ($this->regexes as $r)
            $content = preg_replace($r[0], $r[1], $content);
        return $content;
    }
}
