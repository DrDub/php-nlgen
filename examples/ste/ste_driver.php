<?php

/*
 * Copyright (c) 2011-2019 Pablo Ariel Duboue <pablo.duboue@gmail.com>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining 
 * a copy of this software and associated documentation files (the "Software"), 
 * to deal in the Software without restriction, including without limitation 
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, 
 * and/or sell copies of the Software, and to permit persons to whom the 
 * Software is furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included 
 * in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS 
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL 
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING 
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER 
 * DEALINGS IN THE SOFTWARE.
 * 
 */

global $argv,$argc;

require 'ste.php';

if($argc>1){
    if($argv[1] == "test") {
      foreach($examples as $example){
          print ucfirst($gen->generate(parse_deps($example))).".\n";
      }
    }else{
        // load file
        $lines = file($argv[1]);
        $deps = array();
        $current = "";
        foreach($lines as $line) {
            if($line == "\n"){
                $deps[] = $current;
                $current = "";
            }else{
                $current .= $line;
            }
        }
        foreach($deps as $task){
            print ucfirst($gen->generate(parse_deps($task))).".\n";
        }
    }
}
//print_r($gen->semantics());
