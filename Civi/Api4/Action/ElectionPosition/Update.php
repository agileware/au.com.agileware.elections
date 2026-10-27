<?php

namespace Civi\Api4\Action\ElectionPosition;

use Civi\Api4\Generic\DAOUpdateAction;

/**
 * Election position update that respects the rule for elections that have started.
 */
class Update extends DAOUpdateAction {
  use GuardsStartedElections;

}
