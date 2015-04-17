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
                        $s1 = preg_replace($p, $r[0], $s);
                        if ($s1 != $s)
                        {
                            //echo "replaced=$s=$s1={$prev->getContent()}={$prev->tokenName($prev->getType())}\n";
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

