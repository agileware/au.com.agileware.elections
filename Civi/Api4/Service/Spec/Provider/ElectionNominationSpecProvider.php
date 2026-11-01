<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;
use CRM_Elections_ExtensionUtil as E;

/**
 * Where a nomination stands, in one word, for SearchKit: a nominee who has not accepted, a candidate who has,
 * one who said no before accepting (declined) and one who pulled out after accepting (withdrawn).
 *
 * CiviCRM finds spec providers in this namespace in an extension without any extra set up.
 */
class ElectionNominationSpecProvider implements Generic\SpecProviderInterface {

  const NOMINEE = 'nominee';

  const CANDIDATE = 'candidate';

  const DECLINED = 'declined';

  const WITHDRAWN = 'withdrawn';

  /**
   * @return string[]
   *   Status => label.
   */
  public static function getStatuses(): array {
    return [
      self::NOMINEE => E::ts('Nominee'),
      self::CANDIDATE => E::ts('Candidate'),
      self::DECLINED => E::ts('Declined'),
      self::WITHDRAWN => E::ts('Withdrawn'),
    ];
  }

  public function applies($entity, $action): bool {
    return $entity === 'ElectionNomination' && $action === 'get';
  }

  public function modifySpec(RequestSpec $spec): void {
    $status = new FieldSpec('nomination_status', 'ElectionNomination', 'String');
    $status->setLabel(E::ts('Status'))
      ->setTitle(E::ts('Status'))
      ->setDescription(E::ts('Nominee has not accepted yet, candidate has accepted, declined said no before accepting, withdrawn pulled out after accepting.'))
      ->setColumnName('id')
      ->setReadonly(TRUE)
      ->setType('Extra')
      ->setInputType('Select')
      ->setOptions(self::getStatuses())
      ->setSqlRenderer([__CLASS__, 'renderStatus']);
    $spec->addFieldSpec($status);
  }

  /**
   * @param array $field
   * @return string
   */
  public static function renderStatus(array $field): string {
    $col = fn(string $column) => preg_replace('/`[^`]+`$/', '`' . $column . '`', $field['sql_name']);
    return "CASE WHEN IFNULL({$col('has_withdrawn_nomination')}, 0) = 1 THEN '" . self::WITHDRAWN . "'"
      . " WHEN IFNULL({$col('has_rejected_nomination')}, 0) = 1 THEN '" . self::DECLINED . "'"
      . " WHEN {$col('is_eligible_candidate')} = 1 AND IFNULL({$col('has_accepted_nomination')}, 0) = 1 THEN '" . self::CANDIDATE . "'"
      . " ELSE '" . self::NOMINEE . "' END";
  }

}
