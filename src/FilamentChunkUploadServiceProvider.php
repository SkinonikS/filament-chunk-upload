<?php

namespace Iperamuna\FilamentChunkUpload;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentChunkUploadServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-chunk-upload')
            ->hasViews();
    }
}
