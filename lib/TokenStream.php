<?php

namespace PhpClassRenamer;

class TokenStream 
{
    protected $source;
    protected $tokens = array();
    protected $tokensBefore = array();
    protected $state = null;
    protected $output = '';
    protected $insideFunctionArgs = false;
    protected $constants = array();
    
    function __construct($source)
    {
        $this->source = $source;
        $this->tokenize();
    }
    
    // todo : docblocks? method to rename known class names 
    
    function tokenize()
    {
        $line = 0;
        foreach (token_get_all($this->source) as $t)
        {
            if (is_string($t)) {
                switch ($t)
                {
                    case '(' : $tt = Token::T_LEFT_BRACKET; break;
                    case ')' : $tt = Token::T_RIGHT_BRACKET; break;
                    case '{' : $tt = Token::T_LEFT_BRACE; break;
                    case '}' : $tt = Token::T_RIGHT_BRACE; break;
                    case ';' : $tt = Token::T_SEMICOLON; break;
                    case ',' : $tt = Token::T_COMMA; break;
                    default: $tt = 0;
                }
                $t = array($tt, $t, $line);
            } else
                $line = $t[2];
            //echo token_name($type) . "=$content=$line\n";
            $this->addToken(new Token($t));
        }
        $this->setState(null, null, $t[2]);
    }
    
    public function dumpFoundClassTokens()
    {
        foreach ($this->getTokens() as $t)
        {
            switch ($t->getType())
            {
                case Token::T_CLASS_NAME:
                case Token::T_EXTENDS_NAME:
                case Token::T_STATIC_CALL:
                case Token::T_CLASS_NEW:
                case Token::T_USE_NS:
                case Token::T_USE_AS:
                case Token::T_FUNCTION_ARG:
                case Token::T_NS_NAME:
                    echo $t->getName() . "=" . $t->getContent() . "=\n";
            }
        }
    }
    
    protected function parseDoubleColon($lastToken)
    {
        end($this->tokens);
        list($starto, $endo, $contento, $lineo) = $this->findTokensBack ([T_VARIABLE, T_OBJECT_OPERATOR, 
            T_NS_SEPARATOR, T_STRING]);
        end($this->tokens);
        list($start, $end, $content) = $this->findTokensBack([T_STRING, T_NS_SEPARATOR]);
        if ($start !== null)
        {
            if ($starto != $start)
                echo "Variable static call [$contento] at line $lineo\n";
            elseif ($content != 'self')
                $this->replaceTokensToNewType($start, $end, Token::T_STATIC_CALL);
        }
    }
    
    /**
     * Find tokens back from current position $this->tokens matching $types
     * return array($start, $end) or array(null, null) if no tokens found
     * $end includes last matching element
     */
    protected function findTokensBack(array $types, $startI = null)
    {
        $start = $end = $line = null;
        $content = '';
        do {
            $k = key($this->tokens);
            if (($startI !== null) && ($k == $startI))
                $startI = null;
            if ($startI === null)
            {
                $token = current($this->tokens);
                if ($token === false) break; // end of array
                if (!$token->is($types)) break;
                if ($end === null) $end = $start = $k;
                else $start = $k;
                if ($end) {
                    $content .= $token->getContent();
                    $line = $token->getLine();
                }
            } 
            prev($this->tokens);
        } while (key($this->tokens));
        return array($start, $end, $content, $line);
    }
    
    /**
     * 
     * @param int|array $types
     * @param int $start
     * @return int
     */
    public function findNextToken($types, $start=0)
    {
        $it = new \ArrayIterator($this->tokens);
        if ($start)
        {
            $it->seek($start);
            if ($it->key() !== $start) 
                throw new \Exception("Cannot seek to $start");
        }
        do 
        {
            $token = $it->current();
            $i = $it->key();
            if ($token->is($types))
                return $i;
            $it->next();
        } while ($it->valid());
    }
    
    /**
     * @param type $i
     * @return Token
     */
    public function getTokenByNumber($i)
    {
        return $this->tokens[$i];
    }
    
    protected function replaceTokensToNewType($start, $end, $newType)
    {
        for ($i=$start;$i<=$end;$i++)
            $toMerge[] = $this->tokens[$i];
        array_splice($this->tokens, $start, $end-$start+1, 
                array(Token::merge($newType, $toMerge))
        );
    }
    
    protected function addToken(Token $token)
    {
        $type = $token->getType();
        $content = $token->getContent();
        $line = $token->getLine();

        $this->outputBefore = $this->output;
        $this->output .= $content;
        end($this->tokens); $i = key($this->tokens);
        $lastToken = null;
        if ($this->tokens)
        {
            do {
                $lastToken = & $this->tokens[key($this->tokens)];
                prev($this->tokens);
            } while (($lastToken->getType() == T_WHITESPACE));
        }
        // here we set lastToken to last non-whitespace token
        switch ($type)
        {
            case T_DOUBLE_COLON:
                $this->parseDoubleColon($lastToken);
                break;
            case Token::T_COMMA:
                if ($this->insideFunctionArgs) {
                    $this->setState (Token::T_FUNCTION_ARG, $content, $line);
                };
                break;
            case Token::T_SEMICOLON:
                if ($this->state == T_NAMESPACE)
                {
                    $this->setState(0, $content, $line);
                } elseif ($this->state == T_NEW) {
                    if ($lastToken->is(T_STRING) && trim($lastToken->getContent()) == trim($this->outputBefore)) {
                        $lastToken->setType(Token::T_CLASS_NEW);
                    } else {
                        echo "NEW class creation for variable at $line [".$this->outputBefore."]\n";
                    }
                    $this->setState(0, $content, $line);
                }
                break;
            case Token::T_LEFT_BRACKET:
                if ($this->state == T_NEW)
                {
                    if ($lastToken->is(T_STRING) && trim($lastToken->getContent()) == trim($this->outputBefore)) {
                        $lastToken->setType(Token::T_CLASS_NEW);
                    } else {
                        echo "NEW class creation for variable at $line [".$this->outputBefore."]\n";
                    }
                    $this->setState(0, $content, $line);
                } elseif ($this->state == T_USE) {
                    $this->setState(0, $content, $line);
                } elseif ($this->state == T_FUNCTION) {
                    $this->setState(Token::T_FUNCTION_ARG, $content, $line);
                    $this->insideFunctionArgs = true;
                }
                break;
            case Token::T_RIGHT_BRACKET:
                $this->insideFunctionArgs = false;
                if ($this->state == Token::T_FUNCTION_ARG)
                {
                    $this->setState(0, $content, $line);
                } elseif ($this->state == T_NEW) {
                    if ($lastToken->is(T_STRING) && trim($lastToken->getContent()) == trim($this->outputBefore)) {
                        $lastToken->setType(Token::T_CLASS_NEW);
                    } else {
                        echo "NEW class creation for variable at $line [".$this->outputBefore."]\n";
                    }
                    $this->setState(0, $content, $line);
                }
                break;
            case T_STRING:
                if ($this->state == T_CLASS)
                {
                    $type = Token::T_CLASS_NAME;
                    $this->setState(0, $content, $line);
                } elseif ($this->state == T_EXTENDS) {
                    $type = Token::T_EXTENDS_NAME;
                    $this->setState(0, $content, $line);
                } elseif ($this->state == T_AS) {
                    $type = Token::T_USE_AS;
                    $this->setState(0, $content, $line);
                }
                break;
            case T_AS:
                if (!$lastToken->is(Token::T_USE_AS) && $this->state != T_USE)
                    break;
            case T_CLASS:
            case T_EXTENDS:
            case T_USE:
            case T_NEW:
            case T_NAMESPACE:
            case T_FUNCTION:
                $this->setState($type, $content, $line);
                break;
        }
        $this->tokens[] = new Token($type, $content, $line);
        end($this->tokens);
        $this->tokensBefore[key($this->tokens)] = & $this->tokens[key($this->tokens)];
    }
    
    protected function setState($type, $content, $line)
    {
        if ($this->state)
        {
            $m = Token::tokenName($this->state);
            if (method_exists($this, 'end' . $m))
                $this->{'end'. $m}($type, $content, $line);
            //else 
                //echo "no method " . 'end' . $m . "\n";
        }
        $this->output = '';
        $this->tokensBefore = array();
        $this->state = $type;
    }
    
    protected function endT_USE($type, $content, $line)
    {
        reset($this->tokensBefore);
        while (list($k,$tok) = each($this->tokensBefore))
            if ($tok->is(T_STRING, T_NS_SEPARATOR)) 
            {
                $end = $start = $k; break;
            }
        while (list($k,$tok) = each($this->tokensBefore))
            if ($tok->is(T_STRING, T_NS_SEPARATOR))
            {
                $end = $k;
            }
        $this->replaceTokensToNewType($start, $end, Token::T_USE_NS);
    }
    
    protected function endT_FUNCTION_ARG($type, $content, $line)
    {
        list($start, $end, $content, $line) = $this->findTokensBack([T_VARIABLE, T_WHITESPACE]);
        if (!$start) return;
        list($start, $end, $content, $line) = $this->findTokensBack([T_STRING, T_NS_SEPARATOR], $start-1);
        if ($content == 'array') return;
        if (!$content) return;
        $this->replaceTokensToNewType($start, $end, Token::T_FUNCTION_ARG);
    }
    
    protected function endT_NAMESPACE($type, $content, $line)
    {
        reset($this->tokensBefore);
        $content = ''; $start = null;
        foreach ($this->tokensBefore as $k => $tok)
        {
            // skip only whitespace and ( else failure
            if (!$start && in_array($tok[0], [T_STRING, T_NS_SEPARATOR]))
            {
                $start = $end = $k;
                $content .= $tok[1];
                continue;
            } elseif ($start && !in_array($tok[0], [T_STRING, T_NS_SEPARATOR])) {
                break;
            } 
            if ($start) {
                $end = $k;
                $content .= $tok[1];
            }
        }
        if (!$start) return;
        //
        array_splice($this->tokens, $start, $end-$start, 
                array(new Token(Token::T_NS_NAME, $content, $tok[2])));
        
    }
    
    public function getTokens()
    {
        return $this->tokens;
    }
    
    public function replaceTokens($offset, $length, array $replacement)
    {
        return array_splice($this->tokens, $offset, $length, $replacement);
    }

    public function getFileContent()
    {
        $out = '';
        foreach ($this->tokens as $token)
            $out .= $token->getContent();
        return $out;
    }
    
    public function dumpTokens()
    {
        $out = "";
        foreach ($this->tokens as $t)
        {
            $out .= sprintf("%d:%s=%s=\n", 
               $t->getLine(), $t->getName(), str_replace("\n", "\\n", $t->getContent()));
        }
        return $out;
    }
}



