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
                        if ($r[3] !== null)
                            if (preg_match($r[3], $outputFn))
                                continue;
                        $prev = $stream->getTokenByNumber($i-1);
                        if ($prev->is($r[1]) || in_array($prev->getContent(), $r[2]))
                            continue; // exclude !
                        if ($i < 2 ) continue;
                        
                        $pprev = $stream->getTokenByNumber($i-2);
                        if ($pprev && ($pprev->getType() == T_FUNCTION))
                            continue; // dirty hack! do not replace where function is defined!
                        $s1 = preg_replace($p, $r[0], $s);
                        if ($s1 != $s)
                        {
                            if ($this->runCallbacks($token, $i, $token->getContent(), $s1, $stream))
                                $token->setContent($s1);
                            //echo "replaced=$s=$s1={$prev->getContent()}={$prev->tokenName($prev->getType())}=$inputFn\n";
                        }
                    }
                    break;
            }
        }
    }
    
    function addPattern($pattern, $replacement, $excludeTypes=[], $excludeContent=[], $excludeFn = null)
    {
        $this->patterns[$pattern] = 
            array($replacement, $excludeTypes, $excludeContent, $excludeFn);
        return $this;
    }
}

