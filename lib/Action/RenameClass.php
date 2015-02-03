<?php

namespace PhpClassRenamer\Action;

use PhpClassRenamer\TokenStream;

class RenameClass extends AbstractAction
{
    public function process(TokenStream $stream, $inputFn, $outputFn, $pass = 0)
    {
        $currentNs = null;
        $tokens = $stream->getTokens();
        foreach ($tokens as & $token)
        {
            switch ($token->getType())
            {
                case Token::T_NS_NAME:
                    $currentNs = $token->getContent();
                    break;
                    
                case Token::T_CLASS_NAME:
                case Token::T_EXTENDS_NAME:
                case Token::T_STATIC_CALL:
                case Token::T_CLASS_NEW:
                case Token::T_CLASS_ARG:
                case Token::T_USE_NS:
                case Token::T_USE_AS:
                case Token::T_FUNCTION_ARG:
                    $token->setContent( $this->changer->replace($token->getContent()) );
                    break;
            }       
        }
        $stream->setTokens($tokens);
    }
}
