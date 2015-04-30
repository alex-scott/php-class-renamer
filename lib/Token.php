<?php

namespace PhpClassRenamer;

class Token implements \ArrayAccess
{
    protected $type;
    protected $content;
    protected $line;
    
    static private $constants = array();
    
    const T_NONE = 10001;
    const T_STATIC_CALL = 10003;
    const T_CLASS_NAME = 10004;
    const T_EXTENDS_NAME = 10005;
    const T_CLASS_NEW = 10006;
    const T_USE_NS = 10008;
    const T_USE_AS = 10009;
    const T_NS_NAME = 10010;
    const T_INSTANCEOF_NAME = 10011;
    
    const T_LEFT_BRACKET = 10100; // (
    const T_RIGHT_BRACKET = 10101; // )
    const T_LEFT_BRACE = 10102; // {
    const T_RIGHT_BRACE = 10103; // }
    const T_SEMICOLON = 10104; // ;
    const T_COMMA = 10105; // ,
    const T_FUNCTION_ARG = 10106;
    
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
    function getName() { return self::tokenName($this->type); }
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
    
    static function tokenName($type)
    {
        if (empty(self::$constants))
        {
            $r = new \ReflectionClass(__CLASS__);
            foreach ($r->getConstants() as $k => $v)
            {
                self::$constants[$v] = $k;
            }
        }
        if ($type > 10000)
            return self::$constants[$type];
        else
            return token_name($type) == 'UNKNOWN' ? $type : token_name($type);
    }
}

