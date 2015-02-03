<?php

namespace PhpClassRenamer\Action;

use PhpClassRenamer\TokenStream;
use PhpClassRenamer\Token;


class MoveClassToNs extends AbstractAction
{
    /** @var \PhpClassRenamer\TokenStream $stream */
    protected $stream;
    
    function parseNsAndClass($className)
    {
        $a = explode('\\', $className);
        $c = array_pop($a);
        return [ implode('\\', $a) , $c ]; 
    }
    
    function insertNamespace($i, $ns)
    {
        $tokens = $this->stream->getTokens();
        $token = $tokens[$i];
        array_splice($tokens, $i+1, 0, array(
            new Token(T_NAMESPACE, 'namespace', $token->getLine()),
            new Token(TokenStream::T_NONE, " ", $token->getLine()),
            new Token(TokenStream::T_NS_NAME, $ns, $token->getLine()),
            new Token(TokenStream::T_NONE, ";\n", $token->getLine()),
        ));
        $this->stream->setTokens($tokens);
        $this->currentNs = $ns;
    }
    
    function process(TokenStream $stream, $inputFn, $outputFn, $pass = 0)
    {
        $this->stream = $stream;
        
        
        $it = $this->stream->findNextToken(TokenStream::T_CLASS_NAME);
        if (!$it) return; // no class defs found
        $firstClass = $this->stream->getTokenByNumber($it)->getContent();
        list($ns, $cl) = $this->parseNsAndClass($firstClass);
        $ns = ltrim($ns, '\\');
        $i = $this->stream->findNextToken(T_OPEN_TAG, 0);
        if ($i === null)
            throw new \Exception("Cannot add namespace: may not find PHP open tag");
        $this->insertNamespace($i, $ns);
        /// found following classes
        $l = 0;
        while ($it = $this->stream->findNextToken(TokenStream::T_CLASS_NAME, $it+4)) {
            $class = $this->stream->getTokenByNumber($it)->getContent();
            echo "$it=$class\n";
            if ($l++ > 10) return;
        };
        
    }
    
}

