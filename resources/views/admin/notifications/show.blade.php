@extends(backpack_view('blank'))

@section('header')
    <div class="container-fluid">
        <h2>
            <span class="text-capitalize">Detail notifikace</span>
        </h2>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        @if(isset($notification->data['website_name']))
                            {{ $notification->data['website_name'] }}
                        @else
                            Notifikace {{ $notification->id }}
                        @endif
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Status:</label>
                                <div>
                                    @if(is_null($notification->read_at))
                                        <span class="badge bg-primary">Nepřečteno</span>
                                    @else
                                        <span class="badge bg-secondary">Přečteno ({{ $notification->read_at->format('d.m.Y H:i') }})</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Typ:</label>
                                <div>
                                    @if($notification->type === 'App\Notifications\MonitoringFailureNotification')
                                        <span class="badge bg-danger">Monitoring - chyba</span>
                                    @else
                                        <span class="badge bg-info">{{ Str::afterLast($notification->type, '\\') }}</span>
                                    @endif
                                </div>
                            </div>
                            @if(isset($notification->data['website_url']))
                                <div class="mb-3">
                                    <label class="form-label font-weight-bold">URL:</label>
                                    <div>
                                        <a href="{{ $notification->data['website_url'] }}" target="_blank">
                                            {{ $notification->data['website_url'] }}
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Vytvořeno:</label>
                                <div>{{ $notification->created_at->format('d.m.Y H:i:s') }}</div>
                            </div>
                            @if(isset($notification->data['check_time']))
                                <div class="mb-3">
                                    <label class="form-label font-weight-bold">Čas kontroly:</label>
                                    <div>{{ \Carbon\Carbon::parse($notification->data['check_time'])->format('d.m.Y H:i:s') }}</div>
                                </div>
                            @endif
                            @if(isset($notification->data['tool_name']))
                                <div class="mb-3">
                                    <label class="form-label font-weight-bold">Nástroj:</label>
                                    <div>{{ $notification->data['tool_name'] }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    @if(isset($notification->data['message']))
                        <div class="mb-4">
                            <label class="form-label font-weight-bold">Zpráva:</label>
                            <div class="alert alert-warning">
                                {{ $notification->data['message'] }}
                            </div>
                        </div>
                    @endif
                    
                    <div class="mb-4">
                        <label class="form-label font-weight-bold">Data:</label>
                        <div class="alert alert-light">
                            <pre>{{ json_encode($notification->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                    
                    @if(isset($notification->data['result_id']))
                        <div class="mb-3">
                            <a href="{{ backpack_url('monitoring-result/' . $notification->data['result_id'] . '/show') }}" class="btn btn-info">
                                <i class="la la-eye"></i> Zobrazit výsledek monitoringu
                            </a>
                        </div>
                    @endif
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ backpack_url('notification') }}" class="btn btn-secondary">
                        <i class="la la-arrow-left"></i> Zpět na seznam
                    </a>
                    
                    <div>
                        <button onclick="deleteNotification('{{ $notification->id }}')" class="btn btn-danger">
                            <i class="la la-trash"></i> Smazat
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('after_scripts')
<script>
    function deleteNotification(id) {
        if (!confirm('Opravdu chcete smazat tuto notifikaci?')) {
            return;
        }
        
        fetch('{{ backpack_url('notification') }}/' + id, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '{{ backpack_url('notification') }}';
            }
        });
    }
</script>
@endsection