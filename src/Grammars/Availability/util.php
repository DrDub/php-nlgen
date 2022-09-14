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

namespace NLGen\Grammars\Availability;

// diff in minutes between two times expressed as integer tuples hour, minute
function minDiff($a, $b) : int {
    $start = $a;
    $end   = $b;
    if($a[0] > $b[0] || ($a[0] == $b[0] && $a[1] > $b[1])) {
        $start = $b;
        $end   = $a;
    }   
    return ($end[0] - $start[0]) * 60 - $start[1] + $end[1];
}

function intersection(array $a, array $b) : int {
    $result = 0;
    foreach($a as $t => $x) {
        if(isset($b[$t])) {
            $result++;
        }
    }
    return $result;
}

// print a time table of day-of-week into array of entries
function tableToString(array $table) : string {
    $result = "";
    foreach($table as $dow => $entries) {
        if($result) {
            $result .= "\n";
        }
        $es = [];
        foreach($entries as $entry){
            $es[] = strval($entry);
        }
        $result.="$dow: ".implode("; ", $es);
    }
    return $result;
}


