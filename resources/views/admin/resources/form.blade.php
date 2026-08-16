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
                        @elseif ($fieldType === 'image')
                            <div class="image-picker">
                                <input
                                    id="{{ $field['name'] }}"
                                    name="{{ $field['name'] }}"
                                    type="text"
                                    value="{{ $value }}"
                                    placeholder="/storage/media/bicikli/slika.jpg"
                                    data-image-input="{{ $field['name'] }}"
                                >
                                <button
                                    class="btn secondary"
                                    type="button"
                                    data-open-file-manager
                                    data-target="{{ $field['name'] }}"
                                >
                                    Odaberi iz File Managera
                                </button>
                            </div>
                            <img
                                class="image-preview"
                                data-image-preview="{{ $field['name'] }}"
                                src="{{ $value }}"
                                alt="Pregled slike"
                                onerror="this.removeAttribute('src')"
                            >
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

@section('scripts')
    <script>
        function refreshImagePreview(target, url) {
            const preview = document.querySelector(`[data-image-preview="${target}"]`);

            if (!preview) {
                return;
            }

            if (url) {
                preview.src = url;
                return;
            }

            preview.removeAttribute('src');
        }

        document.querySelectorAll('[data-open-file-manager]').forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.dataset.target;
                const url = new URL(@json(route('admin.file-manager.index')), window.location.origin);
                url.searchParams.set('picker', '1');
                url.searchParams.set('target', target);

                window.open(url.toString(), 'stupnikBikeFileManager', 'width=1180,height=820,resizable=yes,scrollbars=yes');
            });
        });

        document.querySelectorAll('[data-image-input]').forEach((input) => {
            input.addEventListener('input', () => refreshImagePreview(input.dataset.imageInput, input.value));
        });

        window.addEventListener('message', (event) => {
            if (event.origin !== window.location.origin || event.data?.type !== 'stupnik-bike:file-selected') {
                return;
            }

            const input = document.querySelector(`[data-image-input="${event.data.target}"]`);

            if (!input) {
                return;
            }

            input.value = event.data.url;
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
    </script>
@endsection
