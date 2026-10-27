<?php

namespace Civi\Api4\Action\ElectionPosition;

use Civi\Api4\Generic\DAOCreateAction;

/**
 * Election position create that respects the rule for elections that have started.
 */
class Create extends DAOCreateAction {
  use GuardsStartedElections;

}
