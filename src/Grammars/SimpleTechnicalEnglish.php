<?php

/*
 * Copyright (c) 2016-2020 Pablo Ariel Duboue <pablo.duboue@gmail.com>
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

namespace NLGen\Grammars;

use NLGen\Generator;


// built at the INLG2016 hackathon takes as input as a stripped
// version of the Stanford Parser Universal dependencies

class SimpleTechnicalEnglish extends Generator {

  function top($data){
      $this->context['data'] = $data;
      // data is a list of array(rel, input, output)
      // build the tree, each node is an array( head=> string, relation => array(nodes), relation... )
      // check whether is ROOT or ROOT-0
      $rootname = 'ROOT';
      foreach($this->context['data'] as $entry){
          if($entry[1] == 'ROOT-0'){
              $rootname='ROOT-0';
          }
      }
      
      $root = $this->_build_tree($rootname);
      
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
      $word = preg_replace('/-\d+$/', "", $word);
      if(preg_match('/\-PLURAL$/', $word)){
          $pluralize = TRUE;
          $word = preg_replace('/\-PLURAL$/', "", $word);
      }
      $word = preg_replace('/-\d+$/', "", $word);
      if($pluralize){
          $word .= "s";
      }
      return $word;
  }

  protected function root($entry){
      $result = $entry['head'];
      if(isset($entry['compound'])){ foreach($entry['compound'] as $compound){ $result=$this->compound($compound).' '.$result; } }
      if(isset($entry['det'])){ foreach($entry['det'] as $det){ $result=$this->det($det).' '.$result; } }
      if(isset($entry['advmod'])){ foreach($entry['advmod'] as $advmod){ $result=$this->advmod($advmod).' '.$result; } }
      if(isset($entry['neg'])){ foreach($entry['neg'] as $neg){ $result=$this->advmod($neg).' '.$result; } }
      if(isset($entry['aux'])){ foreach($entry['aux'] as $aux){ $result=$this->aux($aux).' '.$result; } }
      if(isset($entry['auxpass'])){ foreach($entry['auxpass'] as $aux){ $result=$result.' '.$this->aux($aux); } }
      if(isset($entry['cop'])){ foreach($entry['cop'] as $cop){ $result=$this->cop($cop).' '.$result; } }
      if(isset($entry['dobj'])){ foreach($entry['dobj'] as $dobj){ $result=$result.' '.$this->dobj($dobj); } }
      if(isset($entry['mark'])){ foreach($entry['mark'] as $mark){ $result=$this->mark($mark).' '.$result; } }
      if(isset($entry['compound:prt'])){ foreach($entry['compound:prt'] as $compound){ $result=$result.' '.$this->compound($compound); } }
      if(isset($entry['dep'])){ foreach($entry['dep'] as $dep){
              $generated = $this->dep($dep);
              if(count(explode(' ',$generated)) > 1){
                  $result = $result.' '.$generated;
              }else{
                  $result=$generated.' '.$result;
              }
          } }
      if(isset($entry['nmod'])){ foreach($entry['nmod'] as $nmod){ $result=$result.' '.$this->nmod($nmod); } }
      if(isset($entry['nmod:of'])){ foreach($entry['nmod:of'] as $nmod){ $result=$result.' '.$this->nmod($nmod); } }
      if(isset($entry['nmod:with'])){ foreach($entry['nmod:with'] as $nmod){ $result=$result.' '.$this->nmod($nmod); } }
      if(isset($entry['xcomp'])){ foreach($entry['xcomp'] as $xcomp){ $result=$result.' '.$this->root($xcomp); } }
      if(isset($entry['nsubj'])){ foreach($entry['nsubj'] as $nsubj){ $result=$this->dobj($nsubj).' '.$result; } }
      if(isset($entry['csubj'])){ foreach($entry['csubj'] as $csubj){ $result=$this->root($csubj).' '.$result; } }
      if(isset($entry['nsubjpass'])){ foreach($entry['nsubjpass'] as $nsubj){ $result=$result.' '.$this->dobj($nsubj); } }
      if(isset($entry['iobj'])){ foreach($entry['iobj'] as $iobj){ $result=$result.' '.$this->dobj($iobj); } }
      if(isset($entry['ccomp'])){ foreach($entry['ccomp'] as $ccomp){ $result=$result.' '.$this->root($ccomp); } }
      if(isset($entry['cc'])){ foreach($entry['cc'] as $cc){ $result=$result.' '.$this->cc($cc); } }
      if(isset($entry['conj'])){ foreach($entry['conj'] as $conj){ $result=$result.' '.$this->root($conj); } }
      if(isset($entry['advcl'])){ foreach($entry['advcl'] as $advcl){ $result=$result.' '.$this->root($advcl); } }
      foreach($entry as $key => $value){
          if($key != 'head' && $key != 'dep' && $key != 'dobj' && $key != 'nmod' && $key != 'mark'
          && $key != 'xcomp' && $key != 'nsubj' && $key != 'ccomp' && $key != 'cop' && $key != 'det'
          && $key != 'compound:prt' && $key != 'nmod:with'  && $key != 'nmod:of'  && $key != 'nsubjpass'
          && $key != 'aux' && $key != 'auxpass' && $key != 'advmod' && $key != 'cc' && $key != 'conj'
          && $key != 'neg' && $key != 'iobj' && $key != 'csubj' && $key != 'advcl' && $key != 'compound'){
              $result=$result.' [MM-root:'.$key.']';
          }
      }
      return $result;
  }

  protected function dobj($entry){
      $result = $entry['head'];
      if(isset($entry['compound'])){ foreach($entry['compound'] as $compound){ $result=$this->compound($compound).' '.$result; } }
      if(isset($entry['amod'])){ foreach($entry['amod'] as $amod){ $result=$this->amod($amod).' '.$result; } }      
      if(isset($entry['nummod'])){ foreach($entry['nummod'] as $nummod){ $result=$this->nummod($nummod).' '.$result; } }
      if(isset($entry['det'])){ foreach($entry['det'] as $det){ $result=$this->det($det)." ".$result; } }
      if(isset($entry['neg'])){ foreach($entry['neg'] as $neg){ $result=$this->advmod($neg).' '.$result; } }
      if(isset($entry['acl'])){ foreach($entry['acl'] as $acl){ $result=$result.' '.$this->root($acl); } }
      if(isset($entry['acl:relcl'])){ foreach($entry['acl:relcl'] as $acl){ $result=$result.' '.$this->root($acl); } }
      if(isset($entry['appos'])){ foreach($entry['appos'] as $appos){ $result=$result.' '.$this->appos($appos); } }
      if(isset($entry['nmod'])){ foreach($entry['nmod'] as $nmod){ $result=$result.' '.$this->nmod($nmod); } }
      if(isset($entry['nmod:of'])){ foreach($entry['nmod:of'] as $nmod){ $result=$result.' '.$this->nmod($nmod); } }
      if(isset($entry['cc'])){ foreach($entry['cc'] as $cc){ $result=$result.' '.$this->cc($cc); } }
      foreach($entry as $key => $value){
          if($key != 'head' && $key != 'det' && $key != 'acl' && $key != 'compound' && $key != 'amod' && $key != 'nmod' && $key != 'appos'
           && $key != 'nmod:of' && $key != 'acl:relcl' && $key != 'cc' && $key != 'nummod' && $key != 'neg'){
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
      
      if(isset($entry['compound'])){ foreach($entry['compound'] as $compound){ $result=$this->compound($compound).' '.$result; } }
      if(isset($entry['amod'])){ foreach($entry['amod'] as $amod){ $result=$this->amod($amod)." ".$result; } }
      if(isset($entry['nummod'])){ foreach($entry['nummod'] as $nummod){ $result=$this->nummod($nummod).' '.$result; } }
      if(isset($entry['det'])){ foreach($entry['det'] as $det){ $result=$this->det($det)." ".$result; } }
      if(isset($entry['case'])){ foreach($entry['case'] as $case){ $result=$this->_case($case).' '.$result; } }
      if(isset($entry['nmod'])){ foreach($entry['nmod'] as $nmod){ $result=$result.' '.$this->nmod($nmod); } }
      foreach($entry as $key => $value){
          if($key != 'head' && $key != 'case' && $key != 'det'&& $key != 'amod' && $key != 'compound' && $key != 'nmod' && $key != 'nummod'){
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
  
  protected function advmod($entry){
      $result = $entry['head'];
      foreach($entry as $key => $value){
          if($key != 'head'){
              $result.=' [MM-advmod:'.$key.']';
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

  protected function aux($entry){
      $result = $entry['head'];
      foreach($entry as $key => $value){
          if($key != 'head'){
              $result.=' [MM-aux:'.$key.']';
          }
      }
      return $result;
  }
  
  protected function cop($entry){
      $result = $entry['head'];
      foreach($entry as $key => $value){
          if($key != 'head'){
              $result.=' [MM-cop:'.$key.']';
          }
      }
      return $result;
  }
  
  protected function appos($entry){
      $result = $entry['head'];
      foreach($entry as $key => $value){
          if($key != 'head'){
              $result.=' [MM-appos:'.$key.']';
          }
      }
      return "(" . $result . ")";
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
  
  protected function cc($entry){
      $result = $entry['head'];
      foreach($entry as $key => $value){
          if($key != 'head'){
              $result.=' [MM-cc:'.$key.']';
          }
      }
      return $result;
  }
  
  protected function amod($entry){
      $result = $entry['head'];
      if(isset($entry['cc'])){ foreach($entry['cc'] as $cc){ $result=$this->cc($cc).' '.$result; } }
      if(isset($entry['conj'])){ foreach($entry['conj'] as $conj){ $result=$this->amod($conj).' '.$result; } }
      foreach($entry as $key => $value){
          if($key != 'head' && $key != 'cc' && $key != 'conj'){
              $result.=' [MM-amod:'.$key.']';
          }
      }
      return $result;
  }
  
  protected function nummod($entry){
      $result = $entry['head'];
      if(isset($entry['compound'])){ foreach($entry['compound'] as $compound){ $result=$this->compound($compound).' '.$result; } }
      foreach($entry as $key => $value){
          if($key != 'head' && $key != 'compound'){
              $result.=' [MM-nummod:'.$key.']';
          }
      }
      return $result;
  }
}

