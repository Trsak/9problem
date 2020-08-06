<?php
namespace App\Presenters;

use Nette,
    Nette\Application\UI\Form,
    App\Puzzle;

class HomepagePresenter extends BasePresenter
{
    /**
     * Array with all elements correctly sorted
     * @var array
     */
    private $elementsDone = [[1, 2, 3], [4, 5, 6], [7, 8, 0]];

    /**
     * Array with current state of elements
     * @var array
     */
    private $elementsNow = [[1, 2, 3], [4, 5, 6], [7, 8, 0]];

    /**
     * Set all default variables on start
     */
    public function actionDefault()
    {
        //Template variables
        $this->template->elements = $this->elementsNow;
        $this->template->elementsJson = json_encode($this->elementsNow);
        $this->template->sequences = [];
        $this->template->count = 0;

        //Create json filter for latte
        $this->template->addFilter('json', function ($s) {
            return json_encode($s);
        });
    }

    /**
     * Handles event to change elements
     * @param $elements
     */
    public function handleChange($elements)
    {
        $this->template->elementsJson = json_encode($elements);
        $elements = json_decode($elements);
        $this->template->elements = $elements;
        $this->redrawControl('elements');
    }

    /**
     * Handles event to random generate puzzle
     */
    public function handleRandomGenerate()
    {
        if ($this->isAjax()) {
            $elements = $this->elementsDone;

            //Make 50 random moves
            for ($i = 0; $i < 50; ++$i) {
                $elements = $this->randomMove($elements);
            }

            $this->template->elements = $elements;
            $this->template->elementsJson = json_encode($elements);
            $this->elementsNow = $elements;
            $this->flashMessage("Pole bylo úspěšně náhodně vygenerováno!", "success");
            $this->redrawControl('elements');
            $this->redrawControl('elementsSettings');
            $this->redrawControl('play');
            $this->redrawControl('sequence');
            $this->redrawControl('errors');
        }
    }

    /**
     * Handles event to solve the puzzle
     * @param $elementsNow - current state of elements
     */
    public function handlePlay($elementsNow)
    {
        if ($this->isAjax()) {
            $elementsNow = json_decode($elementsNow);
            $elements = [$elementsNow[0][0], $elementsNow[0][1], $elementsNow[0][2], $elementsNow[1][0], $elementsNow[1][1], $elementsNow[1][2], $elementsNow[2][0], $elementsNow[2][1], $elementsNow[2][2]];

            if ($this->elementsDone == $elementsNow) { //Check if puzzle isn't solved yet
                $this->flashMessage("Pole je již správně poskládáno!", "success");
            } elseif (!$this->isSolvable($elements)) { //Check if puzzle is solvable
                $this->flashMessage("Toto puzzle se nedá vyřešit!", "test");
            } else { //Solve puzzle
                $solFound = FALSE; //Search will stop, when solution is found

                $initial_state = new Puzzle();
                $initial_state->setInitial($elementsNow); //Set current position
                $initial_state->setGoalpos($this->elementsDone); //Set goal position
                $initial_state->evaluate($elementsNow);

                $steps = 1;  //Current steps made
                $bound = $initial_state->path_cost + $initial_state->val;

                while (!$solFound) { //Until solution is found
                    $current_state = clone $initial_state;
                    $min = $this->solve($current_state, $bound, $steps); //Start solving the puzzle
                    if ($min == 1) {
                        $result = $current_state->getSequences(); //Get all sequences

                        $this->template->sequences = $result;
                        $this->template->count = count($result) - 1;

                        $this->flashMessage("Puzzle se podařilo vyřešit v " . (count($result) - 1) . " krocích!", "success");
                        $this->redrawControl('errors');
                        $this->redrawControl('sequence');
                        $solFound = True;
                    }
                    $bound = $min;
                }


                $this->redrawControl('errors');
            }

            $this->flashMessage("close", "close");
            $this->redrawControl('errors');
        }
    }

    /**
     * Handles event to solve the puzzle
     * @param $current_state Puzzle
     * @param $bound integer
     * @param $steps integer - number of nodes
     * @return integer
     */
    public function solve(Puzzle &$current_state, $bound, &$steps)
    {
        //Create stack
        $nodeQue = new \SplStack();
        $nodeQue->push($current_state);
        $nodeQue->rewind();
        $min = 10000;

        while ($nodeQue->valid()) {

            $steps++;
            $current_state = $nodeQue->pop();
            if ($current_state->goalTest() == TRUE) {
                return TRUE;
            }
            $moves = $current_state->possibleMoves();

            foreach ($moves as $move) {
                $a_star = $move->path_cost + $move->val;
                if ($a_star <= $bound) {
                    $nodeQue->push($move);
                } else {
                    if ($min > $a_star) {
                        $min = $a_star;
                    }
                }
            }
            $nodeQue->rewind();
        }

        return $min;
    }

    /**
     * Create form with elements
     * @return Form
     */
    protected function createComponentChangeForm()
    {
        $form = new Form;
        $form->addText('elementS1', '');
        $form->addText('elementS2', '');
        $form->addText('elementS3', '');
        $form->addText('elementS4', '');
        $form->addText('elementS5', '');
        $form->addText('elementS6', '');
        $form->addText('elementS7', '');
        $form->addText('elementS8', '');
        $form->addText('elementS9', '');
        $form->addSubmit('potvrdit', 'Potvrdit');
        $form->onSuccess[] = array($this, 'changeFormSucceeded');
        return $form;
    }

    /**
     * Change elements using form
     * @param $form Form
     * @param $values array
     */
    public function changeFormSucceeded(Form $form, $values)
    {
        $list = [0, 1, 2, 3, 4, 5, 6, 7, 8];
        $listUser = [];

        for ($i = 1; $i < 10; ++$i) {
            if ($values["elementS" . $i] == "") {
                $values["elementS" . $i] = 0;
            }

            $listUser[] = $values["elementS" . $i];
        }

        $diff = array_diff($list, $listUser);


        if (count($diff) == 0) {
            $elements = [[$values["elementS1"], $values["elementS2"], $values["elementS3"]], [$values["elementS4"], $values["elementS5"], $values["elementS6"]], [$values["elementS7"], $values["elementS8"], $values["elementS9"]]];
            $this->template->elements = $elements;
            $this->template->elementsJson = json_encode($elements);
            $this->elementsNow = $elements;
            $this->flashMessage("Pole bylo úspěšně upraveno!", "success");
            $this->redrawControl('elements');
            $this->redrawControl('elementsSettings');
            $this->redrawControl('play');
            $this->redrawControl('sequence');
            $this->redrawControl('errors');
        } else {
            $this->flashMessage("settingsValues", "error");
            $this->redrawControl('errors');
        }
    }

    /**
     * Find space in the puzzle
     * @param $elements array
     * @return array
     */
    private function findSpace($elements)
    {
        $col = false;
        $row = 0;

        for ($i = 0; $col === false; ++$i) {
            $col = array_search('0', $elements[$i]);
            $row = $i;
        }

        return [$row, $col];
    }

    /**
     * Find all the actions
     * @param $pos array
     * @return array
     */
    private function findActions($pos)
    {
        $row = $pos[0];
        $col = $pos[1];

        $actions[] = $col - 1;
        $actions[] = $col + 1;
        $actions[] = $row - 1;
        $actions[] = $row + 1;

        for ($i = 0; $i < count($actions); ++$i) {
            if ($actions[$i] < 0 or $actions[$i] > 2) {
                $actions[$i] = false;
            }
        }

        return $actions;
    }

    /**
     * Find number of possible actions
     * @param $actions array
     * @return integer
     */
    private function findPossibleActions($actions)
    {
        foreach ($actions as $key => $value) {
            if ($value === false) {
                unset($actions[$key]);
            }
        }

        return count($actions);
    }

    /**
     * Select the action
     * @param $actions array
     * @param $action integer
     * @param $pos array
     * @return array
     */
    private function selectAction($actions, $action, $pos)
    {
        $x = 0;
        $row = $pos[0];
        $col = $pos[1];

        for ($i = 0; $i < count($actions); $i++) {

            if ($actions[$i] !== false) {
                if ($action == $x + 1) {
                    if ($i == 0) {
                        $col -= 1;
                    } elseif ($i == 1) {
                        $col += 1;
                    } elseif ($i == 2) {
                        $row -= 1;
                    } else {
                        $row += 1;
                    }
                }
                ++$x;
            }
        }

        return [$row, $col];
    }

    /**
     * Perform the action
     * @param $elements array
     * @param $space array
     * @param $move array
     * @return array
     */
    private function performAction($elements, $space, $move)
    {
        $toRow = $space[0];
        $toCOL = $space[1];

        $fromRow = $move[0];
        $fromCOL = $move[1];

        $elements[$toRow][$toCOL] = $elements[$fromRow][$fromCOL];
        $elements[$fromRow][$fromCOL] = 0;

        return $elements;
    }

    /**
     * Make the random move
     * @param $elements array
     * @return array
     */
    private function randomMove($elements)
    {
        $pos = $this->findSpace($elements);

        $actions = $this->findActions($pos);

        $possibleActions = $this->findPossibleActions($actions);

        $random = mt_rand(1, $possibleActions);

        $move = $this->selectAction($actions, $random, $pos);

        $perform = $this->performAction($elements, $pos, $move);

        return $perform;
    }

    /**
     * Check if puzzle is solveable
     * @param $elements array
     * @return boolean
     */
    public function isSolvable($elements)
    {
        $inversions = 0;

        for ($i = 0; $i < count($elements) - 1; ++$i) {
            for ($x = $i + 1; $x < count($elements); ++$x) {
                if ($elements[$i] > $elements[$x] and $elements[$x] > 0) {
                    ++$inversions;
                }
            }
        }

        return ($inversions % 2 == 0);
    }
}

