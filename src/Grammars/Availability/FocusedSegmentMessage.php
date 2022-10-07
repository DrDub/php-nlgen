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

class FocusedSegmentMessage {

    // focus constants
    public const DAYS = 0;
    public const SEGMENT = 1;
    public const WEEK = 2;

    public int $focus; // one of the constants above
    public array $startTime; // array int (hour), int (minute)
    public array $endTime;
    public array $dows; // array int (day-of-the-week)
    public bool $fullRange; // do the start and end cover the full range of the day?
    public array $blocks; // fine grain details

    public function __construct(int $focus, array $startTime, array $endTime, array $dows, bool $fullRange, array $blocks) {
        $this->focus = $focus;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->dows = $dows;
        $this->fullRange = $fullRange;
        $this->blocks = $blocks;
    }

    public function semantics(): array {
        $block_sem = [];
        foreach($this->blocks as $block) {
            $block_sem[] = $block->semantics();
        }
        return [ 'startTime' => $this->startTime,
                 'endTime' => $this->endTime,
                 'dows' => $this->dows,
                 'fullRange' => $this->fullRange,
                 'blocks' => $block_sem,
        ];
    }

    public function minutes() : int {
        $result = 0;
        foreach($this->blocks as $block) {
            $result += $block->minutes();
        }
        return $result;
    }
}
