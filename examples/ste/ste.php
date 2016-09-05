<?php

/*
 * Copyright (c) 2011-16 Pablo Ariel Duboue <pablo.duboue@gmail.com>
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

use nlgen\Generator;
require '../../php-nlgen/generator.php';

// done at the INLG2016 hackathon
// takes as input as a stripped version of the Stanford Parser Universal dependencies
class SimpleTechnicalEnglish extends Generator {

  function top($data){
      $this->context['data'] = $data;
      // data is a list of array(rel, input, output)
      // build the tree, each node is an array( head=> string, relation => array(nodes), relation... )
      $root = $this->_build_tree('ROOT');
      
      return $this->root($root['root'][0]);
  }

  private function _build_tree($head){
      $result = array('head' => $this->_morphology($head));
      foreach($this->context['data'] as $entry){
          if($entry[1] == $head){
              if(!isset($result[$entry[0]])){
                  $result[$entry[0]] = array();
              }
              $result[$entry[0]][] = $this->_build_tree($entry[2]);
          }
      }
      return $result;
  }

  private function _morphology($word){
      $pluralize = FALSE;
      if(preg_match('/\-PLURAL$/', $word)){
          $pluralize = TRUE;
          $word = preg_replace('/\-PLURAL$/', "", $word);
      }
      return preg_replace('/-\d+$/', "", $word);
  }

  protected function root($entry){
      $result = $entry['head'];
      if(isset($entry['dobj'])){
          foreach($entry['dobj'] as $dobj){
              $result=$result.' '.$this->dobj($dobj);
          }
      }
      if(isset($entry['mark'])){
          foreach($entry['mark'] as $mark){
              $result=$this->mark($mark).' '.$result;
          }
      }
      if(isset($entry['dep'])){
          foreach($entry['dep'] as $dep){
              $generated = $this->dep($dep);
              if(count(split(' ',$generated)) > 1){
                  $result = $result.' '.$generated;
              }else{
                  $result=$generated.' '.$result;
              }
          }
      }
      if(isset($entry['nmod'])){
          foreach($entry['nmod'] as $nmod){
              $result=$result.' '.$this->nmod($nmod);
          }
      }
      if(isset($entry['xcomp'])){
          foreach($entry['xcomp'] as $xcomp){
              $result=$result.' '.$this->root($xcomp);
          }
      }
      if(isset($entry['nsubj'])){
          foreach($entry['nsubj'] as $nsubj){
              $result=$this->dobj($nsubj).' '.$result;
          }
      }
      foreach($entry as $key => $value){
          if($key != 'head' && $key != 'dep' && $key != 'dobj' && $key != 'nmod' && $key != 'mark' && $key != 'xcomp' && $key != 'nsubj'){
              $result.=' [MM-root:'.$key.']';
          }
      }
      return $result;
  }

  protected function dobj($entry){
      $result = $entry['head'];
      if(isset($entry['compound'])){
          foreach($entry['compound'] as $compound){
              $result=$this->compound($compound).' '.$result;
          }
      }
      if(isset($entry['det'])){
          foreach($entry['det'] as $det){
              $result=$this->det($det)." ".$result;
          }
      }
      if(isset($entry['acl'])){
          foreach($entry['acl'] as $acl){
              $result=$result.' '.$this->root($acl);
          }
      }
      foreach($entry as $key => $value){
          if($key != 'head' && $key != 'det' && $key != 'acl' && $key != 'compound'){
              $result.=' [MM-dobj:'.$key.']';
          }
      }
      return $result;
  }
  
  protected function xcomp($entry){
      $result = $entry['head'];
      foreach($entry as $key => $value){
          if($key != 'head'){
              $result.=' [MM-xcomp:'.$key.']';
          }
      }
      return $result;
  }
  
  protected function nmod($entry){
      $result = $entry['head'];
      
      if(isset($entry['compound'])){
          foreach($entry['compound'] as $compound){
              $result=$this->compound($compound).' '.$result;
          }
      }
      if(isset($entry['amod'])){
          foreach($entry['amod'] as $amod){
              $result=$this->amod($amod)." ".$result;
          }
      }
      if(isset($entry['det'])){
          foreach($entry['det'] as $det){
              $result=$this->det($det)." ".$result;
          }
      }
      if(isset($entry['case'])){
          foreach($entry['case'] as $case){
              $result=$this->_case($case).' '.$result;
          }
      }
      if(isset($entry['nmod'])){
          foreach($entry['nmod'] as $nmod){
              $result=$result.' '.$this->nmod($nmod);
          }
      }
      foreach($entry as $key => $value){
          if($key != 'head' && $key != 'case' && $key != 'det'&& $key != 'amod' && $key != 'compound' && $key != 'nmod'){
              $result.=' [MM-nmod:'.$key.']';
          }
      }
      return $result;
  }
      
  protected function head($entry,$label){
      $result = "[M:".$label." ".$entry['head']."]";
      return $result;
  }
  
  protected function compound($entry){
      $result = $entry['head'];
      foreach($entry as $key => $value){
          if($key != 'head'){
              $result.=' [MM-compound:'.$key.']';
          }
      }
      return $result;
  }
  
  protected function mark($entry){
      $result = $entry['head'];
      foreach($entry as $key => $value){
          if($key != 'head'){
              $result.=' [MM-mark:'.$key.']';
          }
      }
      return $result;
  }

  protected function det($entry){
      $result = $entry['head'];
      foreach($entry as $key => $value){
          if($key != 'head'){
              $result.=' [MM-det:'.$key.']';
          }
      }
      return $result;
  }
  
  protected function dep($entry){
      $result = $entry['head'];
      if(isset($entry['nmod'])){
          foreach($entry['nmod'] as $nmod){
              $result=$result.' '.$this->nmod($nmod);
          }
      }
      if(isset($entry['nmod:poss'])){
          foreach($entry['nmod:poss'] as $nmodposs){
              $result=$this->nmod($nmodposs).' '.$result;
          }
      }
      foreach($entry as $key => $value){
          if($key != 'head' && $key != 'nmod'&& $key != 'nmod:poss'){
              $result.=' [MM-dep:'.$key.']';
          }
      }
      return $result;
  }

  protected function _case($entry){
      $result = $entry['head'];
      foreach($entry as $key => $value){
          if($key != 'head'){
              $result.=' [MM-case:'.$key.']';
          }
      }
      return $result;
  }
  
  protected function amod($entry){
      $result = $entry['head'];
      foreach($entry as $key => $value){
          if($key != 'head'){
              $result.=' [MM-amod:'.$key.']';
          }
      }
      return $result;
  }
}

global $argv,$argc;

$examples = array(
<<<HERE
dep(oil, put)
root(ROOT, oil)
case(surface, on)
det(surface, the)
amod(surface, machined)
nmod(oil, surface)
HERE
,
<<<HERE
root(ROOT, increase)
det(temperature, the)
dobj(increase, temperature)
mark(decrease, to)
acl(temperature, decrease)
det(time, the)
compound(time, cure)
dobj(decrease, time)
HERE
,
<<<HERE
root(ROOT, measure)
det(time, the)
nsubj(absorb, time)
dep(absorb, necessary)
case(gel, for)
det(gel, the)
compound(gel, silica)
nmod(necessary, gel)
mark(absorb, to)
xcomp(measure, absorb)
det(moisture, the)
dobj(absorb, moisture)
HERE
,
<<<HERE
root(ROOT, clean-1)
nmod:poss(skin, your)
dep(clean-1, skin)
case(quantity, with)
det(quantity, a)
amod(quantity, large)
nmod(skin, quantity)
case(water, of)
amod(water, clean-2)
nmod(quantity, water)
HERE

);

function parse_deps($deps) {
    $result=array();
    $lines = explode("\n", $deps);
    foreach($lines as $line){
        $parts=preg_split("/(\(|,|\))\s*/", $line);
        if(count($parts) > 1){
            $result[] = array($parts[0], $parts[1], $parts[2]);
        }
    }
    return $result;
}


$gen = SimpleTechnicalEnglish::NewSealed();

foreach($examples as $example){
    print ucfirst($gen->generate(parse_deps($example))).".\n";
}

//print_r($gen->semantics());


