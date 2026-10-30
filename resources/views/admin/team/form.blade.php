@php $editing = $member->exists; $self = $editing && $member->is(auth()->user()); @endphp
<x-layouts.admin :title="$editing ? 'Edit '.$member->name : 'Add team member'">
    <x-page-header :title="$editing ? $member->name : 'Add team member'" :breadcrumbs="['Team' => route('admin.team.index'), $editing ? 'Edit' : 'New']" />
    <div class="row"><div class="col-lg-7"><x-card>
        <form method="POST" action="{{ $editing ? route('admin.team.update', $member) : route('admin.team.store') }}" novalidate>
            @csrf @if ($editing) @method('PUT') @endif
            @error('role')<x-alert type="danger" :dismissible="false">{{ $message }}</x-alert>@enderror
            <x-input name="name" label="Full name" :value="$member->name" required maxlength="150" autocomplete="off" />
            <x-input name="email" label="Work email" type="email" :value="$member->email" required autocomplete="off" />
            <x-select name="role" label="Role" :options="collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])" :value="$member->role ? $member->roleEnum()->value : 'staff'" required :disabled="$self" />
            @if ($self)<input type="hidden" name="role" value="{{ $member->roleEnum()->value }}">@endif
            <div class="row">
                <div class="col-md-6"><x-input name="password" :label="$editing ? 'New password' : 'Password'" type="password" :required="! $editing" autocomplete="new-password" :help="$editing ? 'Leave blank to keep the current one.' : 'Share it with them privately.'" /></div>
                <div class="col-md-6"><x-input name="password_confirmation" label="Repeat password" type="password" autocomplete="new-password" /></div>
            </div>
            @if ($self)
                <input type="hidden" name="is_active" value="1">
            @else
                <x-checkbox name="is_active" label="Can sign in" :checked="$member->is_active" />
            @endif
            <div class="d-flex gap-2"><button class="btn btn-primary">{{ $editing ? 'Save' : 'Add member' }}</button><a href="{{ route('admin.team.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </x-card></div></div>
</x-layouts.admin>
