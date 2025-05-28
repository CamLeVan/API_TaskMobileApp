@extends('admin.layouts.app')

@section('title', 'User Management')
@section('page-title', 'User Management')

@section('content')
<div class="row mb-3">
    <div class="col-md-6">
        <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex">
            <input type="text" 
                   name="search" 
                   class="form-control me-2" 
                   placeholder="Search users..." 
                   value="{{ request('search') }}">
            <select name="status" class="form-select me-2" style="width: auto;">
                <option value="">All Users</option>
                <option value="admin" {{ request('status') === 'admin' ? 'selected' : '' }}>Admins</option>
                <option value="user" {{ request('status') === 'user' ? 'selected' : '' }}>Regular Users</option>
            </select>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i>
            </button>
        </form>
    </div>
    <div class="col-md-6 text-end">
        <span class="text-muted">Total: {{ $users->total() }} users</span>
    </div>
</div>

<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="bi bi-people me-2"></i>Users List
        </h6>
    </div>
    <div class="card-body">
        @if($users->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Tasks</th>
                            <th>Teams</th>
                            <th>Last Login</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2">
                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 32px; height: 32px;">
                                            <span class="text-white small">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <strong>{{ $user->name }}</strong>
                                </div>
                            </td>
                            <td>
                                <span class="text-muted">{{ $user->email }}</span>
                            </td>
                            <td>
                                @if($user->is_admin)
                                    <span class="badge bg-danger">Admin</span>
                                @else
                                    <span class="badge bg-secondary">User</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $user->personal_tasks_count }}</span>
                            </td>
                            <td>
                                <span class="badge bg-success">{{ $user->teams_count }}</span>
                            </td>
                            <td>
                                @if($user->last_login_at)
                                    <small class="text-muted">{{ $user->last_login_at->diffForHumans() }}</small>
                                @else
                                    <small class="text-muted">Never</small>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $user->created_at->format('M d, Y') }}</small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.users.show', $user) }}" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    @if($user->id !== auth()->id())
                                        <form method="POST" 
                                              action="{{ route('admin.users.toggle-status', $user) }}" 
                                              class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to {{ $user->is_admin ? 'revoke' : 'grant' }} admin privileges for {{ $user->name }}?')">
                                            @csrf
                                            <button type="submit" 
                                                    class="btn btn-sm {{ $user->is_admin ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                    title="{{ $user->is_admin ? 'Revoke Admin' : 'Grant Admin' }}">
                                                <i class="bi bi-{{ $user->is_admin ? 'shield-slash' : 'shield-check' }}"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $users->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-4">
                <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">No users found.</p>
                @if(request('search'))
                    <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-primary">
                        Clear Search
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>

<div class="mt-4">
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Privacy Notice:</strong> This panel only shows basic user statistics and metadata. 
        Personal task content, chat messages, and private documents are not accessible to maintain user privacy.
    </div>
</div>
@endsection
