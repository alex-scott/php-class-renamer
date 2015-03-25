<?php

namespace PhpClassRenamer\Action;

use PhpClassRenamer\TokenStream;
use PhpClassRenamer\Token;


class FixDocBlocks extends AbstractAction
{
    /** @var \PhpClassRenamer\TokenStream $stream */
    protected $stream;
    
    function process(TokenStream $stream, $inputFn, $outputFn, $pass = 0)
    {
        $this->stream = $stream;
        $it = 0;
        $ns = null;
        foreach ($this->stream->getTokens() as $i => $token)
        {
            switch ($token->getType())
            {
                case Token::T_NS_NAME:
                    $ns = $token->getContent();
                    break;
                case T_DOC_COMMENT:
                    $token->setContent(preg_replace_callback('#^\s*\*\s*(@param|@return)\s+(.+)$#m', array($this, '_rpl'), $token->getContent()));
                    break;
            }
        }
    }
    
    public function _rpl($matches)
    {
        \print_r($matches[2]);
    }
}

