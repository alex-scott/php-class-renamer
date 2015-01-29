<?php

namespace PhpClassRenamer;

class Token implements \ArrayAccess
{
    protected $type;
    protected $content;
    protected $line;
    
    function __construct($typeOrArr, $content = null, $line = null)
    {
        if (is_array($typeOrArr) && $content === null && $line === null)
        {
            list($typeOrArr, $content, $line) = $typeOrArr;
        }
        $this->type = $typeOrArr;
        $this->content = $content;
        $this->line = $line;
    }
    function getType() { return $this->type; }
    function getContent() { return $this->content; }
    function getLine() { return $this->line; }
    function setType($type) { $this->type = $type; }
    function setContent($content) { $this->content = $content; }
    
    /**
     * Return true if token type matches any argument
     */
    function is($type0, $type1=null, $type2=null)
    {
        if (is_array($type0))
            $types = $type0;
        else
            $types = func_get_args();
        return in_array($this->type, $types);
    }
    
    function offsetExists($i) { return $i == 0 || $i == 1 || $i == 2; }
    function offsetGet($i) 
    { 
        $x = debug_backtrace();
        print_r("call offsetGet from {$x[0]['line']}\n");
        switch ($i) {
            case 0: return $this->getType();
            case 1: return $this->getContent();
            case 2: return $this->getLine(); 
        }
        throw new Exception("Unknown element requested [$i]");
    }
    function offsetSet($i, $v)
    {
        $x = debug_backtrace();
        print_r("call offsetSet from {$x[0]['line']}\n");
        switch ($i)
        {
            case 0: $this->type = $v; return;
            case 1: $this->content = (string)$v; return;
            case 2: $this->line = $v; return;
        }
        throw new Exception("Unknown element requested [$i]");
    }
    function offsetUnset($i) 
    {
        throw new Exception("Impossible to remove element from Token!");
    }
    
    /**
     * @return Token
     */
    static function merge($newType, array $tokens)
    {
        if (!$tokens)
            throw new \Exception("No tokens to merge");
        $content = "";
        foreach ($tokens as $t)
        {
            $content .= $t->getContent();
            $line = $t->getLine();
        }
        return new Token($newType, $content, $line);
    }
}

