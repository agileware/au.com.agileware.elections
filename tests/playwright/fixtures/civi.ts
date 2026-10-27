import { execFileSync } from 'node:child_process';
import path from 'node:path';

/**
 * Shells out to `cv` to read and change CiviCRM data directly, bypassing the browser,
 * so specs can assert on real database state (not only what is rendered) and move an
 * election between stages the way the passing of time would.
 *
 * CIVI_EXEC_PREFIX (set by the CI workflow to a `docker exec ...` command targeting the
 * WordPress container) is put in front of `cv`. When the tests run inside the container
 * itself (DDEV: `ddev exec`), leave it empty.
 */
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
  return JSON.parse(run(['cv', 'api4', entityDotAction, JSON.stringify(params)]));
}

/** Election stages in time order. */
export type Stage = 'nominations' | 'advertise' | 'voting' | 'results';

/**
 * Run a step of the persona fixture's election (see fixtures/advance-council.php).
 * Steps: fill-nominations, to-advertise, fill-responses, to-voting, fill-ballots,
 * to-results, generate.
 */
export function advanceCouncil(step: string): void {
  run(['cv', 'scr', path.resolve(__dirname, 'advance-council.php')], { STEP: step });
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
  run(['cv', 'ev', php]);
}

/** Evaluate PHP inside CiviCRM (`cv ev`) and return what it prints. */
export function civiEval(php: string): string {
  return run(['cv', 'ev', php]);
}
