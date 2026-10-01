@extends("layouts.infra")

@section("title", __("feedback.admin_title") . " – Nouron")

@section("content")
    <h1 class="feedback-admin-title">{{ __("feedback.admin_title") }}</h1>

    @if ($feedback->isEmpty())
        <p>{{ __("feedback.admin_empty") }}</p>
    @else
        <div class="overflow-auto">
            <table class="striped">
                <thead>
                    <tr>
                        <th>{{ __("feedback.admin_col_date") }}</th>
                        <th>{{ __("feedback.admin_col_user") }}</th>
                        <th>{{ __("feedback.admin_col_category") }}</th>
                        <th>{{ __("feedback.admin_col_context") }}</th>
                        <th>{{ __("feedback.admin_col_message") }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($feedback as $entry)
                        <tr>
                            <td>{{ $entry->created_at->format("d.m.Y H:i") }}</td>
                            <td>{{ $entry->user?->username ?? "#" . $entry->user_id }}</td>
                            <td>{{ __("feedback.category_" . $entry->category) }}</td>
                            <td>
                                {{ $entry->run_id ? "#" . $entry->run_id : "–" }}
                                / {{ $entry->sol ?? "–" }}
                                / <code>{{ $entry->page ?? "–" }}</code>
                            </td>
                            <td style="white-space: pre-wrap">{{ $entry->message }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $feedback->links() }}
    @endif
@endsection
