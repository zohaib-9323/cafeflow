<x-layouts.admin title="Team">
    <x-page-header title="Team" :breadcrumbs="['Team']" subtitle="People who can sign in to the back office.">
        <a href="{{ route('admin.team.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1" aria-hidden="true"></i>Add team member</a>
    </x-page-header>
    @error('role')<x-alert type="danger">{{ $message }}</x-alert>@enderror
    <div class="card">
        <x-table>
            <x-slot:head><th>Name</th><th>Role</th><th class="text-end">Orders handled</th><th>Last sign-in</th><th>Status</th><th></th></x-slot:head>
            @foreach ($members as $m)
                <tr>
                    <td data-label="Name"><span class="fw-semibold">{{ $m->name }}</span>@if ($m->is(auth()->user())) <span class="small text-secondary">(you)</span>@endif<span class="d-block small text-secondary">{{ $m->email }}</span></td>
                    <td data-label="Role">{{ $m->roleEnum()->label() }}</td>
                    <td data-label="Orders" class="text-end">{{ $m->assigned_orders_count }}</td>
                    <td data-label="Last sign-in" class="small">{{ $m->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                    <td data-label="Status"><x-badge :tone="$m->is_active ? 'success' : 'secondary'">{{ $m->is_active ? 'Active' : 'Deactivated' }}</x-badge></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.team.edit', $m) }}" class="btn btn-sm btn-light">Edit</a>
                        @can('delete', $m)
                            <x-confirm :action="route('admin.team.destroy', $m)" method="DELETE" class="d-inline" :message="'Remove '.$m->name.'? They will be signed out and lose access. Their order history stays.'" confirm-label="Remove">
                                <button class="btn btn-sm btn-outline-danger" aria-label="Remove {{ $m->name }}"><i class="bi bi-person-dash" aria-hidden="true"></i></button>
                            </x-confirm>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </x-table>
    </div>
    <p class="small text-secondary mt-3">
        <strong>Super admin</strong>: everything, including settings and the team. ·
        <strong>Manager</strong>: menu, stock, orders, customers, promotions, content and reports. ·
        <strong>Staff</strong>: orders they take or are given, and recording waste.
    </p>
</x-layouts.admin>
