<?php


namespace PhpClassRenamer\Action;

use PhpClassRenamer\TokenStream;
use PhpClassRenamer\Token;

class RenameClassRefs extends AbstractAction
{
    protected $unchanged = array();
    
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
                    
                case Token::T_EXTENDS_NAME:
                case Token::T_STATIC_CALL:
                case Token::T_CLASS_NEW:
                case Token::T_FUNCTION_ARG:
                    $old = $token->getContent();
                    $new = $this->changer->replace ( $old);
                    if ($old != $new)
                    {
                        $token->setContent( $new);
                    } else {
                        if (preg_match('#^Am#', $old))
                            $this->reportUnchanged($old);
                    }
                    break;
            }       
        }
    }
    
    public function reportUnchanged($class)
    {
        $this->unchanged[$class] = true;
    }
    
    public function getUnchanged()
    {
        return array_keys($this->unchanged);
    }
}
