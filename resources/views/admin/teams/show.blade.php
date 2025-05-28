@extends('admin.layouts.app')

@section('title', 'Team Details')
@section('page-title', 'Team Details: ' . $team->name)

@section('content')
<div class="row">
    <div class="col-md-4">
        <!-- Team Info Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-diagram-3 me-2"></i>Team Information
                </h6>
            </div>
            <div class="card-body text-center">
                <div class="avatar-lg mx-auto mb-3">
                    <div class="bg-info rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 80px; height: 80px; margin: 0 auto;">
                        <span class="text-white h3 mb-0">
                            {{ strtoupper(substr($team->name, 0, 1)) }}
                        </span>
                    </div>
                </div>
                
                <h5 class="mb-1">{{ $teamData['basic_info']['name'] }}</h5>
                <p class="text-muted mb-3">Created by {{ $teamData['creator']['name'] }}</p>
                
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h6 class="text-muted">Created</h6>
                            <p class="mb-0">{{ $teamData['basic_info']['created_at']->format('M d, Y') }}</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <h6 class="text-muted">Team ID</h6>
                        <p class="mb-0">#{{ $teamData['basic_info']['id'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Members
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $teamData['statistics']['members_count'] }}
                                </div>
                                <div class="text-xs text-success">
                                    {{ $teamData['statistics']['active_members'] }} active
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-people text-primary" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Tasks
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $teamData['statistics']['tasks_count'] }}
                                </div>
                                <div class="text-xs text-info">
                                    {{ $teamData['statistics']['completed_tasks'] }} done
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-list-task text-success" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Messages
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $teamData['statistics']['messages_count'] }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-chat text-info" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Completion
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    @if($teamData['statistics']['tasks_count'] > 0)
                                        {{ round(($teamData['statistics']['completed_tasks'] / $teamData['statistics']['tasks_count']) * 100) }}%
                                    @else
                                        0%
                                    @endif
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-graph-up text-warning" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Members List -->
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-people me-2"></i>Team Members
                </h6>
            </div>
            <div class="card-body">
                @if(count($teamData['members_list']) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th>Last Login</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teamData['members_list'] as $member)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2">
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 24px; height: 24px;">
                                                    <span class="text-white small">
                                                        {{ strtoupper(substr($member['name'], 0, 1)) }}
                                                    </span>
                                                </div>
                                            </div>
                                            {{ $member['name'] }}
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $member['email'] }}</small>
                                    </td>
                                    <td>
                                        @if($member['role'] === 'admin')
                                            <span class="badge bg-danger">Admin</span>
                                        @else
                                            <span class="badge bg-secondary">Member</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($member['joined_at'])->format('M d, Y') }}
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            @if($member['last_login'])
                                                {{ \Carbon\Carbon::parse($member['last_login'])->diffForHumans() }}
                                            @else
                                                Never
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.users.show', $member['id']) }}" 
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
                        <i class="bi bi-people text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted mt-2">No members found in this team.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <div class="alert alert-warning">
        <i class="bi bi-shield-exclamation me-2"></i>
        <strong>Privacy Protection:</strong> This view only shows team statistics and member information. 
        Chat messages, task content, and shared documents remain private and are not accessible through this interface.
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
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
</style>
@endsection
