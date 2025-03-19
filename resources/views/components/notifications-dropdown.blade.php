<div class="nav-item dropdown d-none d-md-flex me-3">
    <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1" aria-label="Show notifications">
        <i class="la la-bell fs-4"></i>
        @if($unreadCount > 0)
            <span class="badge bg-red badge-notification badge-pill">{{ $unreadCount }}</span>
        @endif
    </a>
    <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Notifikace</h3>
                @if($unreadCount > 0)
                    <a href="#" class="btn btn-sm btn-link" wire:click="markAllAsRead">Označit vše jako přečtené</a>
                @endif
            </div>
            <div class="list-group list-group-flush list-group-hoverable">
                @forelse($notifications as $notification)
                    <div class="list-group-item {{ $notification->read_at ? 'bg-light' : 'fw-bold' }}">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h5 class="mb-0">
                                        @if(isset($notification->data['website_name']))
                                            {{ $notification->data['website_name'] }}
                                        @else
                                            Notifikace
                                        @endif
                                    </h5>
                                    <small class="text-muted">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </small>
                                </div>
                                <div>
                                    @if(isset($notification->data['message']))
                                        {{ $notification->data['message'] }}
                                    @elseif(isset($notification->data['website_url']))
                                        {{ $notification->data['website_url'] }}
                                    @else
                                        Podrobnosti nejsou k dispozici
                                    @endif
                                </div>
                                @if(isset($notification->data['tool_name']))
                                    <div class="small text-muted">
                                        Nástroj: {{ $notification->data['tool_name'] }}
                                    </div>
                                @endif
                                @if(!$notification->read_at)
                                    <div class="mt-1">
                                        <a href="#" wire:click="markAsRead('{{ $notification->id }}')" class="btn btn-sm btn-link">
                                            Označit jako přečtené
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="list-group-item">
                        <div class="text-center py-3">
                            <i class="la la-bell fs-2 text-muted mb-2"></i>
                            <p class="mb-0">Žádné nové notifikace</p>
                        </div>
                    </div>
                @endforelse
            </div>
            @if($notifications->count() > 0)
                <div class="card-footer text-center">
                    <a href="{{ backpack_url('notification') }}" class="btn btn-sm btn-primary">
                        Zobrazit všechny notifikace
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>