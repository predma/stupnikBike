@extends('admin.layout')

@section('content')
    <div class="grid stats">
        @foreach ($counts as $label => $value)
            <div class="card">
                <div class="stat-label">{{ str_replace('_', ' ', ucfirst($label)) }}</div>
                <div class="stat-value">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid dashboard-panels" style="margin-top: 20px;">
        <div class="card table-card" style="grid-column: span 2;">
            <div class="table-head">
                <div>
                    <h2>Aktivnost bicikala</h2>
                    <div class="muted">Najnoviji unos i trenutne stanice.</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Šifra</th>
                        <th>Naziv</th>
                        <th>Veličina</th>
                        <th>Lager</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentBikes as $bike)
                        <tr>
                            <td>{{ $bike->code }}</td>
                            <td>{{ $bike->name }}</td>
                            <td>{{ $bike->size ?? '-' }}</td>
                            <td>{{ $bike->stock_quantity }}</td>
                            <td>{{ $bike->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Nema podataka.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>Rezervacije</h3>
            <div class="panel-list" style="margin-top: 16px;">
                @forelse ($recentReservations as $reservation)
                    <div class="panel-item">
                        <strong>{{ $reservation->reservation_number }}</strong><br>
                        <span class="muted">{{ $reservation->user?->name ?? '-' }} · {{ $reservation->bike?->name ?? '-' }}</span><br>
                        <span class="pill" style="margin-top: 10px;">{{ $reservation->status }}</span>
                    </div>
                @empty
                    <div class="muted">Nema rezervacija.</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <h3>Kvarovi</h3>
            <div class="panel-list" style="margin-top: 16px;">
                @forelse ($recentIssues as $issue)
                    <div class="panel-item">
                        <strong>{{ $issue->title }}</strong><br>
                        <span class="muted">{{ $issue->bike?->name ?? '-' }}</span><br>
                        <span class="pill" style="margin-top: 10px;">{{ $issue->status }}</span>
                    </div>
                @empty
                    <div class="muted">Nema prijavljenih kvarova.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
