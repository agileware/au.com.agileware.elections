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
  /** Group whose members may take part. */
  groupId?: number;
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
      ...(options.groupId ? { allowed_groups: String(options.groupId) } : {}),
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
  civiSql('DELETE v FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id INNER JOIN civicrm_election e ON e.id = p.election_id WHERE e.name LIKE %1', { 1: like });
  civiSql('DELETE s FROM civicrm_election_nomination_seconder s INNER JOIN civicrm_election_nomination n ON n.id = s.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id INNER JOIN civicrm_election e ON e.id = p.election_id WHERE e.name LIKE %1', { 1: like });
  civiSql('DELETE n FROM civicrm_election_nomination n INNER JOIN civicrm_election_position p ON p.id = n.election_position_id INNER JOIN civicrm_election e ON e.id = p.election_id WHERE e.name LIKE %1', { 1: like });
  civiSql('DELETE p FROM civicrm_election_position p INNER JOIN civicrm_election e ON e.id = p.election_id WHERE e.name LIKE %1', { 1: like });
  civiSql('DELETE FROM civicrm_election WHERE name LIKE %1', { 1: like });
}

let groupCounter = 0;

/** A group of throwaway members: contacts that can take part in an election using the group. */
export function createMembers(names: string[]): { groupId: number; ids: Record<string, number> } {
  const title = `PW members ${RUN} ${++groupCounter}`;
  const groupId = civiApi4<Array<{ id: number }>>('Group.create', { values: { title, name: title.replace(/\W+/g, '_').toLowerCase() } })[0].id;
  const ids: Record<string, number> = {};
  for (const name of names) {
    const [contact] = civiApi4<Array<{ id: number }>>('Contact.create', {
      values: { contact_type: 'Individual', first_name: name, last_name: `Member ${RUN}` },
    });
    civiApi4('GroupContact.create', { values: { group_id: groupId, contact_id: contact.id, status: 'Added' } });
    ids[name] = contact.id;
  }
  return { groupId, ids };
}

export function addNomination(positionId: number, nomineeId: number, flags: { eligible?: boolean; accepted?: boolean; statement?: string } = {}): number {
  return civiApi4<Array<{ id: number }>>('ElectionNomination.create', {
    values: {
      election_position_id: positionId,
      member_nominee: nomineeId,
      is_eligible_candidate: flags.eligible ?? true,
      has_accepted_nomination: flags.accepted ?? false,
      comments: flags.statement ?? null,
    },
  })[0].id;
}

export function nominationFlags(nominationId: number) {
  return civiApi4<Array<{ is_eligible_candidate: boolean; has_accepted_nomination: boolean; comments: string | null; statement_url: string | null }>>('ElectionNomination.get', {
    select: ['is_eligible_candidate', 'has_accepted_nomination', 'comments', 'statement_url'],
    where: [['id', '=', nominationId]],
  })[0];
}

export function nomineeNames(electionId: number): string[] {
  return civiApi4<Array<{ 'member_nominee.display_name': string }>>('ElectionNomination.get', {
    select: ['member_nominee.display_name'],
    where: [['election_position_id.election_id', '=', electionId]],
  }).map((r) => r['member_nominee.display_name']);
}

export function removeRunMembers() {
  civiApi4('Contact.delete', { where: [['last_name', '=', `Member ${RUN}`]], useTrash: false });
  civiApi4('Group.delete', { where: [['title', 'LIKE', `PW members ${RUN}%`]] });
}
