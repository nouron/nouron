<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class NexusDbController extends Controller
{
    public function index(): View
    {
        return view('nexusdb.index');
    }
}
