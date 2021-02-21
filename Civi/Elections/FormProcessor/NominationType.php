<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\Elections\FormProcessor;

use Civi\FormProcessor\Config\Specification;
use Civi\FormProcessor\Config\SpecificationBag;
use Civi\FormProcessor\Type\AbstractType;
use Civi\FormProcessor\Type\OptionListInterface;

use CRM_Elections_ExtensionUtil as E;

class NominationType extends AbstractType implements OptionListInterface {

  public function __construct() {
    parent::__construct('election_nomination', E::ts('Nomination'));
  }


  /**
   * Get the configuration specification
   *
   * @return SpecificationBag
   */
  public function getConfigurationSpecification() {
    $positions = [];
    $electionsApi = civicrm_api3('Election', 'get', [
      'is_visible' => 1,
      'options' => ['limit' => 0],
    ]);
    foreach ($electionsApi['values'] as $election) {
      $positionsApi = civicrm_api3('ElectionPosition', 'get', [
        'election_id' => $election['id'],
        'options' => ['limit' => 0],
      ]);
      foreach ($positionsApi['values'] as $position) {
        $positions[$position['id']] = $election['name']. ': '.$position['name'];
      }
    }

    return new SpecificationBag([
      new Specification('position_id', 'Integer', E::ts('Position'), TRUE, NULL, NULL, $positions, FALSE),
    ]);
  }


  /**
   * Returns whether the provided value is a valid
   *
   * @param mixed $value
   * @param array $allValues
   *
   * @return bool
   */
  public function validateValue($value, $allValues = []) {
    $options = $this->getOptions($allValues);
    if (isset($options[$value])) {
      return true;
    }
    return false;
  }

  /**
   * Returns the type number from CRM_Utils_Type
   */
  public function getCrmType() {
    return \CRM_Utils_Type::T_INT;
  }

  /**
   * Returns an array with the options.
   *
   * The key of the array is the value and the array item is the label.
   *
   * @param array $params
   *   This array contains the params used to call getFields. This way we could
   *   build conditional option lists.
   *
   * @return array
   */
  public function getOptions($params) {
    $position_id = $this->configuration->get('position_id');
    $nominationsApi = civicrm_api3('ElectionNomination', 'get', [
      'election_position_id' => $position_id,
      'has_accepted_nomination' => 1,
      'is_eligible_candidate' => 1,
      'options' => ['limit' => 0],
    ]);
    $nominations = [];
    foreach($nominationsApi['values'] as $nomination) {
      $display_name = $nomination['id'];
      try {
        $display_name = civicrm_api3('Contact', 'getvalue', [
          'id' => $nomination['member_nominee'],
          'return' => 'display_name'
        ]);
      } catch (\CiviCRM_API3_Exception $ex) {
        // Do nothing.
      }
      $nominations[$nomination['id']] = $display_name;
    }
    return $nominations;
  }

  /**
   * Returns true when this field is a multiple field.
   *
   * @return bool
   */
  public function isMultiple() {
    return FALSE;
  }


}
