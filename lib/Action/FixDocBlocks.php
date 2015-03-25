<?php

namespace PhpClassRenamer\Action;

use PhpClassRenamer\TokenStream;
use PhpClassRenamer\Token;


class FixDocBlocks extends AbstractAction
{
    /** @var \PhpClassRenamer\TokenStream $stream */
    protected $stream;
    protected $ns;
    
    function process(TokenStream $stream, $inputFn, $outputFn, $pass = 0)
    {
        $this->stream = $stream;
        $it = 0;
        $this->ns = null;
        foreach ($this->stream->getTokens() as $i => $token)
        {
            switch ($token->getType())
            {
                case Token::T_NS_NAME:
                    $this->ns = $token->getContent();
                    break;
                case T_DOC_COMMENT:
                    $token->setContent(preg_replace_callback('#(^\s*\*\s*(@var|@param|@return)\s+)([A-Za-z0-9_|]+)#m', array($this, '_rpl'), $token->getContent()));
                    break;
            }
        }
    }
    
    public function _rpl($matches)
    {
        $ss = $matches[3];
        $ss = preg_split('#\s*\|\s*#', $ss);
        $r = $this->changer->getRenames();
        foreach ($ss as $k => $s)
        {
            if (!empty($r[$s]))
            {
                $cl = $r[$s];
                if ($this->ns && (strpos($cl, '\\' . $this->ns) === 0))
                    if (strlen($cl) - strlen($this->ns) > 2)
                        $cl = substr($cl, strlen($this->ns) + 2);
                $ss[$k] = $cl;
            }
        }
        return $matches[1] . implode('|', $ss);
    }
}

