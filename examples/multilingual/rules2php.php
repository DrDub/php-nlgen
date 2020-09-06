<?php

/*
 * Copyright (c) 2011-2020 Pablo Ariel Duboue <pablo.duboue@gmail.com>
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

require __DIR__ . '/vendor/autoload.php';

echo '<?php' . "\n";

$rules = array();
$current = "";
while (($line = fgets(STDIN)) !== false) {
  $line = chop($line);
  if(preg_match('/^\s*$/', $line)){
    $rules[] = $current;
    $current = "";
  }else{
    $current = $current . " " . $line;
  }
}
if(!preg_match('/^\s*$/', $current)){
  $rules[] = $current;
}
#print_r($rules);

for ($i=0; $i<count($rules); $i++){
  $rule = $rules[$i];
  $ret = preg_split('/THEN/',$rule);
  # print_r($ret);
  $cond = $ret[0]; $act = $ret[1];
  # condition
  $cond = preg_replace('/IF /', "", $cond, 1);
  $cond = preg_replace('/AND/', '&&', $cond);
  $cond = preg_replace('/OR/', '||', $cond);
  $terms = preg_split('/\s+/',$cond);
  echo '    if(';
  foreach ($terms as $term) {
    if(preg_match('/^[a-z].*[a-z]/', $term)){
      echo ' $data["' . $term . '"]';
    } else {
      echo ' ' . $term;
    }
  }
  echo " ){\n";
  
  # action
  $matches = array();
  preg_match('/^\s*([a-z\_]+)\s*(\(.*\))\s*$/', $act, $matches);
  $pred = $matches[1];
  echo '      $result[] = new Predicate("' . $pred . '", array(';
  $args = $matches[2];
  $args = preg_replace('/^\s*\(/','', $args);
  $args = preg_replace('/\)\s*$/','', $args);
  $args_arr = preg_split('/,/',$args);
  $start = true;
  foreach($args_arr as $arg){
    $arg = preg_replace('/^\s*/', '', $arg);
    $arg = preg_replace('/\s*$/', '', $arg);
    if($start) {
      $start = false;
    }else{
      echo ', ';
    }
    if(preg_match('/\(/',$arg)){
      $spl = preg_split('/\(/',$arg);
      $rp = $spl[0]; $ra = $spl[1];
      $ra = preg_replace('/\)\s*$/','', $ra);
      echo 'new Predicate("' . $rp . '", array("' . $ra . '"))';
    }else{
      echo '"' . $arg . '"';
    }
  }
  echo "));\n";
  echo "    }\n";
}
