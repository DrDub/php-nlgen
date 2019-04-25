<?php

/*
 * Copyright (c) 2019 Pablo Ariel Duboue <pablo.duboue@gmail.com>
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

require_once '../../nlgen/generator.php';
use nlgen\Generator;

class TruckConfigGenerator extends Generator {

  # data are different dimensions changed plus new values
  function top($data){
      # check and remove defaults
      $info = array();
      if($data['box'] == 'short'){
          $info['box'] = 'short';
      }
      if($data['cabin'] == 'crew'){
          $info['cabin'] = 'crew';
      }
      if($data['drivetrain'] == '4x4'){
          $info['drivetrain'] = '4x4';
      }

      return $this->sentence($data['action'], $info);
  }

  protected function sentence($action, $info) {      
      $result =  "You want to " . $action . " " . $this->truck($info) . '.';
      $sem = $this->current_semantics()['truck_orig'];
      if(isset($sem['leftover'])){
          $result = $result . " " . $this->truck_s($sem['leftover']);
      }
      return $result;
  }

  protected function truck($data) {
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

  protected function cabin_ap($cabin) {
      return $cabin . "-cabin";
  }
  protected function cabin_pp($cabin) {
      return "with a " . $cabin . " cabin";
  }
  
  protected function drivetrain_ap($drivetrain) {
      if($drivetrain == "4x4"){
          return "four wheel-drive";
      }
      return "standard traction";          
  }
  protected function drivetrain_pp($drivetrain) {
      return "with " . $this->drivetrain_ap($drivetrain);
  }
  
  protected function box_ap($box) {
      return $box . "-box";
  }
  protected function box_pp($box) {
      return "having a " . $box . " box";
  }

  protected function truck_s($data) {
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
