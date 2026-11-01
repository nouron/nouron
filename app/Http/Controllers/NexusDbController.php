<?php

namespace App\Http\Controllers;

use App\Services\OnboardingHintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NexusDbController extends Controller
{
    public function __construct(
        private readonly OnboardingHintService $onboardingHintService,
    ) {}

    /** First-visit flag for the SvelteKit page; the concept texts come from the lang files. */
    public function data(): JsonResponse
    {
        $userId = Auth::id();

        return response()->json(['first_visit' => $userId ? $this->onboardingHintService->checkFirstVisit('nexusdb', $userId) : false]);
    }

    public function index(): View
    {
        $userId = Auth::id();
        $firstVisit = $userId ? $this->onboardingHintService->checkFirstVisit('nexusdb', $userId) : false;

        return view('nexusdb.index', compact('firstVisit'));
    }
}
