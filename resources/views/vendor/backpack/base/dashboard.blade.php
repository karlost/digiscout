@extends(backpack_view('blank'))

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Failures</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Website</th>
                                    <th>Tool</th>
                                    <th>Time</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentFailures as $failure)
                                    <tr>
                                        <td>{{ $failure->website->name ?? 'Unknown' }}</td>
                                        <td>{{ $failure->monitoringTool->name ?? 'Unknown' }}</td>
                                        <td>{{ $failure->created_at->diffForHumans() }}</td>
                                        <td>
                                            @if(isset($failure->additional_data['message']))
                                                {{ $failure->additional_data['message'] }}
                                            @else
                                                No details available
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No recent failures</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Website Success Rates (7 days)</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($websiteStats as $website)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>{{ $website->name }}</span>
                                    <span class="badge {{ $website->success_rate >= 90 ? 'bg-success' : ($website->success_rate >= 75 ? 'bg-warning' : 'bg-danger') }}">
                                        {{ $website->success_rate }}%
                                    </span>
                                </div>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar {{ $website->success_rate >= 90 ? 'bg-success' : ($website->success_rate >= 75 ? 'bg-warning' : 'bg-danger') }}" 
                                         role="progressbar" 
                                         style="width: {{ $website->success_rate }}%;" 
                                         aria-valuenow="{{ $website->success_rate }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item">No website statistics available</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection