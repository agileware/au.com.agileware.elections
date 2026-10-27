<?php
/**
 * Moves the Playwright council election along, filling in what personas have not done
 * in the browser. Run with `cv scr` and STEP set (see civi.ts).
 *
 * Steps: to-nominations, fill-nominations, to-advertise, fill-responses, to-voting,
 * fill-ballots, to-results, generate.
 */
require_once __DIR__ . '/../../phpunit/CRM/Elections/Test/CouncilFixture.php';

use CRM_Elections_Test_CouncilFixture as Council;

$fixture = Council::fromArray(json_decode(file_get_contents(__DIR__ . '/.generated/council-personas.json'), TRUE));
$step = getenv('STEP');
switch ($step) {
  case 'to-nominations': $fixture->moveToStage(Council::STAGE_NOMINATIONS); break;
  case 'fill-nominations': $fixture->runNominations(); break;
  case 'to-advertise': $fixture->moveToStage(Council::STAGE_ADVERTISE); break;
  case 'fill-responses': $fixture->reloadNominations(); $fixture->runResponses(); break;
  case 'to-voting': $fixture->moveToStage(Council::STAGE_VOTING); break;
  case 'fill-ballots': $fixture->reloadNominations(); $fixture->castBallots(); break;
  case 'to-results': $fixture->moveToStage(Council::STAGE_RESULTS); break;
  case 'generate': $fixture->generateResults(); break;
  default:
    fwrite(STDERR, "Unknown STEP '$step'\n");
    exit(1);
}
echo "done $step\n";
