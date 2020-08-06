<?php
namespace App;

class Puzzle
{
    /**
     * Actual position
     * @var array
     */
    var $pos;

    /**
     * All sequences
     * @var array
     */
    var $sequence;

    /**
     * Current depth
     * @var integer
     */
    var $depth;
    var $val;

    /**
     * Cost of the path
     * @var integer
     */
    var $path_cost;

    /**
     * Completed puzzle state
     * @var array
     */
    var $goalpos;

    /**
     * Set Initial values with current puzzle state
     * @param $current_pos array
     */
    function setInitial($current_pos)
    {
        $this->pos = $this->arrayToNumber($current_pos);
        $this->depth = 1;
        $this->sequence[] = $this->pos;
    }

    /**
     * Set goal state
     * @param $goal_pos array
     */
    function setGoalpos($goal_pos)
    {
        $this->goalpos = $this->arrayToNumber($goal_pos);
        $this->evaluate($goal_pos);
    }

    /**
     * Check if puzzle isn't solved yet
     */
    function goalTest()
    {
        if ($this->pos == $this->goalpos) {
            return True;
        } else {
            return False;
        }
    }

    /**
     * Get all possible moves
     */
    function possibleMoves()
    {
        $Moves = array();
        $current_pos = $this->numberToArray($this->pos);
        for ($i = 0; $i < 3; $i++) { //Find blank
            for ($j = 0; $j < 3; $j++) {
                if ($current_pos[$i][$j] == 0) {
                    break 2;
                }
            }
        }

        //Check for moves possibility
        $this->checkMove($i, $j, $i - 1, $j, $current_pos, $Moves);
        $this->checkMove($i, $j, $i + 1, $j, $current_pos, $Moves);
        $this->checkMove($i, $j, $i, $j - 1, $current_pos, $Moves);
        $this->checkMove($i, $j, $i, $j + 1, $current_pos, $Moves);

        return $Moves;
    }

    /**
     * Move blank position
     * @param $srcRow integer
     * @param $srcCol integer
     * @param $destRow integer
     * @param $destCol integer
     * @param $newpos array
     * @return array
     */
    function moveBlank($srcRow, $srcCol, $destRow, $destCol, $newpos)
    {
        $tmp = $newpos[$destRow][$destCol];
        $newpos[$destRow][$destCol] = $newpos[$srcRow][$srcCol];
        $newpos[$srcRow][$srcCol] = $tmp;
        return $newpos;
    }

    /**
     * Is current state already in sequences?
     * @param $pos array
     * @return boolean
     */
    function InSequence($pos)
    {
        foreach ($this->sequence as $seqpos) {
            if ($seqpos === $pos) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Check for the move possibility
     * @param $srcRow integer
     * @param $srcCol integer
     * @param $destRow integer
     * @param $destCol integer
     * @return boolean
     */
    function canMove($srcRow, $srcCol, $destRow, $destCol)
    {
        if ($srcRow < 0 or $srcCol < 0 or $destRow < 0 or $destCol < 0) {
            return FALSE;
        }
        if ($srcRow > 2 or $srcCol > 2 or $destRow > 2 or $destCol > 2) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Make the move
     * @param $srcRow integer
     * @param $srcCol integer
     * @param $destRow integer
     * @param $destCol integer
     * @param $current_pos array
     * @param $Moves array
     */
    function checkMove($srcRow, $srcCol, $destRow, $destCol, $current_pos, & $Moves)
    {
        if ($this->canMove($srcRow, $srcCol, $destRow, $destCol)) {
            $newpos = $this->moveBlank($srcRow, $srcCol, $destRow, $destCol, $current_pos);
            $posnum = $this->arrayToNumber($newpos);
            if ($this->InSequence($posnum) == FALSE) {
                $newMove = clone $this;
                $newMove->pos = $posnum;
                $newMove->sequence[] = $posnum;

                $newMove->depth++;
                $newMove->evaluate($newpos);
                $Moves[] = $newMove;
            }
        }
    }

    /**
     * Return all the needed sequences
     */
    function getSequences()
    {
        for ($i = 0; $i < count($this->sequence); $i++) {
            $sequences[] = $this->numberToarray($this->sequence[$i]);
        }

        return $sequences;
    }

    /**
     * Evaluate the solver
     * @param $pos array
     */
    function evaluate($pos)
    {
        $this->heuristics($pos);
        $this->pathCost();
    }

    /**
     * Heuristics logic
     * @param $pos array
     */
    function heuristics($pos)
    {
        $goalpos = $this->numberToarray($this->goalpos);
        $this->val = 0;
        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 3; $j++) {
                $blockrow = 0;
                $blockcol = 0;
                $this->findBlock($goalpos[$i][$j], $blockrow, $blockcol, $pos);
                $blockval = abs($blockrow - $i) + abs($blockcol - $j);
                $this->val = $this->val + $blockval;
            }
        }
    }

    /**
     * Get cost of path
     */
    function pathCost()
    {
        $this->path_cost = $this->depth;
    }

    function findBlock($val, &$i, &$j, $pos)
    {
        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 3; $j++) {
                if ($pos[$i][$j] == $val) {
                    break 2;
                }
            }
        }
    }

    /**
     * Array to string
     * @param $posarray array
     * @return string
     */
    function arrayToString($posarray)
    {
        $posstr = "";
        for ($i = 0; $i < 3; $i++) {
            $s = implode(",", $posarray[$i]);
            $posstr = $posstr . "|" . $s;
        }
        return $posstr;
    }

    /**
     * String to atring
     * @param $posstr string
     * @return array
     */
    function stringToarray($posstr)
    {

        $posarray = array();
        $iterarray = explode("|", $posstr);
        for ($i = 1; $i < count($iterarray); $i++) {
            $posarray[] = explode(",", $iterarray[$i]);
        }
        return $posarray;
    }

    /**
     * Array to number
     * @param $posarray array
     * @return integer
     */
    function arrayToNumber($posarray)
    {
        $posnum = 0;
        $multiplier = 100000000;
        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 3; $j++) {
                $posnum = $posnum + $posarray[$i][$j] * $multiplier;  //pow(10, $pw);
                $multiplier = $multiplier / 10;
            }
        }
        return $posnum;
    }

    /**
     * Number to array
     * @param $posnum array
     * @return integer
     */
    function numberToArray($posnum)
    {
        $posarray = array();
        $divider = 100000000;
        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 3; $j++) {
                $posarray[$i][$j] = (int)($posnum / $divider);
                $posnum = $posnum % $divider;
                $divider = $divider / 10;
            }
        }
        return $posarray;
    }

}