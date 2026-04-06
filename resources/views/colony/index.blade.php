@extends('layouts.app')
@section('title', 'Kolonie — Nouron')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- Colony overview --}}
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-globe2"></i> {{ $colony->name }}</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <th scope="row" class="text-muted fw-normal" style="width:40%">Position</th>
                            <td>{{ $colony->x }}, {{ $colony->y }}, Spot {{ $colony->spot }}</td>
                        </tr>
                        <tr>
                            <th scope="row" class="text-muted fw-normal">Gegründet (Tick)</th>
                            <td>{{ $colony->since_tick }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Rename --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-pencil"></i> Kolonie umbenennen</div>
            <div class="card-body">
                <form method="POST" action="{{ route('colony.rename') }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label for="colony_name" class="form-label">Name</label>
                        <input type="text" id="colony_name" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $colony->name) }}"
                               minlength="2" maxlength="50" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg"></i> Speichern
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
