/**
 * Shared logic of the segmented AP bar inside the "upgrade button"
 * (resources/views/partials/ap-upgrade-button.blade.php), used by the colony hex
 * view ("Ausbauen") and the techtree (Kenntnis level-up). Spread it into an Alpine
 * component: `{ ...window.NouronApBar, ... }`. Pure helpers, no component state.
 *
 * A row needs ap_spend and ap_for_levelup; the optional per-Sol cap contract is
 * `ap_cap: null | { cap, spent, remaining }` (null/absent = cap off, plain bar).
 */
window.NouronApBar = {
    AP_BAR_MAX_COLS: 10,

    // True once today's allowance of this row is used up. Always false while the cap is off.
    apCapReached(row) {
        return !!row?.ap_cap && row.ap_cap.remaining <= 0;
    },

    // The cap tooltip only applies when a cap is actually in play: capped, or the day-limit
    // marker is visible. Never for a plain level start.
    apCapTipApplies(row) {
        return this.apCapReached(row) || this.apCapLimitPos(row) !== null;
    },

    // Segments wrap into rows of at most AP_BAR_MAX_COLS so 1 segment = 1 AP stays legible
    // for big costs. Columns are balanced across the rows (11 -> 2 rows of 6, not 10 + 1).
    apBarLayout(row) {
        const n = Math.max(row?.ap_for_levelup ?? 0, 1);
        const rows = Math.ceil(n / window.NouronApBar.AP_BAR_MAX_COLS);
        return { rows, cols: Math.ceil(n / rows) };
    },

    // Position of the "day limit" marker (only once AP was invested): the boundary after the
    // segments still investable today, as { left: percent of row width, row: 0-based row index }.
    // null = no marker (cap off, or boundary at bar end).
    apCapLimitPlace(row) {
        const cap = row?.ap_cap;
        if (!cap || !row.ap_for_levelup || row.ap_spend === 0) return null;
        const boundary = Math.min(row.ap_spend + Math.max(cap.remaining, 0), row.ap_for_levelup);
        if (boundary >= row.ap_for_levelup) return null;
        const { cols } = this.apBarLayout(row);
        // A boundary at the start of a row is drawn at the right end of the row above.
        const rowIdx = Math.ceil(boundary / cols) - 1;
        return { left: ((boundary - rowIdx * cols) / cols) * 100, row: rowIdx };
    },

    // Left offset (percent of row width) of the marker, or null (kept for callers that only
    // need "is there a marker").
    apCapLimitPos(row) {
        return this.apCapLimitPlace(row)?.left ?? null;
    },

    // CSS classes for segment i (1-based). With the cap off, or at a level start (nothing
    // invested yet), only the plain filled state applies. Capped: filled-earlier / filled-today / free-today / locked-today.
    apSegClasses(row, i) {
        const cap = row.ap_cap;
        const spend = row.ap_spend;
        if (!cap || spend === 0) return { 'btn-seg--filled': i <= spend };
        const earlier = spend - cap.spent;
        return {
            'btn-seg--filled': i <= spend,
            'btn-seg--today': i > earlier && i <= spend,
            'btn-seg--free': i > spend && i <= spend + Math.max(cap.remaining, 0),
            'btn-seg--locked': i > spend + Math.max(cap.remaining, 0),
        };
    },
};
