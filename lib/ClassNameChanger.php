<?php

namespace PhpClassRenamer;

class ClassNameChanger
{
    protected $fixed = array();
    protected $patterns = array();
    protected $spatterns = array();
    protected $toNs = array();
    protected $moveExtends = array();
    
    protected $renames = array();

    protected $errors = [];
    
    protected $callbacks = array();
    
    
    public function getRenames()
    {
        return $this->renames;
    }

    public function getSortedRenames()
    {
        $r = $this->renames;
        arsort($r);
        return $r;
    }
    
    function reset()
    {
        $this->fixed = array();
        $this->patterns = array();
        $this->toNs = array();
        $this->spatterns = array();
        return $this;
    }
    
    function addFixed($old, $new)
    {
        $this->fixed[$old] = $new;
        return $this;
    }
    function addPattern($old, $new)
    {
        $this->patterns[$old] = $new;
        return $this;
    }
    function addSimplePattern($old, $new)
    {
        $this->spatterns[$old] = $new;
        return $this;
    }
    function addToNs($prefix)
    {
        $this->toNs[] = $prefix;
        return $this;
    }
    function moveExtends($whatExtends, $targetNs, $exceptions = array(), $okPattern = '/.+/')
    {
        $this->moveExtends[$whatExtends] = array('targetNs' => $targetNs, 'exceptions' => $exceptions, 'okPattern' => $okPattern);
    }
    
    function addRename($from, $to)
    {
        $this->renames[$from] = $to;
    }
    function replace($class, $extends = null)
    {
        $class = trim($class);

        // if class starts with \ and hash underscore or known, we will handle it
        if ($class[0] == '\\')
        {
            $_c = substr($class, 1);
            if (isset($this->renames[$_c]))
                return $this->renames[$_c];
        }

        if (isset($this->renames[$class]))
            return $this->renames[$class];
        $orig = $class;
        
        foreach ($this->fixed as $k => $v)
            if ($class == $k)
                $class = $v;
            
        if ($extends)
        {
            foreach ($this->moveExtends as $k => $v)
            {
                if ((strpos($extends, $k)===0) 
                    && !in_array($class, $v['exceptions'])
                    && preg_match($v['okPattern'], $class))
                {
                    $class = $v['targetNs'] . $class;
                    break;
                }
            }
        }
            
        foreach ($this->patterns as $k => $v)
        {
            $class = preg_replace("#".$k."#X", $v, $class);
        }
        foreach ($this->spatterns as $k => $v)
        {
            if (strpos($class, $k)===0)
                $class = substr_replace($class, $v, 0, strlen($k));
        }
        foreach ($this->toNs as $k)
        {
            if (strpos($class, $k) === 0)
                $class = preg_replace('#(^|_)#', '\\', $class);
        }

        $cancel = false;
        foreach ($this->callbacks as $callback)
        {
            $ret = $callback($orig, $class, $extends, $cancel);
            if ($ret) 
                $class = $ret;
        }
        if (!$cancel)
            $this->renames[$orig] = $class;
        
        return $class;
    }
    
    function addCallback(callable $callback)
    {
        $this->callbacks[] = $callback;
    }
    function clearCallbacks()
    {
        $this->callbacks = array();
    }
}
