<?php

/*
 * Copyright (c) 2011-2022 Pablo Ariel Duboue <pablo.duboue@gmail.com>
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

use NLGen\Grammars\Availability\AvailabilityGenerator;

header('Content-Type: application/json; charset=utf-8');

function clean($a)
{
    if(! is_array($a)) {
        return $a;
    }
    if(isset($a['value'])) {
        return $a['value'];
    }
    $result=[];
    foreach($a as $k=>$v){
        if($k === "text") {
            continue;
        }
        if($k === "dows" && isset($v['number'])){
            continue;
        }
        $v = clean($v);
        $result[$k] = $v;
        if($k === "blocks" && !isset($v[0])) {
            $result[$k] = [ $v ];
        }
    }
    return $result;
}

if ($_SERVER['REQUEST_METHOD']=="POST") {
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, True);
    //file_put_contents("/tmp/api_call.json", $rawData);
    $coarseness = $data[0];
    $ranges = [];
    foreach($data[1] as $dow=>$pair) {
        $ranges[intval($dow)] = $pair;
    }
    $busyList = $data[2];
    
    $gen = new AvailabilityGenerator();
    $text = $gen->generateAvailability($busyList, $ranges, $coarseness, null);
    $sem = $gen->semantics();
    //file_put_contents("/tmp/api_result.json", json_encode($sem));

    $result = [];
    $result['text'] = $text;
    $maxCount = count($sem['top']);
    $sentCount = 0;
    $prev = null;
    $delta = 0;
    while(isset($sem['top'][$sentCount])) {
        if($prev) { // merge with previous for fragments
            $delta++;
            $sem['top'][$sentCount-$delta] = $sem['top'][$sentCount];
            $sem['top']['offsetStart'] = $prev['offsetStart'];
            $prev = null;
        }elseif(! isset($sem['top'][$sentCount]['blocks'])){ // fragment
            $prev = $sem['top'][$sentCount];
        }elseif($delta){
            $sem['top'][$sentCount - $delta] = $sem['top'][$sentCount];
        }
        $sentCount++;
    }
    if($delta) {
        foreach(range($sentCount-$delta, $sentCount-1) as $i) {
            unset($sem['top'][$i]);
        }
        $sentCount -= $delta;
    }
    //file_put_contents("/tmp/api_result2.json", json_encode($sem));
    $result['sentences'] = [];
    foreach(range(0, $sentCount - 1) as $sent) {
        //$sentText = substr($text, $sem['top'][$sent]['offsetStart'], $sem['top'][$sent]['offsetEnd'] - $sem['top'][$sent]['offsetStart']);
        //if($sent) {
        //    $key = "focusedMessage$sent";
        //}else{
        //    $key = "focusedMessage";
        //}
        $sentSem = clean($sem['top'][$sent]);
        //$sentSem['offsetStart'] = $sem['top'][$sent]['offsetStart'];
        //$sentSem['offsetEnd']   = $sem['top'][$sent]['offsetEnd'];
        $result['sentences'][] = $sentSem;
        //$sentText = substr($text, $sem['top'][$sent]['offsetStart'], $sem['top'][$sent]['offsetEnd'] - $sem['top'][$sent]['offsetStart']);
    }
    /*    
    foreach($sem['top'] as $key => $val) {
        echo "Key: ";print_r($key);echo "\n";
        echo "Value: ";print_r($val);echo"\n";
    }
    */
    $result['status'] = 200;
    
    //file_put_contents("/tmp/api_result3.json", json_encode($result));
    echo json_encode($result);
}else{
    echo '{"status":500, "error":"wrong method"}';
}

