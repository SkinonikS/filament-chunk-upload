<?php

use Iperamuna\FilamentChunkUpload\ChunkedFileUpload;

uses(\Iperamuna\FilamentChunkUpload\Tests\TestCase::class);

it('can initialize the component', function () {
    $component = ChunkedFileUpload::make('attachment');

    expect($component)
        ->toBeInstanceOf(ChunkedFileUpload::class)
        ->getName()->toBe('attachment');
});

it('validation rules contain required if set', function () {
    $component = ChunkedFileUpload::make('attachment')->required();

    expect($component->getValidationRules())
        ->toBeArray()
        ->toContain('required');
});
