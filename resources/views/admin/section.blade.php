@extends('admin.layout')

@section('content')
    <div class="card table-card">
        <div class="table-head">
            <div>
                <h2>{{ $title }}</h2>
                <div class="muted">{{ $subtitle }}</div>
            </div>
            <div class="pill">{{ is_countable($rows) ? count($rows) : 0 }} zapisa</div>
        </div>

        <table>
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($headers) }}">Nema podataka.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
