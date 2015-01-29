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
                case TokenStream::T_NS_NAME:
                    $currentNs = $token->getContent();
                    break;
                    
                case TokenStream::T_CLASS_NAME:
                case TokenStream::T_EXTENDS_NAME:
                case TokenStream::T_STATIC_CALL:
                case TokenStream::T_CLASS_NEW:
                case TokenStream::T_CLASS_ARG:
                case TokenStream::T_USE_NS:
                case TokenStream::T_USE_AS:
                case TokenStream::T_FUNCTION_ARG:
                    $token->setContent( $this->changer->replace($token->getContent()) );
                    break;
            }       
        }
        $stream->setTokens($tokens);
    }
}
