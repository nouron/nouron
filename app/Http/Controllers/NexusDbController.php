<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class NexusDbController extends Controller
{
    public function index(): View
    {
        $buildings = config('buildings');
        $ships     = config('ships');
        $knowledge = config('game.knowledge_cc_level_cap');

        return view('nexusdb.index', compact('buildings', 'ships', 'knowledge'));
    }
}
