<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;
use CRM_Elections_ExtensionUtil as E;

/**
 * Two calculated fields on Election for SearchKit: where the election is now (stage) and whether the
 * election is active, which means switched on with its nominations started, so it can no longer be
 * edited, deleted or have its positions changed (is_locked). The rule is the one in
 * CRM_Elections_BAO_Election::canEdit() and canDelete().
 *
 * CiviCRM finds spec providers in this namespace in an extension without any extra set up.
 */
class ElectionSpecProvider implements Generic\SpecProviderInterface {

  const STAGE_NOT_RUNNING = 'not_running';

  const STAGE_NOMINATIONS = 'nominations';

  const STAGE_CANDIDATES = 'candidates';

  const STAGE_VOTING = 'voting';

  const STAGE_VOTING_CLOSED = 'voting_closed';

  const STAGE_RESULTS = 'results';

  /**
   * @return string[]
   *   Stage => label.
   */
  public static function getStages(): array {
    return [
      self::STAGE_NOT_RUNNING => E::ts('Not running'),
      self::STAGE_NOMINATIONS => E::ts('Nominations open'),
      self::STAGE_CANDIDATES => E::ts('Candidates announced'),
      self::STAGE_VOTING => E::ts('Voting open'),
      self::STAGE_VOTING_CLOSED => E::ts('Voting closed'),
      self::STAGE_RESULTS => E::ts('Results published'),
    ];
  }

  public function modifySpec(RequestSpec $spec): void {
    $locked = new FieldSpec('is_locked', 'Election', 'Boolean');
    $locked->setLabel(E::ts('Active (locked)'))
      ->setTitle(E::ts('Active (locked)'))
      ->setDescription(E::ts('Switched on and nominations have started, so the election and its positions can no longer be changed.'))
      ->setColumnName('id')
      ->setReadonly(TRUE)
      ->setType('Extra')
      ->setInputType('Toggle')
      ->setSqlRenderer([__CLASS__, 'renderIsLocked']);
    $spec->addFieldSpec($locked);

    $stage = new FieldSpec('stage', 'Election', 'String');
    $stage->setLabel(E::ts('Stage'))
      ->setTitle(E::ts('Stage'))
      ->setDescription(E::ts('Where the election is now. Not running means switched off or nominations have not started.'))
      ->setColumnName('id')
      ->setReadonly(TRUE)
      ->setType('Extra')
      ->setInputType('Select')
      ->setOptions(self::getStages())
      ->setSqlRenderer([__CLASS__, 'renderStage']);
    $spec->addFieldSpec($stage);
  }

  public function applies($entity, $action): bool {
    return $entity === 'Election' && $action === 'get';
  }

  /**
   * The SQL name of another column of the same table, given this field's.
   */
  private static function column(array $field, string $column): string {
    return preg_replace('/`[^`]+`$/', '`' . $column . '`', $field['sql_name']);
  }

  /**
   * @param array $field
   * @return string
   */
  public static function renderIsLocked(array $field): string {
    $now = date('Y-m-d H:i:s');
    $visible = self::column($field, 'is_visible');
    $start = self::column($field, 'nomination_start_date');
    return "IF($visible = 1 AND $start IS NOT NULL AND $start <= '$now', 1, 0)";
  }

  /**
   * @param array $field
   * @return string
   */
  public static function renderStage(array $field): string {
    $now = date('Y-m-d H:i:s');
    $col = fn($name) => self::column($field, $name);
    $passed = fn($name) => '(' . $col($name) . " IS NOT NULL AND " . $col($name) . " <= '$now')";
    return "CASE WHEN {$col('is_visible')} <> 1 THEN '" . self::STAGE_NOT_RUNNING . "'"
      . " WHEN {$col('has_results_generated')} = 1 AND {$passed('result_date')} THEN '" . self::STAGE_RESULTS . "'"
      . " WHEN {$passed('voting_end_date')} THEN '" . self::STAGE_VOTING_CLOSED . "'"
      . " WHEN {$passed('voting_start_date')} THEN '" . self::STAGE_VOTING . "'"
      . " WHEN {$passed('advertise_candidates_date')} THEN '" . self::STAGE_CANDIDATES . "'"
      . " WHEN {$passed('nomination_start_date')} THEN '" . self::STAGE_NOMINATIONS . "'"
      . " ELSE '" . self::STAGE_NOT_RUNNING . "' END";
  }

}
