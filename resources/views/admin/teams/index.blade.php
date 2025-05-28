@extends('admin.layouts.app')

@section('title', 'Team Management')
@section('page-title', 'Team Management')

@section('content')
<div class="row mb-3">
    <div class="col-md-6">
        <form method="GET" action="{{ route('admin.teams.index') }}" class="d-flex">
            <input type="text" 
                   name="search" 
                   class="form-control me-2" 
                   placeholder="Search teams..." 
                   value="{{ request('search') }}">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i>
            </button>
        </form>
    </div>
    <div class="col-md-6 text-end">
        <span class="text-muted">Total: {{ $teams->total() }} teams</span>
    </div>
</div>

<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="bi bi-diagram-3 me-2"></i>Teams List
        </h6>
    </div>
    <div class="card-body">
        @if($teams->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Team Name</th>
                            <th>Creator</th>
                            <th>Members</th>
                            <th>Tasks</th>
                            <th>Messages</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($teams as $team)
                        <tr>
                            <td>{{ $team->id }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2">
                                        <div class="bg-info rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 32px; height: 32px;">
                                            <span class="text-white small">
                                                {{ strtoupper(substr($team->name, 0, 1)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <strong>{{ $team->name }}</strong>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="text-muted">{{ $team->creator->name }}</span>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $team->members_count }}</span>
                            </td>
                            <td>
                                <span class="badge bg-success">{{ $team->tasks_count }}</span>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $team->chat_messages_count }}</span>
                            </td>
                            <td>
                                <small class="text-muted">{{ $team->created_at->format('M d, Y') }}</small>
                            </td>
                            <td>
                                <a href="{{ route('admin.teams.show', $team) }}" 
                                   class="btn btn-sm btn-outline-primary" 
                                   title="View Details">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $teams->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-4">
                <i class="bi bi-diagram-3 text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">No teams found.</p>
                @if(request('search'))
                    <a href="{{ route('admin.teams.index') }}" class="btn btn-sm btn-outline-primary">
                        Clear Search
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Teams
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $teams->total() }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-diagram-3 text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Members
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $teams->sum('members_count') }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-people text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Total Tasks
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $teams->sum('tasks_count') }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-list-task text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Privacy Notice:</strong> This panel shows team metadata and statistics only. 
        Chat messages, task content, and shared documents remain private and are not accessible to maintain team privacy.
    </div>
</div>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
</style>
@endsection
