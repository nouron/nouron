<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Recalibrate decay_rate values for 1 tick = 24 hours.
 *
 * Previous values were designed for 1 tick/hour and resulted in ~100–400 day decay cycles.
 * New values target:
 *   7 d → 2.86 | 10 d → 2.0 | 14 d → 1.43 | 21 d → 0.95 | 30 d → 0.67 | 45 d → 0.44 | 60 d → 0.33
 *
 * Source of truth: config/buildings.php, config/ships.php, config/techs.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Buildings ────────────────────────────────────────────────────────
        $buildings = [
            25 => 0.33,   // commandCenter     — 60 days
            27 => 0.95,   // oremine           — 21 days
            28 => 0.44,   // housingComplex    — 45 days
            30 => 0.67,   // depot             — 30 days
            31 => 0.95,   // sciencelab        — 21 days
            32 => 2.00,   // temple            — 10 days
            41 => 0.95,   // silicatemine      — 21 days
            42 => 0.95,   // waterextractor    — 21 days
            43 => 0.67,   // tradecenter       — 30 days
            44 => 0.67,   // civilianSpaceyard — 30 days
            45 => 2.00,   // parc              — 10 days
            46 => 2.00,   // hospital          — 10 days
            48 => 2.00,   // public_security   — 10 days
            50 => 0.33,   // denkmal           — 60 days
            51 => 0.95,   // university        — 21 days
            52 => 2.86,   // bar               —  7 days
            53 => 0.95,   // stadium           — 21 days
            54 => 2.86,   // casino            —  7 days
            55 => 0.95,   // prison            — 21 days
            56 => 0.95,   // museum            — 21 days
            64 => 0.95,   // wastedisposal     — 21 days
            65 => 0.95,   // recyclingStation  — 21 days
            66 => 0.67,   // secretOps         — 30 days
            68 => 0.44,   // militarySpaceyard — 45 days
            70 => 0.67,   // bank              — 30 days
        ];

        foreach ($buildings as $id => $rate) {
            DB::table('buildings')->where('id', $id)->update(['decay_rate' => $rate]);
        }

        // ── Ships ────────────────────────────────────────────────────────────
        $ships = [
            37 => 2.00,   // fighter1          — 10 days
            29 => 1.43,   // frigate1          — 14 days
            49 => 1.43,   // battlecruiser1    — 14 days
            47 => 0.95,   // smallTransporter  — 21 days
            83 => 0.95,   // mediumTransporter — 21 days
            84 => 0.67,   // largeTransporter  — 30 days
        ];

        foreach ($ships as $id => $rate) {
            DB::table('ships')->where('id', $id)->update(['decay_rate' => $rate]);
        }

        // ── Researches ───────────────────────────────────────────────────────
        $researches = [
            33 => 0.95,   // biology           — 21 days
            34 => 0.95,   // languages         — 21 days
            39 => 0.95,   // mathematics       — 21 days
            72 => 0.95,   // medicalScience    — 21 days
            73 => 0.95,   // physics           — 21 days
            74 => 0.95,   // chemistry         — 21 days
            76 => 0.95,   // economicScience   — 21 days
            79 => 0.95,   // diplomacy         — 21 days
            80 => 0.95,   // politicalScience  — 21 days
            81 => 1.43,   // military          — 14 days
        ];

        foreach ($researches as $id => $rate) {
            DB::table('researches')->where('id', $id)->update(['decay_rate' => $rate]);
        }
    }

    public function down(): void
    {
        // Restore previous values (designed for 1 tick/hour)
        $buildings = [
            25 => 0.07, 27 => 0.17, 28 => 0.13, 30 => 0.13, 31 => 0.17,
            32 => 0.13, 41 => 0.17, 42 => 0.17, 43 => 0.13, 44 => 0.12,
            45 => 0.13, 46 => 0.20, 48 => 0.17, 50 => 0.05, 51 => 0.17,
            52 => 0.20, 53 => 0.15, 54 => 0.20, 55 => 0.10, 56 => 0.10,
            64 => 0.13, 65 => 0.13, 66 => 0.13, 68 => 0.08, 70 => 0.10,
        ];
        foreach ($buildings as $id => $rate) {
            DB::table('buildings')->where('id', $id)->update(['decay_rate' => $rate]);
        }

        $ships = [37 => 0.15, 29 => 0.16, 49 => 0.10, 47 => 0.05, 83 => 0.07, 84 => 0.06];
        foreach ($ships as $id => $rate) {
            DB::table('ships')->where('id', $id)->update(['decay_rate' => $rate]);
        }

        $researches = [33=>0.13,34=>0.13,39=>0.13,72=>0.13,73=>0.13,74=>0.13,76=>0.13,79=>0.13,80=>0.13,81=>0.13];
        foreach ($researches as $id => $rate) {
            DB::table('researches')->where('id', $id)->update(['decay_rate' => $rate]);
        }
    }
};
