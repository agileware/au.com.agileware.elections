<?php

use Civi\Token\Event\TokenRegisterEvent;
use Civi\Token\Event\TokenValueEvent;
use Civi\Token\TokenRow;
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_Tokens {
	const TOKEN = 'election';
	
	/**
	 * @param \Civi\Token\Event\TokenRegisterEvent $entity
	 * @param string $field Machine name for the token
	 * @param string $label the translated token label
	 *
	 * @return string
	 */
	protected static function registerCtx(TokenRegisterEvent $entity, string $field, string $label){
		$entity->register($field, $label . ' :: ' . E::ts('Elections') );
	}

	public static function register(TokenRegisterEvent $e) {
		$context = $e->getTokenProcessor()->context;
		if(!is_array($context['schema'] ?? NULL))
			return;

		// Register Election tokens for Activity
		if (in_array('activityId', $context['schema'])) {
			$entity = $e->entity(self::TOKEN);

			self::registerCtx($entity, 'election_name', E::ts('Election Name'));
			self::registerCtx($entity, 'election_position', E::ts('Election Position'));
			self::registerCtx($entity, 'nominator_name', E::ts('Nominator Name'));
			self::registerCtx($entity, 'nominee_name', E::ts('Nominee Name'));
		}
	}

	public static function evaluate(TokenValueEvent $e) {
		foreach($e->getRows() as $row) {
			self::evaluateRow($row);
		}
	}

	protected static function evaluateRow(TokenRow $row) {
		if (empty($row->context['activityId'])) {
			return;
		}
		$row->format('text/html');
		try {
			$activityInfo = civicrm_api3('Activity', 'getsingle', [
				'id'     => $row->context['activityId'],
				'return' => [
				  'source_record_id',
				  'activity_type_id.name',
				  'is_star',
				],
			]);
	
			if (isset($activityInfo['activity_type_id.name']) && $activityInfo['activity_type_id.name'] === 'Nomination') {
				$electionNominationInfo = civicrm_api3('ElectionNominationSeconder', 'getsingle', [
					'id'         => $activityInfo['source_record_id'],
					'sequential' => TRUE,
					'return'     => [
						'election_nomination_id.election_position_id.election_id.name',
						'election_nomination_id.election_position_id.name',
						'member_nominator.display_name',
						'election_nomination_id.member_nominee.display_name',
					],
				]);
	
				$row->tokens(self::TOKEN, 'election_name', $electionNominationInfo['election_nomination_id.election_position_id.election_id.name'] ?? '');
				$row->tokens(self::TOKEN, 'election_position', $electionNominationInfo['election_nomination_id.election_position_id.name'] ?? '');
				$row->tokens(self::TOKEN, 'nominator_name', $electionNominationInfo['member_nominator.display_name'] ?? '');
				$row->tokens(self::TOKEN, 'nominee_name', $electionNominationInfo['election_nomination_id.member_nominee.display_name'] ?? '');
			}
	
			if (isset($activityInfo['activity_type_id.name']) && $activityInfo['activity_type_id.name'] === 'Vote') {
				$electionInfo = civicrm_api3('Election', 'getsingle', [
					'id'         => $activityInfo['source_record_id'],
					'sequential' => TRUE,
					'return'     => [
						'name',
					],
				]);
	
				$row->tokens(self::TOKEN, 'election_name', $electionInfo['name'] ?? '');
			}
		}
		catch(CRM_Core_Exception $e) {
			// Silence is golden
		}
	}
	
}
