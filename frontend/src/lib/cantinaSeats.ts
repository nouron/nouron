import SEATS from './cantina-seats.json';

// Where each figure stands in the cantina picture (owner 2026-11-20). Seats are in % of the
// image (16:9) and the same on every device; tests/Unit/CantinaSeatsTest.php checks that
// no two cards overlap and that on phones each card sits inside one of the 4 swipe pages.
export type Seat = { id: string; kind: 'bar' | 'counter' | 'table' | 'entrance' | 'corner'; x: number; y: number };
export const seats = SEATS as Seat[];
export const PAGES = 4;

export type Figure = { key: string; slug: string; met: boolean };

const GUEST: Seat['kind'][] = ['counter', 'table'];

// Seat pools, best first: the bartender behind the bar; the mysterious figure in the corner;
// someone not met yet just came in at the entrance; everyone else at the counter or a table.
function poolsFor(f: Figure): Seat['kind'][][] {
	if (f.slug === 'bartender') return [['bar']];
	if (f.slug === 'stranger') return [['corner'], ['entrance'], GUEST];
	if (!f.met) return [['entrance'], GUEST, ['corner']];
	return [GUEST, ['entrance'], ['corner']];
}
const rank = (f: Figure) => (f.slug === 'bartender' ? 0 : f.slug === 'stranger' ? 1 : f.met ? 3 : 2);

// Seats above each other: the larger phone cards would touch, so these pairs are filled last.
const stacked = (a: Seat, b: Seat) => Math.abs(a.x - b.x) <= 4 && Math.abs(a.y - b.y) <= 30;

const hash = (s: string) => [...s].reduce((h, c) => (Math.imul(h, 31) + c.charCodeAt(0)) | 0, 7) >>> 0;

/** figure key → seat. Stable while the cast stays the same; a new cast (next Sol) reshuffles. */
export function assignSeats(figures: Figure[]): Record<string, Seat> {
	const cast = figures.map((f) => f.key).sort().join('|');
	const free = [...seats];
	const out: Record<string, Seat> = {};
	// Most constrained first, so the bartender and the stranger get their places.
	for (const f of [...figures].sort((a, b) => rank(a) - rank(b))) {
		const pick = (clearOnly: boolean) => {
			for (const kinds of poolsFor(f)) {
				const pool = free.filter(
					(s) => kinds.includes(s.kind) && (!clearOnly || !Object.values(out).some((t) => stacked(s, t)))
				);
				if (pool.length) return pool[hash(cast + f.key) % pool.length];
			}
			return null;
		};
		const seat = pick(true) ?? pick(false);
		if (!seat) continue;
		free.splice(free.indexOf(seat), 1);
		out[f.key] = seat;
	}
	return out;
}
