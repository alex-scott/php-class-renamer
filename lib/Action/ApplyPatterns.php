<?php

namespace PhpClassRenamer\Action;

use PhpClassRenamer\TokenStream;
use PhpClassRenamer\Token;


class ApplyPatterns extends AbstractAction
{
    protected $patterns = array();
    
    function process(TokenStream $stream, $inputFn, $outputFn, $pass = 0)
    {
        foreach ($stream->getTokens() as $i => $token)
        {
            switch ($token->getType())
            {
                case T_STRING:
                    $s = $token->getContent();
                    foreach ($this->patterns as $p => $r)
                    {
                        $prev = $stream->getTokenByNumber($i-1);
                        if ($prev->is($r[1]) || in_array($prev->getContent(), $r[2]))
                            continue; // exclude !
                        $pprev = $stream->getTokenByNumber($i-2);
                        if ($pprev->getType() == T_FUNCTION)
                            continue; // dirty hack! do not replace where function is defined!
                        $s1 = preg_replace($p, $r[0], $s);
                        if ($s1 != $s)
                        {
                            //echo "replaced=$s=$s1={$prev->getContent()}={$prev->tokenName($prev->getType())}=$inputFn\n";
                            $token->setContent($s1);
                        }
                    }
                    break;
            }
        }
    }
    
    function addPattern($pattern, $replacement, $excludeTypes=[], $excludeContent=[])
    {
        $this->patterns[$pattern] = 
            array($replacement, $excludeTypes, $excludeContent);
        return $this;
    }
}

