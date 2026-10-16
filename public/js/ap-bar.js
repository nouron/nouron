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
    // True once today's allowance of this row is used up. Always false while the cap is off.
    apCapReached(row) {
        return !!row?.ap_cap && row.ap_cap.remaining <= 0;
    },

    // Left offset (percent of bar width) of the "day limit" marker: the boundary after
    // the segments still investable today. null = no marker (cap off, or boundary at bar end).
    apCapLimitPos(row) {
        const cap = row?.ap_cap;
        if (!cap || !row.ap_for_levelup) return null;
        const boundary = Math.min(row.ap_spend + Math.max(cap.remaining, 0), row.ap_for_levelup);
        if (boundary >= row.ap_for_levelup) return null;
        return (boundary / row.ap_for_levelup) * 100;
    },

    // CSS classes for segment i (1-based). With the cap off only the plain filled state
    // applies. Capped: filled-earlier / filled-today / free-today / locked-today.
    apSegClasses(row, i) {
        const cap = row.ap_cap;
        const spend = row.ap_spend;
        if (!cap) return { 'btn-seg--filled': i <= spend };
        const earlier = spend - cap.spent;
        return {
            'btn-seg--filled': i <= spend,
            'btn-seg--today': i > earlier && i <= spend,
            'btn-seg--free': i > spend && i <= spend + Math.max(cap.remaining, 0),
            'btn-seg--locked': i > spend + Math.max(cap.remaining, 0),
        };
    },
};
