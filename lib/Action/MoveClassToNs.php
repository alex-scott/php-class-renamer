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
        return [ ltrim(implode('\\', $a), '\\') , $c ]; 
    }
    
    function insertNamespace($i, $ns)
    {
        $ii = $this->stream->findPrevToken([T_ABSTRACT, T_FINAL], $i, 6);
        if ($ii) $i = $ii - 1;
        $token = $this->stream->getTokenByNumber($i);
        $this->stream->replaceTokens($i+1, 0, array(
            new Token(T_NAMESPACE, 'namespace', $token->getLine()),
            new Token(Token::T_NONE, " ", $token->getLine()),
            new Token(Token::T_NS_NAME, $ns, $token->getLine()),
            new Token(Token::T_NONE, ";\n", $token->getLine()),
        ));
        $this->currentNs = $ns;
    }

    function process(TokenStream $stream, $inputFn, $outputFn, $pass = 0)
    {
        $this->stream = $stream;
        $it = $this->stream->findNextToken(Token::T_CLASS_NAME);
        if (!$it) return; // no class defs found
        $firstClass = $this->stream->getTokenByNumber($it)->getContent();
        list($ns, $cl) = $this->parseNsAndClass($firstClass);
        $i = $this->stream->findNextToken(T_OPEN_TAG, 0);
        if ($i === null)
            throw new \Exception("Cannot add namespace: may not find PHP open tag");
        if ($ns)
            $this->insertNamespace($i, $ns);
        $it += 4;
        /// find following classes
        $l = 0;
        $tokenPos = $this->stream->findTokenPositions(Token::T_CLASS_NAME, $it+4);
        $shift = 0;
        foreach ($tokenPos as $it => $token)
        {
            $it = $it + $shift;
            $class = $token->getContent();
            list($nns, $cl) = $this->parseNsAndClass($class);
            if ($nns && $nns != $ns)
            {
                $ns = $nns;
                $c1 = $this->stream->tokensCount();
                $this->insertNamespace($it - 3, $ns);
                $shift += $this->stream->tokensCount() - $c1;
            }
            if ($l++ > 1000) throw new \Exception("endless cycle?"); // endless cycle?
        };

        //// now as we inserted all namespaces, move on the file stream to simplify class names
        $it = 0;
        $currentNs = null;
        $tokenPos = $this->stream->findTokenPositions([
                Token::T_CLASS_NAME,
                Token::T_CLASS_NEW, Token::T_EXTENDS_NAME,
                Token::T_FUNCTION_ARG, Token::T_NS_NAME,
                Token::T_STATIC_CALL
                ]);
        foreach ($tokenPos as $it => $token)
        {
            if ($token->is(Token::T_NS_NAME))
            {
                $currentNs = $token->getContent();
            } else {
                $class = $token->getContent();
                if ($currentNs)
                {
                    $prefix = '\\' . $currentNs . '\\';
                    if (strpos($class, $prefix)===0)
                    {
                        $token->setContent(substr($class, strlen($prefix)));
                    } elseif ($currentNs
                        && !$token->is(Token::T_CLASS_NAME)
                        && ($class != 'parent')
                        && strpos($class, '\\')===false) {
                        { // we are in namespace, prefix all not-namespaced classes
                            $token->setContent('\\' . $class);
                        }
                    }
                }
            }
        }
    }
    
}

