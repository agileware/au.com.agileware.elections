<?php

namespace Civi\Api4\Action\ElectionPosition;

use Civi\Api4\Generic\DAOSaveAction;

/**
 * Election position save that respects the rule for elections that have started.
 */
class Save extends DAOSaveAction {
  use GuardsStartedElections;

}
