<x-layouts.admin title="Notifications">
    <x-page-header title="Notifications" :breadcrumbs="['Notifications']">
        <form method="POST" action="{{ route('admin.notifications.read-all') }}">@csrf<button class="btn btn-light">Mark all read</button></form>
    </x-page-header>
    <x-card flush>
        <div class="list-group list-group-flush">
            @forelse ($notifications as $n)
                <a href="{{ route('admin.notifications.open', $n->id) }}" @class(['list-group-item list-group-item-action d-flex gap-3 py-3', 'notif-item unread' => ! $n->read_at])>
                    <i class="bi bi-{{ $n->data['icon'] ?? 'bell' }} text-coffee fs-5" aria-hidden="true"></i>
                    <div class="flex-grow-1">
                        <div class="fw-medium">{{ $n->data['title'] ?? 'Notification' }} @unless ($n->read_at)<span class="visually-hidden">(unread)</span>@endunless</div>
                        <div class="small text-secondary">{{ $n->data['body'] ?? '' }}</div>
                    </div>
                    <span class="small text-secondary text-nowrap">{{ $n->created_at->diffForHumans() }}</span>
                </a>
            @empty
                <x-empty-state icon="bell" title="No notifications yet" />
            @endforelse
        </div>
    </x-card>
    <x-pagination :paginator="$notifications" />
</x-layouts.admin>
