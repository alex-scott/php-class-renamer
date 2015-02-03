<?php

namespace PhpClassRenamer;

class ClassNameChanger
{
    protected $fixed = array();
    protected $patterns = array();
    protected $spatterns = array();
    protected $toNs = array();
    
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
    function replace($class, $extends = null)
    {
        foreach ($this->fixed as $k => $v)
            if ($class == $k)
                $class = $v;
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
        return $class;
    }
}
