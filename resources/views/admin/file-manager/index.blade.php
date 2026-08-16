@extends('admin.layout')

@section('content')
    <section class="fm-shell">
        <aside class="fm-sidebar">
            <div class="fm-sidebar-head">
                <div>
                    <div class="fm-kicker">Storage</div>
                    <h2>Media Library</h2>
                </div>

                @if ($isPicker)
                    <button class="btn secondary compact" type="button" onclick="window.close()">Zatvori</button>
                @endif
            </div>

            <a class="fm-root-link {{ $directory === '' ? 'active' : '' }}" href="{{ route('admin.file-manager.index', ['picker' => $isPicker ? 1 : null, 'target' => $target]) }}">
                <span class="fm-folder-mark">ROOT</span>
                <span>
                    <strong>Media</strong>
                    <small>/storage/media</small>
                </span>
            </a>

            <div class="fm-folder-list">
                @if ($parentDirectory !== null)
                    <a class="fm-folder-row" href="{{ route('admin.file-manager.index', ['directory' => $parentDirectory, 'picker' => $isPicker ? 1 : null, 'target' => $target]) }}">
                        <span class="fm-folder-mark">UP</span>
                        <strong>Folder iznad</strong>
                    </a>
                @endif

                @forelse ($directories as $folder)
                    <div class="fm-folder-row-wrap">
                        <a class="fm-folder-row" href="{{ route('admin.file-manager.index', ['directory' => $folder['directory'], 'picker' => $isPicker ? 1 : null, 'target' => $target]) }}">
                            <span class="fm-folder-mark">DIR</span>
                            <strong>{{ $folder['name'] }}</strong>
                        </a>

                        <form method="POST" action="{{ route('admin.file-manager.destroy') }}" onsubmit="return confirm('Obrisati prazan folder?');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="path" value="{{ $folder['path'] }}">
                            <input type="hidden" name="type" value="directory">
                            <button class="fm-icon-danger" type="submit" title="Obriši prazan folder">×</button>
                        </form>
                    </div>
                @empty
                    <div class="fm-empty-mini">Nema podfoldera.</div>
                @endforelse
            </div>

            <form class="fm-create-folder" method="POST" action="{{ route('admin.file-manager.directories.store') }}">
                @csrf
                <input type="hidden" name="directory" value="{{ $directory }}">
                <label>Novi folder</label>
                <div>
                    <input name="name" type="text" placeholder="npr. bicikli" required>
                    <button class="btn secondary compact" type="submit">Dodaj</button>
                </div>
                @error('name')
                    <span class="error">{{ $message }}</span>
                @enderror
            </form>
        </aside>

        <div class="fm-main">
            <div class="fm-top">
                <div>
                    <div class="fm-kicker">File Manager</div>
                    <h2>{{ $directory === '' ? 'Media' : basename($directory) }}</h2>
                    <div class="fm-path">
                        @foreach ($breadcrumbs as $breadcrumb)
                            <a href="{{ route('admin.file-manager.index', ['directory' => $breadcrumb['directory'], 'picker' => $isPicker ? 1 : null, 'target' => $target]) }}">
                                {{ $breadcrumb['label'] }}
                            </a>
                            @if (! $loop->last)
                                <span>/</span>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="fm-stats">
                    <span>{{ $directories->count() }} foldera</span>
                    <span>{{ $files->count() }} slika</span>
                </div>
            </div>

            @error('file_manager')
                <div class="error" style="margin-bottom: 16px;">{{ $message }}</div>
            @enderror

            <form class="fm-upload" method="POST" action="{{ route('admin.file-manager.upload') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="directory" value="{{ $directory }}">
                <label class="fm-dropzone">
                    <span class="fm-upload-icon">UPLOAD</span>
                    <span>
                        <strong>Upload slika u ovaj folder</strong>
                        <small>JPG, PNG, WEBP, GIF ili SVG, više slika odjednom.</small>
                    </span>
                    <input name="images[]" type="file" accept="image/*" multiple required>
                </label>
                <button class="btn" type="submit">Upload</button>
            </form>

            @error('images')
                <div class="error">{{ $message }}</div>
            @enderror
            @error('images.*')
                <div class="error">{{ $message }}</div>
            @enderror

            <div class="fm-browser-head">
                <div>
                    <h3>Datoteke</h3>
                    <p class="muted">Klik na sliku u picker modu odmah odabire URL.</p>
                </div>
            </div>

            <div class="fm-media-grid">
                @forelse ($files as $file)
                    <article class="fm-file">
                        <button
                            class="fm-thumb"
                            type="button"
                            @if ($isPicker)
                                data-picker-url="{{ $file['url'] }}"
                                data-picker-target="{{ $target }}"
                            @endif
                        >
                            <img src="{{ $file['url'] }}" alt="{{ $file['name'] }}">
                        </button>

                        <div class="fm-file-body">
                            <div>
                                <strong title="{{ $file['name'] }}">{{ $file['name'] }}</strong>
                                <span>{{ $file['size'] }}</span>
                            </div>

                            <div class="fm-file-actions">
                                @if ($isPicker)
                                    <button class="btn compact pick-file" type="button" data-picker-url="{{ $file['url'] }}" data-picker-target="{{ $target }}">Odaberi</button>
                                @else
                                    <a class="btn secondary compact" href="{{ $file['url'] }}" target="_blank" rel="noopener">Otvori</a>
                                @endif

                                <button class="btn secondary compact copy-url" type="button" data-copy-url="{{ $file['url'] }}">URL</button>

                                <form method="POST" action="{{ route('admin.file-manager.destroy') }}" onsubmit="return confirm('Obrisati sliku?');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="path" value="{{ $file['path'] }}">
                                    <input type="hidden" name="type" value="file">
                                    <button class="btn danger compact" type="submit">Obriši</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="fm-empty">
                        <strong>Ovaj folder je prazan.</strong>
                        <span>Uploadaj slike gore ili napravi folder lijevo.</span>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
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
                setTimeout(() => button.textContent = 'URL', 1200);
            });
        });
    </script>
@endsection
