import fs from 'node:fs';
import path from 'node:path';
import { frontUrl } from './cms';

/**
 * The council election personas written by fixtures/seed-council.php:
 * V01..V30 voters, N01..N10 nominees (N01..N06 become candidates), OUT an outsider.
 * Each has a personal link (cid + cs checksum), so no passwords are needed on the front end.
 */
export type Persona = { cid: number; cs: string };

/** [position, nominee, nominators, what the nominee does] */
export type PlanRow = [string, string, string[], 'accept' | 'withdraw' | 'ignore' | 'accept_then_withdraw' | 'unseconded'];

export type CouncilData = {
  election_id: number;
  group_id: number;
  positions: Record<string, number>;
  links: Record<string, Persona>;
  plan: PlanRow[];
  /** Candidates expected in each position once the nominees have responded. */
  candidates: Record<string, string[]>;
  /** Each member's ballot: persona -> position -> candidate persona -> rank. */
  ballots: Record<string, Record<string, Record<string, number>>>;
};

export const POSITIONS = ['President', 'Secretary', 'Treasurer', 'Council Member 1', 'Council Member 2', 'Council Member 3'];

let cached: CouncilData | undefined;

/** Read the persona file again (after the election has been seeded again). */
export function forgetCouncil() {
  cached = undefined;
}

export function council(): CouncilData {
  if (!cached) {
    const file = path.resolve(__dirname, '.generated', 'council.json');
    if (!fs.existsSync(file)) {
      throw new Error(`${file} is missing - run fixtures/setup-environment.sh first`);
    }
    cached = JSON.parse(fs.readFileSync(file, 'utf-8'));
  }
  return cached!;
}

/** Front end CiviCRM address for a persona: /?page=CiviCRM&q=<path>&eid=..&cid=..&cs=.. */
export function personaUrl(persona: string, civiPath: string, params: Record<string, string | number> = {}): string {
  const data = council();
  const link = data.links[persona];
  if (!link) {
    throw new Error(`Unknown persona ${persona}`);
  }
  return frontUrl(civiPath, { eid: data.election_id, ...params, cid: link.cid, cs: link.cs });
}

export const voters = Array.from({ length: 30 }, (_, i) => `V${String(i + 1).padStart(2, '0')}`);
export const nominees = Array.from({ length: 10 }, (_, i) => `N${String(i + 1).padStart(2, '0')}`);
