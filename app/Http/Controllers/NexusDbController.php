<?php

namespace App\Http\Controllers;

use App\Services\OnboardingHintService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NexusDbController extends Controller
{
    public function __construct(
        private readonly OnboardingHintService $onboardingHintService,
    ) {}

    public function index(): View
    {
        $userId = Auth::id();
        $firstVisit = $userId ? $this->onboardingHintService->checkFirstVisit('nexusdb', $userId) : false;

        return view('nexusdb.index', compact('firstVisit'));
    }
}
