@extends('admin.layouts.app')

@section('title', 'User Details')
@section('page-title', 'User Details: ' . $user->name)

@section('content')
<div class="row">
    <div class="col-md-4">
        <!-- User Info Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-person me-2"></i>User Information
                </h6>
            </div>
            <div class="card-body text-center">
                <div class="avatar-lg mx-auto mb-3">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 80px; height: 80px; margin: 0 auto;">
                        <span class="text-white h3 mb-0">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </span>
                    </div>
                </div>
                
                <h5 class="mb-1">{{ $userData['basic_info']['name'] }}</h5>
                <p class="text-muted mb-3">{{ $userData['basic_info']['email'] }}</p>
                
                @if($user->is_admin)
                    <span class="badge bg-danger mb-3">Administrator</span>
                @else
                    <span class="badge bg-secondary mb-3">Regular User</span>
                @endif
                
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h6 class="text-muted">Member Since</h6>
                            <p class="mb-0">{{ $userData['basic_info']['created_at']->format('M d, Y') }}</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <h6 class="text-muted">Last Login</h6>
                        <p class="mb-0">
                            @if($userData['basic_info']['last_login_at'])
                                {{ $userData['basic_info']['last_login_at']->diffForHumans() }}
                            @else
                                Never
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-gear me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                @if($user->id !== auth()->id())
                    <form method="POST" 
                          action="{{ route('admin.users.toggle-status', $user) }}" 
                          onsubmit="return confirm('Are you sure you want to {{ $user->is_admin ? 'revoke' : 'grant' }} admin privileges for {{ $user->name }}?')">
                        @csrf
                        <button type="submit" class="btn {{ $user->is_admin ? 'btn-warning' : 'btn-success' }} w-100 mb-2">
                            <i class="bi bi-{{ $user->is_admin ? 'shield-slash' : 'shield-check' }} me-2"></i>
                            {{ $user->is_admin ? 'Revoke Admin' : 'Grant Admin' }}
                        </button>
                    </form>
                @else
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        You cannot modify your own admin status.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Personal Tasks
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $userData['statistics']['total_personal_tasks'] }}
                                </div>
                                <div class="text-xs text-success">
                                    {{ $userData['statistics']['completed_personal_tasks'] }} completed
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-list-task text-primary" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Teams
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $userData['statistics']['teams_count'] }}
                                </div>
                                <div class="text-xs text-info">
                                    {{ $userData['statistics']['team_tasks_assigned'] }} tasks assigned
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-diagram-3 text-success" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teams List -->
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-diagram-3 me-2"></i>Team Memberships
                </h6>
            </div>
            <div class="card-body">
                @if($userData['teams']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Team Name</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($userData['teams'] as $team)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.teams.show', $team->id) }}" class="text-decoration-none">
                                            {{ $team->name }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($team->pivot->role === 'admin')
                                            <span class="badge bg-danger">Admin</span>
                                        @else
                                            <span class="badge bg-secondary">Member</span>
                                        @endif
                                    </td>
                                    <td>{{ $team->pivot->joined_at ? \Carbon\Carbon::parse($team->pivot->joined_at)->format('M d, Y') : 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('admin.teams.show', $team->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-3">
                        <i class="bi bi-diagram-3 text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted mt-2">User is not a member of any teams.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <div class="alert alert-warning">
        <i class="bi bi-shield-exclamation me-2"></i>
        <strong>Privacy Protection:</strong> This view only shows aggregated statistics and team memberships. 
        Task content, chat messages, and personal documents remain private and are not accessible through this interface.
    </div>
</div>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
</style>
@endsection
