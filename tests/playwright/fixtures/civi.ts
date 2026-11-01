import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { forgetCouncil } from './personas';
import { CV } from './cms';

/**
 * Shells out to `cv` to read and change CiviCRM data directly, bypassing the browser,
 * so specs can assert on real database state (not only what is rendered) and move an
 * election between stages the way the passing of time would.
 *
 * CIVI_EXEC_PREFIX (set by the CI workflow to a `docker exec ...` command targeting the
 * WordPress container) is put in front of `cv`. When the tests run inside the container
 * itself (DDEV: `ddev exec`), leave it empty.
 *
 * With a prefix, the commands run where the extension is mounted at COMPONENT_DIR (also set by the
 * workflow), which is not where the tests see it on the runner, so script paths are mapped across.
 */
const EXTENSION_ROOT = path.resolve(__dirname, '..', '..', '..');

function inContainer(file: string): string {
  const dir = process.env.COMPONENT_DIR;
  if (!process.env.CIVI_EXEC_PREFIX || !dir) {
    return file;
  }
  return path.posix.join(dir, path.relative(EXTENSION_ROOT, file).split(path.sep).join('/'));
}

function execPrefixParts(): string[] {
  const prefix = process.env.CIVI_EXEC_PREFIX;
  return prefix ? prefix.split(' ').filter(Boolean) : [];
}

function run(args: string[], env: Record<string, string> = {}): string {
  const parts = [...execPrefixParts()];
  const envPairs = Object.entries(env).map(([k, v]) => `${k}=${v}`);
  if (envPairs.length) {
    parts.push('env', ...envPairs);
  }
  parts.push(...args);
  const [cmd, ...rest] = parts;
  return execFileSync(cmd, rest, { encoding: 'utf-8', env: { ...process.env, ...env }, stdio: ['ignore', 'pipe', 'pipe'] });
}

export function civiApi4<T = any>(entityDotAction: string, params: Record<string, unknown> = {}): T {
  return JSON.parse(run([CV, 'api4', entityDotAction, JSON.stringify(params)]));
}

/** Election stages in time order. */
export type Stage = 'nominations' | 'advertise' | 'voting' | 'results';

/**
 * Run a step of the persona fixture's election (see fixtures/advance-council.php).
 * Steps: fill-nominations, to-advertise, fill-responses, to-voting, fill-ballots,
 * to-results, generate.
 */
export function advanceCouncil(step: string): void {
  run([CV, 'scr', inContainer(path.resolve(__dirname, 'advance-council.php'))], { STEP: step });
}

/** Move the election's dates to a stage, as time passing would. */
export function moveToStage(stage: Stage): void {
  advanceCouncil({ nominations: 'to-nominations', advertise: 'to-advertise', voting: 'to-voting', results: 'to-results' }[stage]);
}

export function countRows(entity: string, where: unknown[][] = []): number {
  return civiApi4<any[]>(`${entity}.get`, { select: ['id'], where, limit: 0 }).length;
}

/** Run SQL straight against the database, for clean-up that the election rules would otherwise block. */
export function civiSql(sql: string, params: Record<string, string | number> = {}): void {
  const typed = Object.fromEntries(Object.entries(params).map(([k, v]) => [k, [v, typeof v === 'number' ? 'Integer' : 'String']]));
  const php = `CRM_Core_DAO::executeQuery(${JSON.stringify(sql)}, json_decode(${JSON.stringify(JSON.stringify(typed))}, TRUE));`;
  run([CV, 'ev', php]);
}

/** Evaluate PHP inside CiviCRM (`cv ev`) and return what it prints. */
export function civiEval(php: string): string {
  return run([CV, 'ev', php]);
}

/** Seed the council election again, at the nominations stage (see fixtures/seed-council.php). */
export function reseedCouncil(): void {
  run([CV, 'scr', inContainer(path.resolve(__dirname, 'seed-council.php'))]);
  forgetCouncil();
}

/** Ids of the nominations in the council election, keyed "Position|Nominee". */
export function councilNominationIds(electionId: number): Record<string, number> {
  const rows = civiApi4<Array<{ id: number; 'election_position_id.name': string; 'member_nominee.last_name': string; 'member_nominee.first_name': string }>>('ElectionNomination.get', {
    select: ['id', 'election_position_id.name', 'member_nominee.first_name', 'member_nominee.last_name'],
    where: [['election_position_id.election_id', '=', electionId]],
    limit: 0,
  });
  const ids: Record<string, number> = {};
  for (const row of rows) {
    // Nominees are named "Nominee 01" .. "Nominee 10" by the fixture.
    const persona = `N${row['member_nominee.last_name']}`;
    ids[`${row['election_position_id.name']}|${persona}`] = row.id;
  }
  return ids;
}
