<?php

namespace Civi\Api4\Action\Election;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * Copy an election and its positions as a new, inactive election.
 *
 * Nominations, votes and results are not copied. Every date moves by the same amount.
 *
 * @method $this setId(int $id)
 * @method int getId()
 * @method $this setName(string $name)
 * @method string|null getName()
 * @method $this setShiftDays(int $days)
 * @method int|null getShiftDays()
 */
class Duplicate extends AbstractAction {

  /**
   * The election to copy.
   *
   * @var int
   * @required
   */
  protected $id;

  /**
   * Name of the copy. Defaults to "Copy of <name>".
   *
   * @var string|null
   */
  protected $name;

  /**
   * Move all dates by this many days (can be negative). Defaults to making the
   * visibility start date one day from now.
   *
   * @var int|null
   */
  protected $shiftDays;

  public function _run(Result $result) {
    $shiftSeconds = $this->shiftDays === NULL ? NULL : (int) $this->shiftDays * 86400;
    $copy = \CRM_Elections_BAO_Election::duplicate($this->id, $this->name, $shiftSeconds);
    $result[] = $copy;
  }

}
