<?php

/*
 * Copyright (c) 2022 Pablo Ariel Duboue <pablo.duboue@gmail.com>
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

use NLGen\Generator;

class MultilingualTruckConfigGenerator extends Generator {
    
    # data includes 'type' (question or configuration)
    function top($data){
        # check for templates
        if(! is_array($data)) {
            return $this->lex->string_for_id($data);
        }      
        if($data['type'] == 'question') {
            return $this->question($data);
        }
        # check and remove defaults
        # TODO replace this code with an ontology lookup
        $info = [ 'vehicle' => $data['vehicle'] ];
        $box        = $data['box'] ?? "";
        $cabin      = $data['cabin'] ?? "";
        $drivetrain = $data['drivetrain'] ?? ""; 
        $engine     = $data['engine'] ?? ""; 
        $color      = $data['color'] ?? ""; 
        if($box == $this->lex->string_for_id('short') || $box == 'short'){
            $info['box'] = 'short';
        }
        if($cabin == $this->lex->string_for_id('crew') || $cabin == 'crew'){
            $info['cabin'] = 'crew';
        }
        if($color == $this->lex->string_for_id('red') || $color == 'red'){
            $info['color'] = 'red';
        }
        if($engine == $this->lex->string_for_id('inboard') || $engine == 'inboard'){
            $info['engine'] = 'inboard';
        }
        if($drivetrain == '4x4'){
            $info['drivetrain'] = '4x4';
        }
        //error_log(print_r($info, TRUE));
        
        return $this->sentence($data['action'], $info);
    }

    function get_options($question) {
        $all_opts = $this->onto->find_all_by_class('option');
        //print_r($all_opts);
        
        $opts = [];        
        $has_default=false;
        $default = NULL;
        $other = NULL;
        foreach($all_opts as $id) {
            $frame = $this->onto->find($id);
            if($frame['question'] == $question){
                //print_r($frame);
                $entry = NULL;
                if($this->lex->has("opt_".$frame['id'])){
                    $entry = $this->lex->find("opt_".$frame['id']);
                }else{
                    $entry = $this->lex->find($frame['id']);
                }
                $entry['onto'] = $frame['id'];
                $entry['default'] = isset($frame['default']);
                if($entry['default']){
                    $has_default=true;
                    $default = $entry;
                }else{
                    $other = $entry;
                }
                $opts[] = $entry;
            }
        }
        //print_r($opts);
        return [ $opts, $has_default, $default, $other ];
    }

    protected function question($data) {
        $question = $data['need'];
        $object   = $data['target'] ?? NULL;

        [ $opts, $has_default, $default, $other ] = $this->get_options($question);
        
        $key = "q_" . $question;
        $text = "";
        $sem = [];
        if($has_default) {
            $text = $this->standard($default, $question, $object) . " " . $this->alternative($other, $question, $object);
            $sem  = [ 'default' => $default, 'other' => $other ];
        }else{
            $text = $this->lex->string_for_id($key, [ "options" => $this->list($opts, 'or') ]);
        }
        $sem['opts'] = $opts;
        
        if($question == "vehicle") {
            $text = $this->lex->string_for_id("greet") . " " . $text;
        }
        return [ 'text' => $text, 'sem' => $sem ];
    }

    protected function standard($entry, $type, $object) {
        if($type == "color"){
            return $this->standard_color($entry, $object);
        }
        $option = $this->option($entry);
        return $this->lex->string_for_id("standard", [ "option" => $option ]);
    }

    protected function alternative($entry, $type, $object) {
        if($type == "color"){
            return $this->alternative_color($entry, $object);
        }
        $option = $this->option($entry);
        return $this->lex->string_for_id("altern", [ "option" => $option ]);
    }

    protected function standard_color($entry, $object) {
        $color = $this->lex->query([ 'id' => $entry['onto'], 'root' => 'y' ])[0]['string'];
        return $this->lex->string_for_id("standardc", [ "color" => $color ]);
    }

    protected function alternative_color($entry, $object) {
        $object = $this->lex->find($object);
        
        $colorobj = $this->determiner([ 'def' => 'n',  'gender' => $object['gender'], 'case' => 'nom' ]) . " " .
                  $this->lex->query([ 'id' => $entry['onto'], 'gender' => $object['gender'], 'case' => 'nom' ])[0]['string'] . " " .
                  $object['string'];
        return $this->lex->string_for_id("alternc", [ "colorobj" => $colorobj ]);
    }
    
    protected function option_de($entry) {
        return $this->determiner([ 'def' => 'n', 'gender' => $entry['gender'], 'case' => 'dat' ]). " " . $entry['string'];
    }

    protected function determiner($data) {
        $query = $data;
        $query['class'] = 'det';
        return $this->lex->query($query)[0]['string'];
    }

    protected function list($opts, $conj) {
        $len = count($opts);
        if($len < 1) return "";
        $copy = $opts;
        $last = array_pop($copy);
        $list = [];
        foreach($copy as $entry){
            $list[] = $entry['string'];
        }
        if($len < 2) {
            return $last['string'];
        }
        return implode(", ", $list)." ".$this->lex->string_for_id($conj)." ". $last['string'];
    }

  protected function sentence($action, $info) {
      $action = $this->lex->string_for_id($action);
      $configured = $this->vehicle($info);
      $result = $this->lex->string_for_id('config', ['configured' => $configured, 'action' => $action ]);
      $sem = $this->current_semantics()['vehicle'];
      if(isset($sem['leftover'])){
          $result = $result . " " . $this->vehicle_s($sem['leftover']);
      }
      return $result;
  }

  protected function vehicle_en($data) {
      if(count($data) == 3){
          return array('text' => "a " . $this->box_ap($data['box']) . " pickup truck " . $this->cabin_pp($data['cabin']),
          'sem' => array('leftover' => array('drivetrain' => $data['drivetrain'])));
      }
      if(isset($data['box'])){
          $result = "a " . $this->box_ap($data['box']) . " pickup truck";
          if(isset($data['cabin'])) {
              $result = $result . " " . $this->cabin_pp($data['cabin']);
          }elseif(isset($data['drivetrain'])) {
              $result = $result . " " . $this->drivetrain_pp($data['drivetrain']);
          }
          return $result;
      }
      if(isset($data['cabin'])){
          $result = "a " . $this->cabin_ap($data['cabin']) . " pickup truck";
          if(isset($data['drivetrain'])) {
              return array('text' => $result,
              'sem' => array('leftover' => array('drivetrain' => $data['drivetrain'])));
          }else{
              return $result;
          }
      }
      if(isset($data['drivetrain'])){
          return "a " . $this->drivetrain_ap($data['drivetrain']) . " pickup truck";
      }
      return "a pickup truck";
  }

  protected function vehicle_de($data) {
      $object = $this->lex->find($data['vehicle']);
      unset($data['vehicle']);
      
      $result = $this->determiner([ 'def' => 'n',  'gender' => $object['gender'], 'case' => 'nom' ]) . " ";
      if(isset($data['color'])){
          $result .= $this->lex->query([ 'id' => $data['color'], 'gender' => $object['gender'], 'case' => 'nom' ])[0]['string']. " ";
          unset($data['color']);
      }
      if (isset($data['drivetrain'])) {
          $result .= $this->drivetrain_ap($data['drivetrain']);
          unset($data['drivetrain']);
      }
      $result .= $object['string'];

      $opts = count($data);
      if($opts) {
          $result .= " " . $this->lex->string_for_id('with') . " ";

          $opts=[];
          foreach($data as $key => $value) {
              switch ($key) {
              case 'box':        $opts[] = [ 'string' => $this->box_ap($value) ]; break;
              case 'cabin':      $opts[] = [ 'string' => ucfirst($this->lex->string_for_id($value)) ]; break;
              case 'engine':     $opts[] = [ 'string' => ucfirst($this->lex->string_for_id($value)) ]; break;
              }
          }
          $result .= $this->list($opts, 'and');
      }
      return $result;
  }

  protected function cabin_ap($cabin) {
      return $this->lex->string_for_id($cabin) . "-cabin";
  }
  protected function cabin_pp_en($cabin) {
      return "with a " . $this->lex->string_for_id($cabin) . " cabin";
  }

  protected function cabin_pp_de($cabin) {
      return "mit " . ucfirst($this->lex->string_for_id($cabin));
  }

  protected function drivetrain_ap_en($drivetrain) {
      if($drivetrain == "4x4"){
          return "four wheel-drive";
      }
      return "standard traction";          
  }

  protected function drivetrain_ap_de($drivetrain) {
      if($drivetrain == "4x4"){
          return "Allrad-";
      }
      return "Standardtraktion "; 
  }

  protected function drivetrain_pp($drivetrain) {
      return "with " . $this->drivetrain_ap($drivetrain);
  }
  
  protected function box_ap($box) {
      return $this->lex->string_for_id($box) . "-box";
  }
  protected function box_pp_en($box) {
      return "having a " . $this->lex->string_for_id($box) . " box";
  }

  protected function box_pp_de($box) {
      return "mit " . $this->lex->string_for_id($box). " Ladefläche";
  }

  protected function vehicle_s($data) {
      if(isset($data['cabin'])){
          return "It comes " . $this->cabin_pp($data['cabin']) . ".";
      }
      if(isset($data['drivetrain'])) {
          return "It is a " . $this->drivetrain_ap($data['drivetrain']) . ".";
      }
      if(isset($data['box'])) {
          return "It has a  " . $data['box'] . " box.";
      }
      return "";
  }
}

/*
$ontology   = file_get_contents("ontology.json");
$lexicon_en = file_get_contents("lexicon_en.json");
$lexicon_de = file_get_contents("lexicon_de.json");
$gen = MultilingualTruckConfigGenerator::NewSealed($ontology,
                                                  [ 'en' => $lexicon_en, 'de' => $lexicon_de ] );
echo $gen->generate([ 'type' => 'question', 'need' => 'drivetrain', 'target' => 'pickup' ], ['lang'=>'de']) . "\n\n";
echo $gen->generate([ 'type' => 'question', 'need' => 'action', 'target' => 'pickup' ], ['lang'=>'de']) . "\n\n";
echo $gen->generate([ 'type' => 'configuration', 'action' => 'lease',
                      'vehicle' => 'boat', 'box' => 'short', 'color' => 'red' ], ['lang'=>'de']) . "\n\n";
echo $gen->generate([ 'type' => 'configuration', 'action' => 'buy',
                      'vehicle' => 'boat', 'box' => 'short', 'engine' => 'inboard', 'color' => 'white' ], ['lang'=>'de']) . "\n\n";
echo $gen->generate([ 'type' => 'configuration', 'action' => 'buy',
                      'vehicle' => 'truck', 'box' => 'long', 'drivetrain' => '4x4', 'color' => 'red',
                      'cabin' => 'crew' ], ['lang'=>'de']) . "\n\n";
echo $gen->generate([ 'type' => 'question', 'need' => 'color', 'target' => 'truck' ], ['lang'=>'de']) . "\n\n";
echo $gen->generate([ 'type' => 'question', 'need' => 'color', 'target' => 'boat' ], ['lang'=>'de']) . "\n\n";
*/
