@extends('admin.layout')

@section('content')
    <div class="grid file-manager-grid">
        <div class="card">
            <div class="table-head" style="padding: 0 0 18px 0;">
                <div>
                    <h2>File Manager</h2>
                    <div class="muted">Slike se spremaju u <strong>/storage/media</strong> i mogu se koristiti u biciklima, kvarovima i budućim modulima.</div>
                </div>

                @if ($isPicker)
                    <button class="btn secondary" type="button" onclick="window.close()">Zatvori picker</button>
                @endif
            </div>

            <div class="breadcrumbs">
                @foreach ($breadcrumbs as $breadcrumb)
                    <a href="{{ route('admin.file-manager.index', ['directory' => $breadcrumb['directory'], 'picker' => $isPicker ? 1 : null, 'target' => $target]) }}">
                        {{ $breadcrumb['label'] }}
                    </a>
                    @if (! $loop->last)
                        <span>/</span>
                    @endif
                @endforeach
            </div>

            @error('file_manager')
                <div class="error" style="margin-bottom: 16px;">{{ $message }}</div>
            @enderror

            <div class="file-toolbar">
                <form method="POST" action="{{ route('admin.file-manager.directories.store') }}">
                    @csrf
                    <input type="hidden" name="directory" value="{{ $directory }}">
                    <div class="inline-form">
                        <input name="name" type="text" placeholder="Novi folder, npr. bicikli" required>
                        <button class="btn secondary" type="submit">Kreiraj folder</button>
                    </div>
                    @error('name')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </form>

                <form method="POST" action="{{ route('admin.file-manager.upload') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="directory" value="{{ $directory }}">
                    <div class="inline-form">
                        <input name="images[]" type="file" accept="image/*" multiple required>
                        <button class="btn" type="submit">Upload slika</button>
                    </div>
                    @error('images')
                        <div class="error">{{ $message }}</div>
                    @enderror
                    @error('images.*')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </form>
            </div>
        </div>

        <div class="card">
            <div class="file-section-head">
                <div>
                    <h3>Direktoriji</h3>
                    <div class="muted">{{ $directory === '' ? 'Root folder' : '/'.$directory }}</div>
                </div>

                @if ($parentDirectory !== null)
                    <a class="btn secondary" href="{{ route('admin.file-manager.index', ['directory' => $parentDirectory, 'picker' => $isPicker ? 1 : null, 'target' => $target]) }}">Natrag gore</a>
                @endif
            </div>

            <div class="folder-grid">
                @forelse ($directories as $folder)
                    <div class="folder-card">
                        <a class="folder-open" href="{{ route('admin.file-manager.index', ['directory' => $folder['directory'], 'picker' => $isPicker ? 1 : null, 'target' => $target]) }}">
                            <span class="folder-icon">DIR</span>
                            <strong>{{ $folder['name'] }}</strong>
                        </a>

                        <form method="POST" action="{{ route('admin.file-manager.destroy') }}" onsubmit="return confirm('Obrisati prazan folder?');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="path" value="{{ $folder['path'] }}">
                            <input type="hidden" name="type" value="directory">
                            <button class="link-danger" type="submit">Obriši</button>
                        </form>
                    </div>
                @empty
                    <div class="muted">Nema foldera u ovom direktoriju.</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="file-section-head">
                <div>
                    <h3>Slike</h3>
                    <div class="muted">{{ $files->count() }} datoteka</div>
                </div>
            </div>

            <div class="media-grid">
                @forelse ($files as $file)
                    <article class="media-card">
                        <button
                            class="media-preview"
                            type="button"
                            @if ($isPicker)
                                data-picker-url="{{ $file['url'] }}"
                                data-picker-target="{{ $target }}"
                            @endif
                        >
                            <img src="{{ $file['url'] }}" alt="{{ $file['name'] }}">
                        </button>

                        <div class="media-meta">
                            <strong title="{{ $file['name'] }}">{{ $file['name'] }}</strong>
                            <span>{{ $file['size'] }}</span>
                        </div>

                        <div class="media-actions">
                            <button class="btn secondary copy-url" type="button" data-copy-url="{{ $file['url'] }}">Kopiraj URL</button>

                            @if ($isPicker)
                                <button class="btn pick-file" type="button" data-picker-url="{{ $file['url'] }}" data-picker-target="{{ $target }}">Odaberi</button>
                            @else
                                <a class="btn secondary" href="{{ $file['url'] }}" target="_blank" rel="noopener">Otvori</a>
                            @endif

                            <form method="POST" action="{{ route('admin.file-manager.destroy') }}" onsubmit="return confirm('Obrisati sliku?');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="path" value="{{ $file['path'] }}">
                                <input type="hidden" name="type" value="file">
                                <button class="link-danger" type="submit">Obriši</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="muted">Nema uploadanih slika u ovom direktoriju.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('[data-picker-url]').forEach((button) => {
            button.addEventListener('click', () => {
                const payload = {
                    type: 'stupnik-bike:file-selected',
                    target: button.dataset.pickerTarget || 'image_url',
                    url: button.dataset.pickerUrl,
                };

                if (window.opener) {
                    window.opener.postMessage(payload, window.location.origin);
                    window.close();
                    return;
                }

                navigator.clipboard?.writeText(payload.url);
            });
        });

        document.querySelectorAll('[data-copy-url]').forEach((button) => {
            button.addEventListener('click', async () => {
                await navigator.clipboard.writeText(button.dataset.copyUrl);
                button.textContent = 'Kopirano';
                setTimeout(() => button.textContent = 'Kopiraj URL', 1200);
            });
        });
    </script>
@endsection
