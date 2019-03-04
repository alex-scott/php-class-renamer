<?php

namespace PhpClassRenamer\Action;

use PhpClassRenamer\TokenStream;
use PhpClassRenamer\Token;

class ReplaceUseTraits extends AbstractAction
{
    protected $unchanged = array();
    
    public function process(TokenStream $stream, $inputFn, $outputFn, $pass = 0)
    {
        $inUse = 0;
        foreach ($stream->getTokens() as $k => & $token)
        {
            $ext = null;
            switch ($token->getType())
            {
                case T_USE:
                    $inUse = 1;
                    break;
                case T_WHITESPACE: 
                    break;

                default:
                    if ($inUse) 
                    {
                        $trait = $token->getContent();
                        if (in_array($trait, $stream->getTraits()))
                        {
                            $token->setContent($xx = $this->changer->replace($trait));
                        }
                        $inUse = 0;
                    }
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
