[![Latest Version on Packagist](https://img.shields.io/packagist/v/iperamuna/filament-chunk-upload.svg?style=flat-square)](https://packagist.org/packages/iperamuna/filament-chunk-upload)
[![Total Downloads](https://img.shields.io/packagist/dt/iperamuna/filament-chunk-upload.svg?style=flat-square)](https://packagist.org/packages/iperamuna/filament-chunk-upload)
[![License](https://img.shields.io/packagist/l/iperamuna/filament-chunk-upload.svg?style=flat-square)](https://packagist.org/packages/iperamuna/filament-chunk-upload)

# Filament Chunk Upload

A Filament form component that enables chunked file uploads using FilePond, powered by `rahulhaque/laravel-filepond`. This package is designed to handle large file uploads reliably by splitting them into smaller chunks.

## Requirements

- PHP 8.2+
- Laravel 10+
- Filament 3+ (v5 supported)
- `rahulhaque/laravel-filepond` package installed and configured

## Installation

You can install the package via composer:

```bash
composer require iperamuna/filament-chunk-upload
```

Ensure you have configured `rahulhaque/laravel-filepond` according to its documentation.

### 1. Publish Configuration and Migrations

After installing the package, publish the configuration and migration files:

```bash
php artisan vendor:publish --provider="RahulHaque\Filepond\FilepondServiceProvider"
```

### 2. Run Migrations

Run the migrations to create the temporary file storage table:

```bash
php artisan migrate
```

### 3. Configure FilePond

Update your `.env` file to configure the disk and URL for FilePond:

```dotenv
FILEPOND_DISK=private
FILEPOND_TEMP_DISK=local
FILEPOND_URL=/filepond
```

Make sure your `config/filesystems.php` has a `private` disk defined if you choose to use it (recommended for secure uploads).

```php
'private' => [
    'driver' => 'local',
    'root' => storage_path('app/private'),
    'visibility' => 'private',
],
```

## Usage

use `Iperamuna\FilamentChunkUpload\ChunkedFileUpload` in your Filament resources or forms:

```php
use Iperamuna\FilamentChunkUpload\ChunkedFileUpload;

ChunkedFileUpload::make('attachment')
    ->label('Upload File')
    ->chunkSize(10485760) // Optional: Set chunk size in bytes (default 10MB)
    ->directory('uploads')
    ->visibility('private')
    ->required();
```

### Features

- **Chunked Uploads**: Automatically splits large files into chunks based on `chunkSize`.
- **Secure Dehydration**: Handles the secure transfer of temporary files to their final destination upon form submission.
- **Filament Integration**: inherites standard Filament validation and storage configuration (disk, directory, visibility).

## Serving Large Files (Secure Download)

Since this package is designed for large files often stored on a `private` disk, you should use a streamed download response to avoid memory exhaustion (OOM) errors in PHP.

Add a route to your `routes/web.php` to handle secure, signed downloads:

```php
use Illuminate\Support\Facades\Storage;

Route::get('/attachments/{attachment}/download', function (App\Models\Attachment $attachment) {
    // 1. Verify Signed URL for security
    if (! request()->hasValidSignature()) {
        abort(403);
    }

    $diskName = config('filament.default_disk', 'private');
    
    // 2. Stream the file to the client
    // We use streamDownload with manual buffer clearing to prevent memory issues with large files
    return response()->streamDownload(function () use ($diskName, $attachment) {
        $stream = Storage::disk($diskName)->readStream($attachment->file_path);

        // Clear output buffer to prevent OOM
        if (ob_get_level()) {
            ob_end_clean();
        }

        fpassthru($stream);

        if (is_resource($stream)) {
            fclose($stream);
        }
    }, $attachment->file_name);
})->name('attachments.download');
```

You can then generate a download link in your Filament resource or Blade view:

```php
use Illuminate\Support\Facades\URL;

URL::signedRoute('attachments.download', ['attachment' => $record->id]);
```

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Indunil Peramuna](https://github.com/iperamuna)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
