<?php

namespace PhpClassRenamer\Action;

use PhpClassRenamer\TokenStream;


class MoveClassToNs extends AbstractAction
{
    function parseNsAndClass($className)
    {
        $a = explode('\\', $className);
        $c = array_pop($a);
        return [ implode('\\', $a) , $c ]; 
    }
    function process(TokenStream $stream, $inputFn, $outputFn, $pass = 0)
    {
        $firstClass = null;
        foreach ($stream->getTokens() as $token)
        {
            if ($token[0] == TokenStream::T_CLASS_NAME)
            {
                $firstClass = $token[1];
                break;
            }
        }
        if (!$firstClass) return; // no class definitions found
        list($ns, $cl) = $this->parseNsAndClass($firstClass);
        
        $tokens = $stream->getTokens();
        reset($tokens);
        while (list($i, $token) = each($tokens))
        {
            if (($token[0] == T_OPEN_TAG))
            {
                array_splice($tokens, $i+2, 0, array(
                    array(T_NAMESPACE, 'namespace', $token[2]),
                    array(TokenStream::T_NONE, " ", $token[2]),
                    array(TokenStream::T_NS_NAME, $ns, $token[2]),
                    array(TokenStream::T_NONE, ";\n", $token[2]),
                ));
            }
        }
        $stream->setTokens($tokens);
    }
}

