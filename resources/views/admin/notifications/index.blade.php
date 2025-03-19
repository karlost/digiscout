@extends(backpack_view('blank'))

@section('header')
    <div class="container-fluid">
        <h2>
            <span class="text-capitalize">Notifikace</span>
        </h2>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h3 class="card-title">Seznam notifikací</h3>
                        <div>
                            @if(Auth::user()->unreadNotifications->count() > 0)
                                <a href="#" class="btn btn-sm btn-outline-primary mark-all-read">Označit vše jako přečtené</a>
                            @endif
                            <div class="dropdown d-inline-block ml-2">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="bulkActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Hromadné akce
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="bulkActionsDropdown">
                                    <li><a class="dropdown-item delete-all" href="#">Smazat vybrané</a></li>
                                    <li><a class="dropdown-item mark-selected-read" href="#">Označit vybrané jako přečtené</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex">
                        <div class="btn-group" role="group">
                            <a href="{{ url()->current() }}" class="btn btn-sm {{ !request()->has('status') ? 'btn-primary' : 'btn-outline-primary' }}">Všechny</a>
                            <a href="{{ url()->current() }}?status=unread" class="btn btn-sm {{ request()->get('status') === 'unread' ? 'btn-primary' : 'btn-outline-primary' }}">Nepřečtené</a>
                            <a href="{{ url()->current() }}?status=read" class="btn btn-sm {{ request()->get('status') === 'read' ? 'btn-primary' : 'btn-outline-primary' }}">Přečtené</a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="select-all">
                                    </th>
                                    <th>Status</th>
                                    <th>Typ</th>
                                    <th>Předmět</th>
                                    <th>Zpráva</th>
                                    <th>Vytvořeno</th>
                                    <th>Akce</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($notifications as $notification)
                                    <tr class="{{ is_null($notification->read_at) ? 'fw-bold' : '' }}">
                                        <td>
                                            <input type="checkbox" class="notification-checkbox" value="{{ $notification->id }}">
                                        </td>
                                        <td>
                                            @if(is_null($notification->read_at))
                                                <span class="badge bg-primary">Nepřečteno</span>
                                            @else
                                                <span class="badge bg-secondary">Přečteno</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($notification->type === 'App\Notifications\MonitoringFailureNotification')
                                                <span class="badge bg-danger">Monitoring - chyba</span>
                                            @else
                                                <span class="badge bg-info">{{ Str::afterLast($notification->type, '\\') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($notification->data['website_name']))
                                                {{ $notification->data['website_name'] }}
                                            @else
                                                Notifikace {{ $notification->id }}
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($notification->data['message']))
                                                {{ Str::limit($notification->data['message'], 50) }}
                                            @elseif(isset($notification->data['website_url']))
                                                {{ Str::limit($notification->data['website_url'], 50) }}
                                            @else
                                                <em>Bez podrobností</em>
                                            @endif
                                        </td>
                                        <td>{{ $notification->created_at->format('d.m.Y H:i') }}</td>
                                        <td>
                                            <a href="{{ backpack_url('notification/'.$notification->id) }}" class="btn btn-sm btn-outline-info" title="Zobrazit detail">
                                                <i class="la la-eye"></i>
                                            </a>
                                            @if(is_null($notification->read_at))
                                                <a href="javascript:void(0);" class="btn btn-sm btn-outline-success mark-read" data-id="{{ $notification->id }}" title="Označit jako přečtené">
                                                    <i class="la la-check"></i>
                                                </a>
                                            @endif
                                            <a href="javascript:void(0);" class="btn btn-sm btn-outline-danger delete-notification" data-id="{{ $notification->id }}" title="Smazat">
                                                <i class="la la-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="la la-bell fs-2 text-muted mb-2 d-block"></i>
                                            <p class="mb-0">Žádné notifikace nejsou k dispozici</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($notifications->total() > $notifications->perPage())
                    <div class="card-footer">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('after_scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Select all checkbox
        const selectAllCheckbox = document.getElementById('select-all');
        const notificationCheckboxes = document.querySelectorAll('.notification-checkbox');
        
        selectAllCheckbox.addEventListener('change', function() {
            notificationCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
        
        // Mark as read
        document.querySelectorAll('.mark-read').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                
                fetch(`{{ backpack_url('notification/mark-as-read') }}/${id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Refresh the page
                        window.location.reload();
                    }
                });
            });
        });
        
        // Mark all as read
        document.querySelector('.mark-all-read')?.addEventListener('click', function(e) {
            e.preventDefault();
            
            fetch(`{{ backpack_url('notification/mark-all-as-read') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh the page
                    window.location.reload();
                }
            });
        });
        
        // Delete notification
        document.querySelectorAll('.delete-notification').forEach(button => {
            button.addEventListener('click', function() {
                if (!confirm('Opravdu chcete smazat tuto notifikaci?')) {
                    return;
                }
                
                const id = this.getAttribute('data-id');
                
                fetch(`{{ backpack_url('notification') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Refresh the page
                        window.location.reload();
                    }
                });
            });
        });
        
        // Mark selected as read
        document.querySelector('.mark-selected-read')?.addEventListener('click', function(e) {
            e.preventDefault();
            
            const selectedIds = Array.from(document.querySelectorAll('.notification-checkbox:checked')).map(checkbox => checkbox.value);
            
            if (selectedIds.length === 0) {
                alert('Nejsou vybrány žádné notifikace');
                return;
            }
            
            // Create a series of promises for each notification
            const promises = selectedIds.map(id => {
                return fetch(`{{ backpack_url('notification/mark-as-read') }}/${id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                }).then(response => response.json());
            });
            
            Promise.all(promises).then(() => {
                window.location.reload();
            });
        });
        
        // Delete selected
        document.querySelector('.delete-all')?.addEventListener('click', function(e) {
            e.preventDefault();
            
            const selectedIds = Array.from(document.querySelectorAll('.notification-checkbox:checked')).map(checkbox => checkbox.value);
            
            if (selectedIds.length === 0) {
                alert('Nejsou vybrány žádné notifikace');
                return;
            }
            
            if (!confirm(`Opravdu chcete smazat ${selectedIds.length} vybraných notifikací?`)) {
                return;
            }
            
            // Create a series of promises for each notification
            const promises = selectedIds.map(id => {
                return fetch(`{{ backpack_url('notification') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                }).then(response => response.json());
            });
            
            Promise.all(promises).then(() => {
                window.location.reload();
            });
        });
    });
</script>
@endsection