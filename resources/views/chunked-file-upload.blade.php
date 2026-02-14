<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div wire:ignore x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('file-upload', 'filament/forms') }}"
        x-data="{
            pond: null,
            state: $wire.$entangle('{{ $getStatePath() }}'),
            init() {
                if (this.pond) return;

                const serverConfig = {
                    process: '{{ $filepondProcessUrl }}',
                    revert: '{{ $filepondProcessUrl }}',
                    patch: '{{ $filepondProcessUrl }}?patch=',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                };

                this.pond = FilePond.create(this.$refs.input, {
                    server: serverConfig,
                    credits: false,
                    allowMultiple: {{ $isMultiple() ? 'true' : 'false' }},
                    maxFileSize: @js(($size = $getMaxSize()) ? "{$size}KB" : null),
                    acceptedFileTypes: @js($getAcceptedFileTypes()),
                    labelIdle: @js($getPlaceholder() ?? 'Drag & Drop your files or <span class="filepond--label-action">Browse</span>'),
                    chunkUploads: true,
                    chunkForce: true,
                    chunkSize: {{ $chunkSize ?? 10485760 }}, // Default 10MB
                    // File Loading (for editing existing files)
                    // files: this.getInitialFiles(),
                    allowReorder: true,
                    onprocessfile: (error, file) => {
                        if (error) return;
                        this.addFileToState(file.serverId);
                    },
                    onremovefile: (error, file) => {
                        if (error) return;
                        this.removeFileFromState(file.serverId);
                    },
                    onreorderfiles: (files) => {
                        this.state = files.map(f => f.serverId);
                    }
                });
                
                // Watch state changes from outside (e.g. form reset)
                this.$watch('state', (value) => {
                    // Logic to sync external state changes back to FilePond if needed
                    // But usually cumbersome. For now we assume one-way sync Uploader -> State
                });
            },
            getInitialFiles() {
                // Return array of file objects for existing files
                // This is tricky because we only have paths/IDs in state
                // We'd need to load them. For now, empty or load via 'load' endpoint if configured.
                // Leaving empty for new uploads. 
                // To support editing, we'd need to map state to { source: '...', options: { type: 'local' } }
                
                let files = [];
                if (this.state) {
                    const paths = Array.isArray(this.state) ? this.state : [this.state];
                    paths.forEach(p => {
                       if (p) {
                           files.push({
                               source: p,
                               options: {
                                   type: 'local' // standard generic type
                               }
                           });
                       }
                    });
                }
                return files;
            },
            addFileToState(id) {
                if (!id) return;
                if ({{ $isMultiple() ? 'true' : 'false' }}) {
                    this.state = [...(this.state || []), id];
                } else {
                    this.state = id;
                }
            },
            removeFileFromState(id) {
                if (!id) return;
                if ({{ $isMultiple() ? 'true' : 'false' }}) {
                    this.state = (this.state || []).filter(item => item !== id);
                } else {
                    this.state = null;
                }
            }
        }">
        <input type="file" x-ref="input" />
    </div>
</x-dynamic-component>