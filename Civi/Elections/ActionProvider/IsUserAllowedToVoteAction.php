<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\Elections\ActionProvider;

use Civi\ActionProvider\Action\AbstractAction;
use Civi\ActionProvider\Exception\InvalidParameterException;
use Civi\ActionProvider\Parameter\ParameterBagInterface;
use Civi\ActionProvider\Parameter\Specification;
use Civi\ActionProvider\Parameter\SpecificationBag;

use CRM_Elections_ExtensionUtil as E;

class IsUserAllowedToVoteAction extends AbstractAction {

  /**
   * Run the action
   *
   * @param ParameterBagInterface $parameters
   *   The parameters to this action.
   * @param ParameterBagInterface $output
   *   The parameters this action can send back
   *
   * @return void
   */
  protected function doAction(ParameterBagInterface $parameters, ParameterBagInterface $output) {

  }

  /**
   * @return bool
   * @throws \Civi\ActionProvider\Exception\InvalidParameterException
   */
  protected function validateParameters(ParameterBagInterface $parameters) {
    $return = parent::validateParameters($parameters);
    $election_id = $this->configuration->getParameter('election_id');
    $voter_contact_id = $parameters->getParameter('voter_contact_id');
    $election = findElectionById($election_id);
    if (!$election->isVisible) {
      throw new InvalidParameterException('Voting is closed.');
    }
    if (!$election->isVotingStarted) {
      throw new InvalidParameterException('Voting is closed.');
    }
    if ($election->isVotingEnded) {
      throw new InvalidParameterException('Voting is closed.');
    }
    if (hasLoggedInUserAlreadyVoted($election_id, $voter_contact_id)) {
      throw new InvalidParameterException('User has already voted.');
    }
    return $return;
  }


  /**
   * Returns the specification of the configuration options for the actual
   * action.
   *
   * @return SpecificationBag
   */
  public function getConfigurationSpecification() {
    $elections = [];
    $electionsApi = civicrm_api3('Election', 'get', [
      'is_visible' => 1,
      'options' => ['limit' => 0],
    ]);
    foreach ($electionsApi['values'] as $election) {
      $elections[$election['id']] = $election['name'];
    }

    return new SpecificationBag([
      new Specification('election_id', 'Integer', E::ts('Election ID'), true, null, null, $elections),
    ]);
  }

  /**
   * Returns the specification of the parameters of the actual action.
   *
   * @return SpecificationBag
   */
  public function getParameterSpecification() {
    return new SpecificationBag([
      new Specification('voter_contact_id', 'Integer', E::ts('Voter Contact ID'), true),
    ]);
  }

}
