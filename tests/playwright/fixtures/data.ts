import { civiApi4, civiSql } from './civi';

/**
 * Throwaway elections for the back end specs. Every run uses its own names (suffix RUN) so a
 * re-run never meets rows left by an earlier one, and removeRunData() clears what it made.
 */
export const RUN = Date.now().toString(36);

export const nm = (base: string) => `PW ${base} ${RUN}`;

export const day = (n: number) => new Date(Date.now() + n * 86400000).toISOString().slice(0, 19).replace('T', ' ');

type Options = {
  active?: boolean;
  /** Days from now the nominations open. Negative means the election has started. */
  nominationsOpenIn?: number;
};

/** An election created straight in the database, inactive and not yet started by default. */
export function createElection(name: string, options: Options = {}): number {
  const open = options.nominationsOpenIn ?? 1;
  const [created] = civiApi4<Array<{ id: number }>>('Election.create', {
    values: {
      name,
      is_visible: false,
      is_deleted: false,
      visibility_start_date: day(open - 6),
      nomination_start_date: day(open),
      nomination_end_date: day(open + 2),
      advertise_candidates_date: day(open + 3),
      voting_start_date: day(open + 4),
      voting_end_date: day(open + 8),
      result_date: day(open + 9),
      visibility_end_date: day(open + 30),
      required_nominations: 2,
    },
  });
  return created.id;
}

export function addPosition(electionId: number, name: string, order: number, seats = 1): number {
  return civiApi4<Array<{ id: number }>>('ElectionPosition.create', {
    values: { election_id: electionId, name, quantity: seats, sortorder: order },
  })[0].id;
}

/** Make an election live: add what it needs, switch it on, then start it by moving its dates. */
export function startElection(electionId: number) {
  civiApi4('Election.update', { values: { is_visible: true }, where: [['id', '=', electionId]] });
  civiApi4('Election.update', { values: { nomination_start_date: day(-1), visibility_start_date: day(-2) }, where: [['id', '=', electionId]] });
}

export function isActive(electionId: number): boolean {
  return civiApi4<Array<{ is_visible: boolean }>>('Election.get', { select: ['is_visible'], where: [['id', '=', electionId]] })[0].is_visible;
}

export function positionNames(electionId: number): string[] {
  return civiApi4<Array<{ name: string }>>('ElectionPosition.get', {
    select: ['name'],
    where: [['election_id', '=', electionId]],
    orderBy: { sortorder: 'ASC' },
  }).map((p) => p.name);
}

export function removeRunData() {
  const like = `%PW % ${RUN}`;
  civiSql('DELETE p FROM civicrm_election_position p INNER JOIN civicrm_election e ON e.id = p.election_id WHERE e.name LIKE %1', { 1: like });
  civiSql('DELETE FROM civicrm_election WHERE name LIKE %1', { 1: like });
}
