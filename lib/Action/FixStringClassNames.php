<?php

namespace PhpClassRenamer\Action;

use PhpClassRenamer\TokenStream;
use PhpClassRenamer\Token;


class FixStringClassNames extends AbstractAction
{
    /** @var \PhpClassRenamer\TokenStream $stream */
    protected $stream;
    protected $ns;
    
    protected $rpl = array();
    protected $staticRpl = array();
    
    function process(TokenStream $stream, $inputFn, $outputFn, $pass = 0)
    {
        $this->stream = $stream;
        $it = 0;
        $this->ns = null;
        $this->inputFn = $inputFn;
        $this->ptokens = [];
        $this->sortedRenames = $this->changer->getSortedRenames(); // in reverse order - longer first
        $replaced = $this->sortedRenames;
        foreach ($replaced as $k => & $newClass)
            if ($newClass[0] == '\\') $newClass = substr($newClass, 1); // strip leading \\
        foreach ($this->stream->getTokens() as $i => $token)
        {
            $newContent = null;
            switch ($token->getType())
            {
                case Token::T_NS_NAME:
                    //$this->ns = $token->getContent();
                    break;
                case T_CONSTANT_ENCAPSED_STRING:
                    $ss = $token->getContent();
                    $s = substr($ss, 1, -1);
                    $count = 0; $xx = 0;
                    $this->line = $token->getLine();
                    $skip = false;
                    $ruleNum = 0;
                    if (!empty($replaced[$s]))
                    {
                        foreach ($this->ptokens as $ptoken)
                        {
                            if ($ptoken->getType() == T_STRING && preg_match('#(___|__hp|__hpe|__e)$#', $ptoken->getContent())) // amember-specific
                                $skip = true; // this classname string is translated so we should not namespace it
                            if ($ptoken->getType() == T_DOUBLE_ARROW && in_array($s, ['State', 'Country']))
                                $skip = true; // do not replace State/Country to classname in arrays - that is commonly used
                        }
                        if (!$skip)
                        {
                            $s = str_replace('\\', '\\\\', $replaced[$s]);
                            $newContent = $ss[0] . $s . substr($ss, -1, 1);
                            $ruleNum = 1;
                        }
                    } elseif (!empty($this->staticRpl[$s])) {
                        $newContent = $ss[0] . $s . substr($ss, -1, 1);
                        $ruleNum = 2;
                    } elseif ($s = preg_replace_callback(
                            $xx = '#^('. implode('|', $this->rpl) .')[A-Z][A-Za-z0-9_%]+$#',
                            array($this, '_rpl'), $s, -1, $count))
                    {
                        if ($count)
                        {
                            $newContent = $ss[0].$s.substr($ss, -1, 1);
                            $ruleNum = 3;
                        }
                    }
//                    if ($newContent)
//                    {
//                        echo "REPLSTR[$ruleNum]:\t" . $token->getContent() . " => " . $newContent . "\n";
//                    }
                    if ($newContent)
                        if ($this->runCallbacks($token, $i, $token->getContent(), $newContent, $stream))
                            $token->setContent($newContent);
                    break;
            }
            if (count($this->ptokens)>5)
                array_shift($this->ptokens);
            array_push($this->ptokens, $token);
        }
    }
    
    function replaceStringClassStartingWith($rpl)
    {
        $this->rpl[] = $rpl;
        return $this;
    }
    
    function staticReplace($from, $to)
    {
        $this->staticRpl[$from] = $to;
        return $this;
    }
    
    function _rpl($matches)
    {
        $string = $matches[0];
        foreach ($this->sortedRenames as $k => $v)
        {
            if (strpos($k, $string) === 0)
            {
                $origV = $v;
                if ($v[0] == '\\')
                    $v = substr($v, 1); // in strings we do not need leading \ slash
                if ($string != $k)
                {
//                    $v = substr($v, 0, strlen($string));
//                    $lastChar = substr($string, -1);
//                    if ($lastChar == '_') $lastChar = '\\';
//                    if ($lastChar != substr($v, -1))
//                    {
//                        throw new \Exception( "ERROR IN STRING CLASS REPLACE: GOING TO REPLACE [$string] => [$v] but seems it is wrong!");
//                    }
                    // make replacement shorter same way as $string shorter than $k
                    $lenDiff = strlen($k) - strlen($string);
                    $v = substr($v, 0, - $lenDiff);

                    if (!$this->classNameEqualsOrLastWordEquals($string, $v))
                        throw new \Exception("Cannot handle string class replacement, calculated [$string] => [$v] but it looks wrong!\nPlease add FixStringClassNames->staticReplace() for it");
                    //echo "COMPLEX STRRPL: string=[$string] k=[$k] origV=[$origV] [$string] => $v\n";
                }
                return str_replace('\\', '\\\\', $v);
                //$lenDiff = strlen($k) - strlen($string);
                //return str_replace('\\', '\\\\', substr($v, 0, -$lenDiff));
            }
        }
        $this->stream->addError("Cannot find replacement for string string name [$string] in {$this->inputFn}:{$this->line}",
            $this->inputFn, $this->line, 'str-string-name-replacement');
        return $string;
    }

    function classNameEqualsOrLastWordEquals($c1, $c2)
    {
        [$c1, $c2] = preg_replace('#^\\\\#', '', [$c1, $c2]);
        [$c1, $c2] = preg_replace('#\\\\#', '_', [$c1, $c2]);
        $lw1 = array_filter(explode('_', $c1));
        $lw2 = array_filter(explode('_', $c2));
        $lww1 = array_pop($lw1);
        $lww2 = array_pop($lw2);
        return ($c1 === $c2) || ($lww1 === $lww2);
    }
}

