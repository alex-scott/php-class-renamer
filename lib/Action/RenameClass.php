<?php

namespace PhpClassRenamer\Action;

use PhpClassRenamer\TokenStream;
use PhpClassRenamer\Token;

class RenameClass extends AbstractAction
{
    public function process(TokenStream $stream, $inputFn, $outputFn, $pass = 0)
    {
        $currentNs = null;

        $tokenPos = $stream->findTokenPositions([Token::T_NS_NAME, Token::T_CLASS_NAME]);
        $extendsPos = $stream->findTokenPositions([Token::T_EXTENDS_NAME]);
        foreach ($tokenPos as $k => $token)
        {
            $ext = null;
            switch ($token->getType())
            {
                case Token::T_NS_NAME:
                    $currentNs = $token->getContent();
                    break;
                    
                case Token::T_CLASS_NAME:
                    $ext = null;
                    foreach ($extendsPos as $ek => $extToken)
                    {
                        if ($ek > $k && $ek < ($k+6))
                            $ext = $extToken->getContent();
                    }

                    $new = $this->changer->replace(  $token->getContent() , $ext );
                    $token->setContent( $new );
                    break;
            }       
        }
    }
}
