<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\Elections;

use Civi\ActionProvider\Action\AbstractAction;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use CRM_Elections_ExtensionUtil as E;
use Symfony\Component\DependencyInjection\Definition;

class CompilerPass implements CompilerPassInterface {

  /**
   * You can modify the container here before it is dumped to PHP code.
   */
  public function process(ContainerBuilder $container) {
    if ($container->hasDefinition('action_provider')) {
      $typeFactoryDefinition = $container->getDefinition('action_provider');
      $typeFactoryDefinition->addMethodCall('addAction', [
        'ElectionVote',
        'Civi\Elections\ActionProvider\VoteAction',
        E::ts('Add a vote'),
        [
          AbstractAction::SINGLE_CONTACT_ACTION_TAG,
          AbstractAction::DATA_RETRIEVAL_TAG
        ]
      ]);
    }

    if ($container->hasDefinition('form_processor_type_factory')) {
      $formProcessorTypeFactoryDefinition = $container->getDefinition('form_processor_type_factory');
      $formProcessorTypeFactoryDefinition->addMethodCall('addType', [new Definition('Civi\Elections\FormProcessor\NominationType')]);
    }

  }


}
