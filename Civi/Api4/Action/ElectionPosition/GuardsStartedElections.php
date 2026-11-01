<?php

namespace Civi\Api4\Action\ElectionPosition;

/**
 * Refuses to write positions of an election that has started, before anything is changed.
 *
 * CiviCRM makes room for a moved position (the weights of the others) before it writes the position,
 * so the rule has to be checked first or a refused move would still shuffle the other positions.
 */
trait GuardsStartedElections {

  protected function writeObjects($items) {
    foreach ($items as $item) {
      \CRM_Elections_BAO_ElectionPosition::assertItemCanChange($item);
    }
    return parent::writeObjects($items);
  }

}
