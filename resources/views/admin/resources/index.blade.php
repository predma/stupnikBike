@extends('admin.layout')

@section('content')
    <div class="card table-card">
        <div class="table-head">
            <div>
                <h2>{{ $title }}</h2>
                <div class="muted">{{ $subtitle }}</div>
            </div>
            <a class="btn" href="{{ $createUrl }}">+ Novi zapis</a>
        </div>

        <table>
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                    <th>Akcije</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @foreach ($row['cells'] as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                        <td style="white-space: nowrap;">
                            <div style="display:flex; gap:10px; flex-wrap: wrap;">
                                <a class="btn secondary" href="{{ $row['editUrl'] }}">Uredi</a>
                                <form method="POST" action="{{ $row['deleteUrl'] }}" onsubmit="return confirm('Obrisati zapis?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn secondary" type="submit" style="border-color: rgba(251, 113, 133, 0.35); color: #fecdd3;">Obriši</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($headers) + 1 }}">Nema podataka.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
