<?php

namespace PhpClassRenamer\Action;

use PhpClassRenamer\TokenStream;
use PhpClassRenamer\Token;


class FixStringClassNames extends AbstractAction
{
    /** @var \PhpClassRenamer\TokenStream $stream */
    protected $stream;
    protected $ns;
    
    protected $rpl = array();
    protected $staticRpl = array();
    
    function process(TokenStream $stream, $inputFn, $outputFn, $pass = 0)
    {
        $this->stream = $stream;
        $it = 0;
        $this->ns = null;
        $replaced = $this->changer->getRenames();
        $this->inputFn = $inputFn;
        foreach ($this->stream->getTokens() as $i => $token)
        {
            $newContent = null;
            switch ($token->getType())
            {
                case Token::T_NS_NAME:
                    //$this->ns = $token->getContent();
                    break;
                case T_CONSTANT_ENCAPSED_STRING:
                    $ss = $token->getContent();
                    $s = substr($ss, 1, -1);
                    $count = 0; $xx = 0;
                    $this->line = $token->getLine();
                    if (!empty($replaced[$s]))
                    {
                        $s = str_replace('\\', '\\\\', $replaced[$s]);
                        $newContent = $ss[0] . $s . substr($ss, -1, 1);
                    } elseif (!empty($this->staticRpl[$s])) {
                        $newContent = $ss[0] . $s . substr($ss, -1, 1);
                    } elseif ($s = preg_replace_callback(
                            $xx = '#^('. implode('|', $this->rpl) .')[A-Z][A-Za-z0-9_]+#', 
                            array($this, '_rpl'), $s, -1, $count))
                    {
                        if ($count)
                            $newContent = $ss[0] . $s . substr($ss, -1, 1);
                    }
                    if ($newContent)
                        if ($this->runCallbacks($token, $i, $token->getContent(), $newContent, $stream))
                            $token->setContent($newContent);
                    break;
            }
        }
    }
    
    function replaceStringClassStartingWith($rpl)
    {
        $this->rpl[] = $rpl;
        return $this;
    }
    
    function staticReplace($from, $to)
    {
        $this->staticRpl[$from] = $to;
        return $this;
    }
    
    function _rpl($matches)
    {
        $class = $matches[0];
        
        foreach ($this->changer->getRenames() as $k => $v)
        {
            if (strpos($k, $class) === 0)
            {
                $lenDiff = strlen($k) - strlen($class);
                return str_replace('\\', '\\\\', substr($v, 0, -$lenDiff)); 
            }
        }
        echo "Cannot find replacement for string class name [$class] in {$this->inputFn}:{$this->line}\n";
        return $class;
    }
}

