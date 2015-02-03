<?php

namespace PhpClassRenamer;

class TokenStream 
{
    protected $source;
    protected $tokens = array();
    protected $tokensBefore = array();
    protected $state = null;
    protected $output = '';

    protected $constants = array();
    
    function __construct($source)
    {
        $r = new \ReflectionClass($this);
        foreach ($r->getConstants() as $k => $v)
        {
            $this->constants[$v] = $k;
        }
        $this->source = $source;
        $this->tokenize();
    }
    
    const T_NONE = 10001;
    const T_STATIC_CALL = 10003;
    const T_CLASS_NAME = 10004;
    const T_EXTENDS_NAME = 10005;
    const T_CLASS_NEW = 10006;
    const T_CLASS_ARG = 10007;
    const T_USE_NS = 10008;
    const T_USE_AS = 10009;
    const T_NS_NAME = 10010;
    
    const T_LEFT_BRACKET = 10100; // (
    const T_RIGHT_BRACKET = 10101; // )
    const T_LEFT_BRACE = 10102; // {
    const T_RIGHT_BRACE = 10103; // }
    const T_SEMICOLON = 10104; // ;
    const T_COMMA = 10105; // ,
    const T_FUNCTION_ARG = 10106;
    
    // todo : docblocks? method to rename known class names 
    
    function tokenize()
    {
        $line = 0;
        foreach (token_get_all($this->source) as $t)
        {
            if (is_string($t)) {
                switch ($t)
                {
                    case '(' : $tt = self::T_LEFT_BRACKET; break;
                    case ')' : $tt = self::T_RIGHT_BRACKET; break;
                    case '{' : $tt = self::T_LEFT_BRACE; break;
                    case '}' : $tt = self::T_RIGHT_BRACE; break;
                    case ';' : $tt = self::T_SEMICOLON; break;
                    case ',' : $tt = self::T_COMMA; break;
                    default: $tt = 0;
                }
                $t = array($tt, $t, $line);
            }
            //echo token_name($type) . "=$content=$line\n";
            $this->addToken(new Token($t));
        }
        $this->setState(null, null, $t[2]);
        
        foreach ($this->getTokens() as $t)
        {
            switch ($t->getType())
            {
                case self::T_CLASS_NAME:
                case self::T_EXTENDS_NAME:
                case self::T_STATIC_CALL:
                case self::T_CLASS_NEW:
                case self::T_CLASS_ARG:
                case self::T_USE_NS:
                case self::T_USE_AS:
                case self::T_FUNCTION_ARG:
                case self::T_NS_NAME:
                    echo $this->constants[$t->getType()] . "=" . $t->getContent() . "=\n";
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
                $this->replaceTokensToNewType($start, $end, self::T_STATIC_CALL);
        }
    }
    
    /**
     * Find tokens back from current position $this->tokens matching $types
     * return array($start, $end) or array(null, null) if no tokens found
     * $end includes last matching element
     */
    protected function findTokensBack(array $types)
    {
        $start = $end = $line = null;
        $content = '';
        do {
            $k = key($this->tokens);
            $token = current($this->tokens);
            if ($token === false) break; // end of array
            if (!$token->is($types)) break;
            if ($end === null) $end = $start = $k;
            else $start = $k;
            if ($end) {
                $content .= $token->getContent();
                $line = $token->getLine();
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
        echo "start=$start\n";
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
        array_splice($this->tokens, $start+1, $end-$start+1, 
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
            case self::T_COMMA:
                if ($this->state == self::T_FUNCTION_ARG) {
                    $this->setState (self::T_FUNCTION_ARG, $content, $line);
                };
                break;
            case self::T_SEMICOLON:
                if ($this->state == T_NAMESPACE)
                {
                    $this->setState(0, $content, $line);
                    break;
                }
            case self::T_LEFT_BRACKET:
                if ($this->state == T_NEW)
                {
                    if ($lastToken->getType() == T_STRING && trim($lastToken->getContent()) == trim($this->outputBefore)) {
                        $lastToken->setType(self::T_CLASS_NEW);
                    } else {
                        echo "NEW class creation for variable at $line [".$this->outputBefore."]\n";
                    }
                    $this->setState(0, $content, $line);
                } elseif ($this->state == T_USE) {
                    $this->setState(0, $content, $line);
                } elseif ($this->state == T_FUNCTION) {
                    $this->setState(self::T_FUNCTION_ARG, $content, $line);
                }
                break;
            case self::T_RIGHT_BRACKET:
                if ($this->state == self::T_FUNCTION_ARG)
                    $this->setState(0, $content, $line);
                break;
            case T_STRING:
                if ($this->state == T_CLASS)
                {
                    $type = self::T_CLASS_NAME;
                    $this->setState(0, $content, $line);
                } elseif ($this->state == T_EXTENDS) {
                    $type = self::T_EXTENDS_NAME;
                    $this->setState(0, $content, $line);
                } elseif ($this->state == T_AS) {
                    $type = self::T_USE_AS;
                    $this->setState(0, $content, $line);
                }
                break;
            case T_AS:
                if (!$lastToken->is(self::T_USE_AS) && $this->state != T_USE)
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
            if ($this->state > 10000)
                $m = $this->constants[$this->state];
            else
                $m = token_name($this->state);
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
        $content = '';
        while (list($k,$tok) = each($this->tokensBefore))
            if ($tok->is(T_STRING)) 
            {
                $end = $start = $k; break;
            }
        while (list($k,$tok) = each($this->tokensBefore))
            if ($tok->is(T_STRING))
            {
                $end = $k;
            }
        
        $this->replaceTokensToNewType($start, $end, T_USE_NS);
    }
    
    protected function endT_FUNCTION_ARG($type, $content, $line)
    {
        reset($this->tokensBefore);
        $content = ''; $start = null;
        foreach ($this->tokensBefore as $k => $tok)
        {
            if (!$start && $tok->is(T_VARIABLE)) return; // no classname here!
            // skip only whitespace and ( else failure
            if (!$start && $tok->is(T_STRING, T_NS_SEPARATOR))
            {
                $start = $end = $k;
                $content .= $tok->getContent();
                continue;
            } elseif ($start && $tok->is(T_STRING, T_NS_SEPARATOR)) {
                break;
            } 
            if ($start) {
                $end = $k;
                $content .= $tok->getContent();
            }
        }
        if (!$start) return;
        $this->replaceTokensToNewType($start, $end, self::T_FUNCTION_ARG);
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
                array(new Token(self::T_NS_NAME, $content, $tok[2])));
        
    }
    
    public function getTokens()
    {
        return $this->tokens;
    }
    
    public function setTokens(array $tokens)
    {
        $this->tokens = $tokens;
    }

    public function getFileContent()
    {
        $out = '';
        foreach ($this->tokens as $token)
            $out .= $token->getContent();
        return $out;
    }
}



