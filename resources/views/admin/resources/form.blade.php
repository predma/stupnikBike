@extends('admin.layout')

@section('content')
    <div class="card">
        <div class="table-head" style="padding: 0 0 18px 0;">
            <div>
                <h2>{{ $title }}</h2>
                <div class="muted">{{ $subtitle }}</div>
            </div>
            <a class="btn secondary" href="{{ $backUrl }}">Natrag</a>
        </div>

        <form method="POST" action="{{ $action }}">
            @csrf
            @if (($method ?? 'POST') !== 'POST')
                @method($method)
            @endif

            <div style="display:grid; gap:16px;">
                @foreach ($fields as $field)
                    @php
                        $value = old($field['name'], $values[$field['name']] ?? '');
                        $fieldType = $field['type'] ?? 'text';
                        $inputType = $fieldType === 'datetime-local' ? 'datetime-local' : ($fieldType === 'password' ? 'password' : $fieldType);
                    @endphp

                    <div class="field">
                        <label for="{{ $field['name'] }}" class="muted">
                            {{ $field['label'] }}
                            @if (!empty($field['required']))
                                <span style="color:#fda4af;">*</span>
                            @endif
                        </label>

                        @if ($fieldType === 'textarea')
                            <textarea id="{{ $field['name'] }}" name="{{ $field['name'] }}" rows="4">{{ $value }}</textarea>
                        @elseif ($fieldType === 'select')
                            <select id="{{ $field['name'] }}" name="{{ $field['name'] }}">
                                <option value="">Odaberi</option>
                                @foreach ($field['options'] as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                        @elseif ($fieldType === 'multiselect')
                            @php
                                $selectedValues = collect(old($field['name'], $values[$field['name']] ?? []))->map(fn ($item) => (string) $item)->all();
                            @endphp
                            <select id="{{ $field['name'] }}" name="{{ $field['name'] }}[]" multiple size="{{ min(8, max(3, count($field['options']))) }}">
                                @foreach ($field['options'] as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, $selectedValues, true))>{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                            <div class="muted" style="font-size: 12px;">Drži `Cmd`/`Ctrl` za višestruki odabir.</div>
                        @elseif ($fieldType === 'checkbox')
                            <label style="display:flex; align-items:center; gap:10px;">
                                <input type="checkbox" name="{{ $field['name'] }}" value="1" @checked((bool) $value)>
                                <span>{{ $field['label'] }}</span>
                            </label>
                        @else
                            <input
                                id="{{ $field['name'] }}"
                                name="{{ $field['name'] }}"
                                type="{{ $inputType }}"
                                @if ($inputType !== 'password')
                                    value="{{ $value }}"
                                @endif
                                step="{{ $field['step'] ?? 'any' }}"
                            >
                        @endif

                        @if (!empty($field['hint']))
                            <div class="muted" style="font-size: 12px;">{{ $field['hint'] }}</div>
                        @endif

                        @error($field['name'])
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div style="margin-top: 22px;">
                <button class="btn" type="submit">Spremi</button>
            </div>
        </form>
    </div>
@endsection
