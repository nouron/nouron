{{--
    Shared "Voraussetzungen" (prerequisites) chip row.

    Used in both the techtree detail panel and the colony hex-view building
    sidebar (Owner-Playtest-Fund 2026-09-04: both screens show the same
    required_list format — a plain string array of "<Name> Lv<n>" entries —
    but previously only the techtree panel rendered it).

    Parameters (PHP, Blade-side):
      $expr — Alpine JS expression that evaluates to the object carrying a
              `required_list` field, e.g. "selectedTech" or "selectedBuilding".
              The partial appends ".required_list" itself, mirroring
              partials/building-detail.blade.php's $expr convention.
--}}
<template x-if="{{ $expr }}.required_list && {{ $expr }}.required_list.length > 0">
    <div class="detail-row">
        <span class="detail-row-label">{{ __("techtree.detail_required") }}</span>
        <ul class="detail-list detail-list--chips">
            <template x-for="(part, idx) in {{ $expr }}.required_list" :key="idx">
                <li>
                    <span class="res-chip res-chip--neutral" x-text="part"></span>
                </li>
            </template>
        </ul>
    </div>
</template>
