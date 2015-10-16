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
    protected $filename;
    
    protected $ignoreVariableClass = array();
    
    function __construct($source, $filename, $ignoreVariableClass = array())
    {
        $this->source = $source;
        $this->filename = $filename;
        $this->ignoreVariableClass = $ignoreVariableClass;
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
                    case '=' : $tt = Token::T_EQUALS; break;
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
                case Token::T_INSTANCE_OF;
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
                echo "Variable static call [$contento] at line $lineo : {$this->filename}\n";
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
            try {
                $it->seek($start);
            } catch (\OutOfBoundsException $e) {
                return;
                //throw new \Exception("Cannot seek to $start");
            }
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
    
    public function findPrevToken($types, $start=null, $limit=-1)
    {
        foreach (array_reverse($this->tokens, true) as $i => $token)
        {
            if ($start !== null && ($i != $start)) continue;
            $start = null; 
            if ($token->is($types))
                return $i;
            if (--$limit == 0) return;
        }
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
            case Token::T_EQUALS:
                if ($this->insideFunctionArgs && $this->state == Token::T_FUNCTION_ARG) {
                    $this->setState (Token::T_EQUALS, $content, $line);
                    break;
                }
            case Token::T_SEMICOLON:
                if ($this->state == T_NAMESPACE)
                {
                    $this->setState(0, $content, $line);
                } elseif ($this->state == T_NEW) {
                    if ($lastToken->is(T_STRING) && trim($lastToken->getContent()) == trim($this->outputBefore)) {
                        if ($lastToken->getContent() != 'self')
                            $lastToken->setType(Token::T_CLASS_NEW);
                    } else {
                        if (!in_array(trim($this->outputBefore), $this->ignoreVariableClass))
                            echo "NEW class creation for variable at $line : {$this->filename} [".$this->outputBefore."]\n";
                    }
                    $this->setState(0, $content, $line);
                }
                break;
            case Token::T_LEFT_BRACKET:
                if ($this->state == T_NEW)
                {
                    if ($lastToken->is(T_STRING) && trim($lastToken->getContent()) == trim($this->outputBefore)) {
                        if ($lastToken->getContent() != 'self')
                            $lastToken->setType(Token::T_CLASS_NEW);
                    } else {
                        if (!in_array(trim($this->outputBefore), $this->ignoreVariableClass))
                            echo "NEW class creation for variable at $line : {$this->filename}[".$this->outputBefore."]\n";
                    }
                    $this->setState(0, $content, $line);
                } elseif ($this->state == T_USE) {
                    $this->setState(0, $content, $line);
                } elseif ($this->state == T_FUNCTION) {
                    $this->setState(Token::T_FUNCTION_ARG, $content, $line);
                    $this->insideFunctionArgs = true;
                } elseif ($this->state == Token::T_AFTER_EXTENDS) {
                    $this->setState(0, $content, $line);
                }
                break;
            case Token::T_RIGHT_BRACKET:
                $this->insideFunctionArgs = false;
                if ($this->state == Token::T_FUNCTION_ARG)
                {
                    $this->setState(0, $content, $line);
                } elseif ($this->state == T_NEW) {
                    if ($lastToken->is(T_STRING) && trim($lastToken->getContent()) == trim($this->outputBefore)) {
                        if ($lastToken->getContent() != 'self')
                            $lastToken->setType(Token::T_CLASS_NEW);
                    } else {
                        if (!in_array(trim($this->outputBefore), $this->ignoreVariableClass))
                            echo "NEW class creation for variable at $line : {$this->filename}[".$this->outputBefore."]\n";
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
                    $this->setState(Token::T_AFTER_EXTENDS, $content, $line);
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
            case T_INSTANCEOF:
                $this->setState($type, $content, $line);
                break;
            case T_INTERFACE: // emulate T_CLASS
                $this->setState(T_CLASS, $content, $line);
                break;
            case T_IMPLEMENTS:
                $this->setState(T_EXTENDS, $content, $line);
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
    
    protected function endT_AFTER_EXTENDS($type, $content, $line)
    {
        $state = 0;
        $numbers = array();
        $currentNumber = 0;
        reset($this->tokensBefore);
        while (list($i, $token) = each($this->tokensBefore))
        {
            /* @var $token Token */
            switch ($state)
            {
                case 0:
                    if ($token->is(Token::T_EXTENDS_NAME))
                    {
                        $state = 1; continue; // after already parsed interface name
                    }
                break;
                case 1:
                    if ($token->is(Token::T_COMMA))
                    {
                        $state = 2; continue; // after comma
                    }
                    break;
                case 2:    
                    if ($token->is(T_STRING, T_NS_SEPARATOR))
                    {
                        $state = 3;
                        $numbers[$currentNumber][0] = $i;
                        $numbers[$currentNumber][1] = $i;
                        continue; // start collecting interface names
                    }
                    break;
                case 3:    
                    if (!$token->is(T_STRING, T_NS_SEPARATOR))
                    {
                        $state = 1; $currentNumber++; continue; 
                    } else {
                        $numbers[$currentNumber][1] = $i;
                    }
                    break;
            }
        }
        foreach ($numbers as $nn)
        {
            $this->replaceTokensToNewType ($nn[0], $nn[1], Token::T_EXTENDS_NAME);
        }
    }
    
    protected function endT_USE($type, $content, $line)
    {
        reset($this->tokensBefore);
        $start = null;
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
        if ($start !== null)
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
            if (!$start && $tok->is(T_STRING, T_NS_SEPARATOR))
            {
                $start = $end = $k;
                $content .= $tok->getContent();
                continue;
            } elseif ($start && !$tok->is(T_STRING, T_NS_SEPARATOR)) {
                break;
            } 
            if ($start) {
                $end = $k;
                $content .= $tok->getContent();
            }
        }
        if (!$start) return;
        //
        array_splice($this->tokens, $start, $end-$start+1, 
                array(new Token(Token::T_NS_NAME, $content, $tok->getLine())));
        
    }
    
    public function getTokens()
    {
        return $this->tokens;
    }
    
    public function endT_INSTANCEOF()
    {
        $no_whitespace = true;
        $name = '';
        $start = null;
        foreach ($this->tokensBefore as $i => $token)
        {
            if ($token->is(T_INSTANCEOF)) continue; // skip instanceof itself
            if ($token->is(T_WHITESPACE) && $no_whitespace) // skip first whitespace
            {
                $no_whitespace = false;
                continue;
            }
            if ($token->is(T_NS_SEPARATOR, T_STRING))
            {
                if (!$start) $start = $i;
                $name .= $token->getContent();
                $end = $i;
                continue;
            }
            break;
        }
        if ($start && $end)
            $this->replaceTokensToNewType($start, $end, Token::T_INSTANCEOF_NAME);
    }
    
    public function replaceTokens($offset, $length, array $replacement)
    {
        return array_splice($this->tokens, $offset, $length, $replacement);
    }

    public function getFileContent($startToken = null, $endToken = null)
    {
        $out = '';
        if ($startToken !== null)
            $arr = array_slice($this->tokens, $startToken, ($endToken !== null) ? $endToken-$startToken+1 : null);
        else
            $arr = $this->tokens;
        foreach ($arr as $token)
            $out .= $token->getContent();
        return $out;
    }
    
    public function dumpTokens($addNumbers = false)
    {
        $out = "";
        foreach ($this->tokens as $i=>$t)
        {
            if ($addNumbers)
                $out .= sprintf("%d\t%d:%s=%s=\n", 
                   $i, $t->getLine(), $t->getName(), str_replace("\n", "\\n", $t->getContent()));
            else
                $out .= sprintf("%d:%s=%s=\n", 
                   $t->getLine(), $t->getName(), str_replace("\n", "\\n", $t->getContent()));
        }
        return $out;
    }
    
    public function getFilename()
    {
        $ns = $class = null;
        foreach ($this->tokens as $token)
        {
            switch ($token->getType())
            {
                case Token::T_NS_NAME:
                    if (!$ns)
                        $ns = $token->getContent();
                    break;
                case Token::T_CLASS_NAME:
                    if (!$class)
                        $class = $token->getContent();
                    break;
            }
        }
        if (!$class) return;
        return $this->getFilenameForClass($class, $ns);
    }
    
    function getFilenameForClass($class, $ns = null)
    {
        if ($ns) $class = $ns . '\\' . $class;
        return str_replace('\\', '/', $class) . '.php';
    }
    
    public function getFilesAndContent()
    {
        $ret = array();
        
        $first = $currentNs = $ns = $class = null;
        
        $prevStop = 0;
        $prevTokens = array();
        foreach ($this->tokens as $i => $token)
        {
            $prevTokens[$i] = $token;
            switch ($token->getType())
            {
                case T_NAMESPACE:
                case T_CLASS:
                case T_INTERFACE:
                    if (!$first) 
                    {   $first = $i;
                        foreach (array_reverse($prevTokens, true) as $i => $tt)
                            if ($tt->is([
                                T_ABSTRACT, T_WHITESPACE, 
                                T_FINAL, T_DOC_COMMENT, T_COMMENT,
                                T_NAMESPACE, T_CLASS, T_INTERFACE]))
                                $first = $i;
                            else
                                break;
                        $prevTokens = array();
                    }
                    break;
                case Token::T_NS_NAME:
                    $currentNs = $token->getContent();
                    break;
                case Token::T_CLASS_NAME:
                    if ($class)
                    { // class has been already defined! add previous tokens to $ret
                        
                        $content = $this->getFileContent($prevStop, $first - 1);
                        if ($ns && !preg_match('#\bnamespace [A-Z]#ms', $content))
                            $content = "namespace $ns;\n\n" . $content;
                        if (count($ret))
                            $content = '<'. "?php\n" . $content; 
                        $ret[ $this->getFilenameForClass($class, $ns) ] = $content;
                        $prevStop = $first;
                        $first = null;
                    } else {
                        $first = null; // reset $first
                    }
                    $class = $token->getContent();
                    $ns = $currentNs;
                    break;
            }
        }
        if (!$class)
        {
            echo "Error: no class found in file {$this->filename}\n";
            return array();
        }
        
        $content = $this->getFileContent($prevStop);
        if ($ns && !preg_match('#\bnamespace [A-Z]#ms', $content))
            $content = "namespace $ns;\n\n" . $content;
        if (count($ret))
            $content = '<'. "?php\n" . $content; 
        $ret[ $this->getFilenameForClass($class, $ns) ] = $content;
        return $ret;
    }
    
}



