<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Run;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * R15 (closed beta): stores tester feedback. The client only sends category,
 * message and the current page; who sent it, in which run and on which Sol is
 * determined here, never taken from the request.
 */
class FeedbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', Rule::in(Feedback::CATEGORIES)],
            'message' => ['required', 'string', 'max:2000'],
            'page' => ['nullable', 'string', 'max:500'],
        ]);

        $run = Run::where('user_id', $request->user()->user_id)
            ->where('status', 'active')
            ->first(['id', 'current_tick']);

        Feedback::create([
            'user_id' => $request->user()->user_id,
            'run_id' => $run?->id,
            // Same clock as the Sol chip: run start (current_tick 0) is Sol 1.
            'sol' => $run !== null ? (int) $run->current_tick + 1 : null,
            'category' => $validated['category'],
            'message' => trim($validated['message']),
            'page' => self::pathOnly($validated['page'] ?? null),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);

        return response()->json(['ok' => true], 201);
    }

    /** Keeps only the path of a submitted page URL (no host, query or fragment). */
    private static function pathOnly(?string $page): ?string
    {
        if ($page === null || $page === '') {
            return null;
        }

        $path = parse_url($page, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? Str::limit($path, 250, '') : null;
    }
}
