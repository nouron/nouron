<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * R15: admin overview of tester feedback, newest first.
 */
class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        $feedback = Feedback::with('user')->latest()->paginate(50);

        return view('admin.feedback', ['feedback' => $feedback]);
    }
}
