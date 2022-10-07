<?php

namespace NLGen\Tests;

use NLGen\Generator;

class SimpleGenerator extends Generator
{
    protected function top($data)
    {
        switch($data){
        case "A": return $this->a(1);
        case "B": return $this->b(2,3);
        default: return $this->z(2);
        }
    }

    protected function a($a)
    {
        return "got: $a";
    }

    protected function b($b1, $b2)
    {
        return "got: $b1, $b2";
    }

    protected function z($z)
    {
        return "got: $z, then ".$this->a($z);
    }
}
    
