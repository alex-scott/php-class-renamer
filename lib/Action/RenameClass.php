<?php

namespace PhpClassRenamer\Action;

use PhpClassRenamer\TokenStream;
use PhpClassRenamer\Token;

class RenameClass extends AbstractAction
{
    public function process(TokenStream $stream, $inputFn, $outputFn, $pass = 0)
    {
        $currentNs = null;
        foreach ($stream->getTokens() as $k => & $token)
        {
            $ext = null;
            switch ($token->getType())
            {
                case Token::T_NS_NAME:
                    $currentNs = $token->getContent();
                    break;
                    
                case Token::T_CLASS_NAME:
                    $i = $stream->findNextToken(Token::T_EXTENDS_NAME, $k+1);
                    if ($i && (($i - $k) <= 6))
                    {
                        $ext = $stream->getTokenByNumber($i)->getContent();
                    }
                    
                    $new = $this->changer->replace(  $token->getContent() , $ext );
                    $token->setContent( $new );
                    break;
            }       
        }
    }
}
