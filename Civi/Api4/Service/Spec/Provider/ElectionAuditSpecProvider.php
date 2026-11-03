<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;
use CRM_Elections_ExtensionUtil as E;

/**
 * Two calculated fields on ElectionAudit for the Audit Log list in SearchKit, so the list shows words and not
 * stored values: what happened (action_label, the words of CRM_Elections_Helper_Audit::actionLabel()) and the
 * details as a short sentence (details_text) instead of the JSON that is stored. The stored values are never
 * changed: they are part of the hash chain.
 *
 * Details never contain how anyone voted, and neither does the text made from them.
 */
class ElectionAuditSpecProvider implements Generic\SpecProviderInterface {

  /**
   * The actions the extension writes (the first part is the kind of record, the second what was done to it).
   */
  const ACTIONS = [
    'election_created', 'election_changed', 'election_deleted',
    'position_created', 'position_changed', 'position_deleted',
    'nomination_created', 'nomination_changed', 'nomination_deleted',
    'results_generated', 'vote_removed', 'votes_anonymised', 'write_ins_summary',
  ];

  public function modifySpec(RequestSpec $spec): void {
    $action = new FieldSpec('action_label', 'ElectionAudit', 'String');
    $action->setLabel(E::ts('What happened'))
      ->setTitle(E::ts('What happened'))
      ->setDescription(E::ts('What was done, in words.'))
      ->setColumnName('action')
      ->setReadonly(TRUE)
      ->setType('Extra')
      ->setInputType('Text')
      ->setSqlRenderer([__CLASS__, 'renderActionLabel']);
    $spec->addFieldSpec($action);

    $details = new FieldSpec('details_text', 'ElectionAudit', 'String');
    $details->setLabel(E::ts('Details'))
      ->setTitle(E::ts('Details'))
      ->setDescription(E::ts('The details of the entry as a short sentence. Never shows how anyone voted.'))
      ->setColumnName('details')
      ->setReadonly(TRUE)
      ->setType('Extra')
      ->setInputType('Text')
      ->setSqlRenderer([__CLASS__, 'renderDetailsText']);
    $spec->addFieldSpec($details);

    // The contact is not a foreign key on purpose (a deleted contact must not change an entry, which is part of the
    // hash chain), so the name is looked up here.
    $who = new FieldSpec('changed_by_name', 'ElectionAudit', 'String');
    $who->setLabel(E::ts('Who'))
      ->setTitle(E::ts('Who'))
      ->setDescription(E::ts('The person who made the change. Empty for changes made by the system.'))
      ->setColumnName('contact_id')
      ->setReadonly(TRUE)
      ->setType('Extra')
      ->setInputType('Text')
      ->setSqlRenderer([__CLASS__, 'renderChangedByName']);
    $spec->addFieldSpec($who);
  }

  /**
   * @param array $field
   * @return string
   */
  public static function renderChangedByName(array $field): string {
    $contactId = self::column($field, 'contact_id');
    return "(SELECT c.display_name FROM civicrm_contact c WHERE c.id = $contactId)";
  }

  public function applies($entity, $action): bool {
    return $entity === 'ElectionAudit' && $action === 'get';
  }

  private static function escape(string $text): string {
    return \CRM_Core_DAO::escapeString($text);
  }

  /**
   * The SQL name of another column of the same table, given this field's.
   */
  private static function column(array $field, string $column): string {
    return preg_replace('/`[^`]+`$/', '`' . $column . '`', $field['sql_name']);
  }

  /**
   * A translated sentence with one value in it, as SQL: the sentence's own words around the value.
   *
   * @param string $sentence
   *   A translated sentence made with the marker \x01 where the value goes.
   * @param string $value
   *   An SQL expression.
   *
   * @return string
   */
  private static function sentence(string $sentence, string $value): string {
    [$before, $after] = explode("\x01", $sentence, 2) + ['', ''];
    $parts = [];
    if ($before !== '') {
      $parts[] = "'" . self::escape($before) . "'";
    }
    $parts[] = $value;
    if ($after !== '') {
      $parts[] = "'" . self::escape($after) . "'";
    }
    return 'CONCAT(' . implode(', ', $parts) . ')';
  }

  /**
   * @param array $field
   * @return string
   */
  public static function renderActionLabel(array $field): string {
    $action = self::column($field, 'action');
    $cases = '';
    foreach (self::ACTIONS as $code) {
      $cases .= " WHEN '$code' THEN '" . self::escape(\CRM_Elections_Helper_Audit::actionLabel($code)) . "'";
    }
    // An action that is not listed has its name turned into words, as actionLabel() does.
    $words = "REPLACE($action, '_', ' ')";
    return "CASE $action$cases ELSE CONCAT(UPPER(SUBSTRING($words, 1, 1)), SUBSTRING($words, 2)) END";
  }

  /**
   * @param array $field
   * @return string
   */
  public static function renderDetailsText(array $field): string {
    $d = self::column($field, 'details');
    $value = fn(string $key) => "JSON_UNQUOTE(JSON_EXTRACT($d, '$.$key'))";
    $has = fn(string $key) => "JSON_CONTAINS_PATH($d, 'one', '$.$key')";
    // The names of the fields that were set or changed, in words ("voting end date").
    $fields = "REPLACE(REPLACE(REPLACE(REPLACE(JSON_KEYS(JSON_EXTRACT($d, '$.changes')), '\"', ''), '[', ''), ']', ''), '_', ' ')";
    return "CASE WHEN $d IS NULL OR $d = '' OR NOT JSON_VALID($d) THEN ''"
      . " WHEN {$has('changes')} THEN " . self::sentence(E::ts('Fields: %1', [1 => "\x01"]), $fields)
      . " WHEN {$has('ballots')} THEN " . self::sentence(E::ts('Ballots counted: %1', [1 => "\x01"]), $value('ballots'))
      . " WHEN {$has('written_in')} THEN " . self::sentence(E::ts('People written in: %1', [1 => "\x01"]), $value('written_in'))
      . " WHEN {$has('rows')} THEN " . self::sentence(E::ts('Votes removed: %1', [1 => "\x01"]), $value('rows'))
      . " WHEN {$has('name')} THEN " . self::sentence(E::ts('Name: %1', [1 => "\x01"]), $value('name'))
      . " ELSE '' END";
  }

}
