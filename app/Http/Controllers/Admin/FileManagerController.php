<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FileManagerController extends Controller
{
    private const ROOT = 'media';

    public function index(Request $request): View
    {
        $directory = $this->cleanDirectory((string) $request->query('directory', ''));
        $disk = Storage::disk('public');
        $rootedDirectory = $this->rootedPath($directory);

        $disk->makeDirectory(self::ROOT);
        $disk->makeDirectory($rootedDirectory);

        $directories = collect($disk->directories($rootedDirectory))
            ->map(fn (string $path) => $this->directoryItem($path))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $files = collect($disk->files($rootedDirectory))
            ->filter(fn (string $path) => $this->isImage($path))
            ->map(fn (string $path) => $this->fileItem($path))
            ->sortByDesc('modified_at')
            ->values();

        return view('admin.file-manager.index', [
            'title' => 'File Manager',
            'pageTitle' => 'File Manager',
            'pageSubtitle' => 'Upload slika, folderi i odabir URL-a za admin forme.',
            'directory' => $directory,
            'directories' => $directories,
            'files' => $files,
            'breadcrumbs' => $this->breadcrumbs($directory),
            'parentDirectory' => $this->parentDirectory($directory),
            'isPicker' => $request->boolean('picker'),
            'target' => (string) $request->query('target', 'image_url'),
        ]);
    }

    public function storeDirectory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'directory' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9 _-]+$/'],
        ]);

        $directory = $this->cleanDirectory($data['directory'] ?? '');
        $folderName = Str::slug($data['name'], '-');

        Storage::disk('public')->makeDirectory($this->rootedPath(trim($directory.'/'.$folderName, '/')));

        return back()->with('status', 'Folder je kreiran.');
    }

    public function upload(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'directory' => ['nullable', 'string', 'max:255'],
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'max:10240'],
        ]);

        $directory = $this->cleanDirectory($data['directory'] ?? '');
        $targetDirectory = $this->rootedPath($directory);
        $disk = Storage::disk('public');
        $disk->makeDirectory($targetDirectory);

        foreach ($request->file('images', []) as $image) {
            $baseName = Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'slika';
            $extension = strtolower($image->getClientOriginalExtension() ?: $image->extension());
            $filename = $this->uniqueFilename($targetDirectory, $baseName, $extension);

            $image->storeAs($targetDirectory, $filename, 'public');
        }

        return back()->with('status', 'Slike su uploadane.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:file,directory'],
        ]);

        $path = $this->cleanRootedPath($data['path']);
        $disk = Storage::disk('public');

        if ($data['type'] === 'file' && $disk->exists($path)) {
            $disk->delete($path);

            return back()->with('status', 'Slika je obrisana.');
        }

        if ($data['type'] === 'directory' && $disk->exists($path) && $path !== self::ROOT) {
            if (count($disk->allFiles($path)) > 0 || count($disk->directories($path)) > 0) {
                return back()->withErrors(['file_manager' => 'Folder mora biti prazan prije brisanja.']);
            }

            $disk->deleteDirectory($path);

            return back()->with('status', 'Folder je obrisan.');
        }

        return back()->withErrors(['file_manager' => 'Tražena stavka ne postoji.']);
    }

    private function directoryItem(string $path): array
    {
        return [
            'name' => basename($path),
            'path' => $path,
            'directory' => $this->relativeDirectory($path),
        ];
    }

    private function fileItem(string $path): array
    {
        $disk = Storage::disk('public');

        return [
            'name' => basename($path),
            'path' => $path,
            'url' => $disk->url($path),
            'size' => $this->formatBytes($disk->size($path)),
            'modified_at' => $disk->lastModified($path),
        ];
    }

    private function cleanDirectory(string $directory): string
    {
        $directory = trim(str_replace('\\', '/', $directory), '/');
        $parts = collect(explode('/', $directory))
            ->filter(fn (string $part) => $part !== '' && $part !== '.' && $part !== '..')
            ->map(fn (string $part) => Str::slug($part, '-'))
            ->filter()
            ->values()
            ->all();

        return implode('/', $parts);
    }

    private function cleanRootedPath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');

        if ($path === self::ROOT) {
            return self::ROOT;
        }

        if (str_starts_with($path, self::ROOT.'/')) {
            $path = substr($path, strlen(self::ROOT) + 1);
        }

        $parts = collect(explode('/', $path))
            ->filter(fn (string $part) => $part !== '' && $part !== '.' && $part !== '..')
            ->map(fn (string $part) => preg_replace('/[^A-Za-z0-9._-]/', '-', $part))
            ->filter()
            ->values()
            ->all();

        return self::ROOT.'/'.implode('/', $parts);
    }

    private function rootedPath(string $directory): string
    {
        $directory = $this->cleanDirectory($directory);

        return $directory === '' ? self::ROOT : self::ROOT.'/'.$directory;
    }

    private function relativeDirectory(string $path): string
    {
        $path = trim($path, '/');

        return $path === self::ROOT ? '' : Str::after($path, self::ROOT.'/');
    }

    private function parentDirectory(string $directory): ?string
    {
        if ($directory === '') {
            return null;
        }

        $parts = explode('/', $directory);
        array_pop($parts);

        return implode('/', $parts);
    }

    private function breadcrumbs(string $directory): array
    {
        $breadcrumbs = [['label' => 'Media', 'directory' => '']];
        $current = '';

        foreach (array_filter(explode('/', $directory)) as $part) {
            $current = trim($current.'/'.$part, '/');
            $breadcrumbs[] = ['label' => $part, 'directory' => $current];
        }

        return $breadcrumbs;
    }

    private function uniqueFilename(string $directory, string $baseName, string $extension): string
    {
        $disk = Storage::disk('public');
        $filename = "{$baseName}.{$extension}";
        $counter = 2;

        while ($disk->exists($directory.'/'.$filename)) {
            $filename = "{$baseName}-{$counter}.{$extension}";
            $counter++;
        }

        return $filename;
    }

    private function isImage(string $path): bool
    {
        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }
}
