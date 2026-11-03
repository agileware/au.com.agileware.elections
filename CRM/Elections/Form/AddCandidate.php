<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Add a candidate directly, as an administrator, for elections where nominations are made
 * outside the website.
 */
class CRM_Elections_Form_AddCandidate extends CRM_Elections_Form_Base {

  private $eId = 0;

  private $election = NULL;

  public function buildQuickForm() {
    if (throwUnauthorizedMessageIfRequired($this)) {
      return;
    }
    $this->eId = CRM_Utils_Request::retrieve('eid', 'Positive', $this, TRUE);
    $this->election = findElectionById($this->eId);
    CRM_Utils_System::setTitle(E::ts('Add Candidate'));
    CRM_Elections_Helper_Frame::assignToForm($this, CRM_Elections_Helper_Frame::trail((int) $this->eId, [[E::ts('Candidates'), CRM_Elections_Helper_Frame::candidatesUrl((int) $this->eId)]], E::ts('Add Candidate')), CRM_Elections_Helper_Frame::candidatesUrl((int) $this->eId));

    $positions = [];
    foreach (civicrm_api3('ElectionPosition', 'get', ['election_id' => $this->eId, 'options' => ['limit' => 0, 'sort' => 'sortorder ASC']])['values'] as $position) {
      if (($position['position_type'] ?? '') === CRM_Elections_BAO_ElectionPosition::TYPE_REFERENDUM) {
        continue;
      }
      $positions[$position['id']] = $position['name'];
    }
    $groups = array_filter(explode(',', CRM_Elections_Helper_Eligibility::groupsFor($this->election, 'candidate')));

    $this->add('select', 'position', E::ts('Position'), ['' => E::ts('Select a position')] + $positions, TRUE);
    $this->assign('hasPositions', count($positions) > 0);
    $this->assign('positionsUrl', CRM_Elections_Helper_Frame::positionsUrl((int) $this->eId));
    $this->addEntityRef('contact', E::ts('Candidate'), [
      'entity' => 'Contact',
      'placeholder' => E::ts('Select a person'),
      'api' => ['params' => ['group' => ['IN' => $groups]]],
    ], TRUE);
    $this->add('advcheckbox', 'accepted', E::ts('Mark as accepted'));
    $this->add('textarea', 'statement', E::ts('Statement'), ['cols' => 55, 'rows' => 5], FALSE);
    $this->addElement('hidden', 'eid', $this->eId);
    CRM_Elections_Helper_Frame::addSubmitButtons($this);
    $this->assign('election', $this->election);
    $this->assign('contactsHidden', CRM_Elections_Helper_Visibility::contactsHidden(array_map('intval', $groups)));
    parent::buildQuickForm();
  }

  public function setDefaultValues() {
    return $this->restoreInput(['accepted' => 1]);
  }

  public function postProcess() {
    $values = $this->exportValues();
    try {
      CRM_Elections_BAO_ElectionNomination::addCandidate($values['position'], $values['contact'], !empty($values['accepted']), $values['statement'] ?: NULL);
    }
    catch (CRM_Core_Exception $e) {
      // The form stays, with what was entered, so the person can correct it.
      $this->stayOnForm($e->getMessage(), E::ts('Candidate not added'));
      return;
    }
    CRM_Core_Session::setStatus(E::ts('Candidate added.'), '', 'success');
    $this->finish(CRM_Utils_System::url('civicrm/elections/candidate-list', 'reset=1', FALSE, '/?eid=' . (int) $this->eId));
  }

}
