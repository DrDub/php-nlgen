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

$loader = require __DIR__ . '/vendor/autoload.php';

use NLGen\Grammars\Availability\AvailabilityGenerator;
use NLGen\Grammars\Availability\AvailabilityGrammar;

mt_srand(5);
//mt_srand(8);
//mt_srand(9);

$ranges = [0 => [ [8, 0], [17, 0] ],
           1 => [ [8, 0], [17, 0] ],
           2 => [ [8, 0], [17, 0] ],
           3 => [ [8, 0], [17, 0] ],
           4 => [ [8, 0], [17, 0] ],
           5 => [ [8, 0], [17, 0] ] ];

// Monday and Wednesday are the same
// Tuesday and Saturday are similar

$busyList = [
    [0, [ 8, 00], [ 8, 50]],
    [0, [10, 10], [10, 40]],
    [0, [11, 30], [11, 40]],
    [0, [12, 00], [13, 00]],
    [0, [14, 20], [14, 50]],
    [0, [15, 30], [16, 00]],
    [0, [16, 40], [17, 00]],
    [1, [ 8, 10], [ 8, 30]],
    [1, [11, 00], [11, 40]],
    [1, [12, 00], [12, 30]],
    [1, [13, 00], [13, 50]],
    [1, [15, 00], [15, 40]],
    [1, [15, 50], [16, 50]],
    [2, [ 8, 00], [ 8, 50]],
    [2, [10, 10], [10, 40]],
    [2, [11, 30], [11, 40]],
    [2, [12, 00], [13, 00]],
    [2, [14, 20], [14, 50]],
    [2, [15, 30], [16, 00]],
    [2, [16, 40], [17, 00]],
    [3, [ 8, 10], [ 8, 40]],
    [3, [ 9, 20], [10, 00]],
    [3, [10, 20], [10, 40]],
    [3, [12, 30], [13, 10]],
    [3, [14, 10], [14, 40]],
    [4, [ 8, 40], [ 9, 00]],
    [4, [10, 00], [10, 30]],
    [4, [12, 50], [14, 40]],
    [4, [15, 50], [16, 50]],
    [5, [ 8, 00], [ 8, 40]],
    [5, [11, 00], [11, 50]],
    [5, [13, 00], [13, 50]],
    [5, [12, 10], [12, 40]],
    [5, [15, 10], [15, 50]],
    [5, [15, 50], [16, 50]]];


if(true){
    $gen = new AvailabilityGenerator();
}else{
    $class = AvailabilityGrammar::class;
    $path = realpath($loader->findFile($class));
    $lexicon = file_get_contents(dirname($path)."/lexicon_en.json");
    $gen = AvailabilityGrammar::NewSealed('', $lexicon);
}

foreach(range(0,3) as $coarseness) {    
    echo AvailabilityGenerator::COARSENESS[$coarseness].":\n\n";
    $text = $gen->generateAvailability($busyList, $ranges, $coarseness, null);
    echo strtoupper(AvailabilityGenerator::COARSENESS[$coarseness])." OUTPUT: $text\n";
    echo "\n------\n";
}

