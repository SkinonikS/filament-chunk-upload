<?php

namespace Iperamuna\FilamentChunkUpload;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Str;
use RahulHaque\Filepond\Facades\Filepond;

class ChunkedFileUpload extends FileUpload
{
    protected string $view = 'filament-chunk-upload::chunked-file-upload';

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Configure default view data
        $this->chunkSize(1024 * 1024 * 10); // 10MB default

        $this->viewData([
            'filepondProcessUrl' => config('filepond.server.url', '/filepond'),
        ]);

        // 2. Override dehydration logic
        $this->dehydrateStateUsing(function ($state) {
            if (blank($state)) {
                return null;
            }

            if ($this->isMultiple()) {
                $files = is_array($state) ? $state : [$state];
                $paths = [];
                foreach ($files as $file) {
                    $paths[] = $this->processFileState($file);
                }

                return $paths;
            }

            return $this->processFileState(is_array($state) ? head($state) : $state);
        });

        // 3. Prevent Filament's default saving logic from running on ouralready-moved files
        $this->saveUploadedFileUsing(function ($file) {
            return $file;
        });
    }

    public function processFileState(string $state): string
    {
        try {
            // Check if this is a FilePond server ID (encrypted string)
            $filepond = Filepond::field($state);
            $model = $filepond->getModel();

            if ($model) {
                $directory = $this->getDirectory();
                $diskName = $this->getDiskName() ?? config('filament.default_disk', 'private');
                $visibility = $this->getVisibility() ?? 'private';

                // Generate target path
                // We use a random filename to avoid collisions, similar to Filament's default behavior
                // Note: The FilePond package automatically appends the extension, so we just provide the base name.
                $filename = Str::random(40);
                $targetPath = ($directory ? $directory.'/' : '').$filename;

                // Move file from temp to final destination
                // The moveTo method returns an array with file details
                $movedFile = $filepond->moveTo($targetPath, $diskName, $visibility);

                $finalPath = $movedFile['location'];

                // Handle "storeFileNamesIn" feature
                if ($this->getFileNamesStatePath()) {
                    // We need to store the ORIGINAL filename, which is in the model
                    $originalName = $model->filename; // The 'filename' column in fileponds table stores original name
                    $this->storeFileName($finalPath, $originalName);
                }

                return $finalPath;
            }
        } catch (\Throwable $e) {
            // State is likely already a file path or invalid ID, return as is
        }

        return $state;
    }

    public function getValidationRules(): array
    {
        $rules = [];

        if ($this->isRequired()) {
            $rules[] = 'required';
        }

        if ($this->isMultiple()) {
            if (filled($count = $this->getMinFiles())) {
                $rules[] = "min:{$count}";
            }
            if (filled($count = $this->getMaxFiles())) {
                $rules[] = "max:{$count}";
            }
        }

        return $rules;
    }

    public function chunkSize(int $bytes): static
    {
        $this->viewData(['chunkSize' => $bytes]);

        return $this;
    }
}
