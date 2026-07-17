<?php

namespace App\Livewire;

use App\Actions\DetectSourceColumns;
use App\Actions\DetectSourceFormat;
use App\Enums\Protocol;
use App\Enums\SourceParserType;
use App\Models\Source;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
class SourcesIndex extends Component
{
    use WithFileUploads;
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|url|max:2048')]
    public string $url = '';

    public ?string $parser_type = null;

    public array $parser_config = [];

    public ?string $default_protocol = null;

    public bool $isDetecting = false;

    public ?string $detectionError = null;

    public ?string $detectionNotice = null;

    #[Validate('nullable|file|mimes:json|max:10240')]
    public $importFile = null;

    public bool $allEnabled;

    public function updatedUrl(string $value): void
    {
        $rewritten = self::rewriteGitHubUrl($value);

        if ($rewritten !== null && $rewritten !== $value) {
            $this->url = $rewritten;
        }
    }

    private static function rewriteGitHubUrl(string $url): ?string
    {
        $pattern = '#^https?://github\.com/([^/]+)/([^/]+)/blob/([^/]+)/(.+)$#i';

        if (preg_match($pattern, $url, $matches)) {
            return sprintf(
                'https://raw.githubusercontent.com/%s/%s/%s/%s',
                $matches[1],
                $matches[2],
                $matches[3],
                $matches[4],
            );
        }

        return null;
    }

    public function mount(): void
    {
        $this->allEnabled = Source::where('is_enabled', false)->doesntExist();
    }

    public function toggleEnabled(int $id): void
    {
        if ($this->isProduction()) {
            return;
        }

        Source::where('id', $id)->update(['is_enabled' => DB::raw('NOT is_enabled')]);
    }

    public function addNew(): void
    {
        if ($this->isProduction()) {
            return;
        }

        $this->reset(['showModal', 'editingId', 'name', 'url', 'parser_type', 'parser_config', 'default_protocol', 'isDetecting', 'detectionError', 'detectionNotice']);
        $this->showModal = true;
    }

    public function cancel(): void
    {
        $this->reset(['showModal', 'editingId', 'name', 'url', 'parser_type', 'parser_config', 'default_protocol', 'isDetecting', 'detectionError', 'detectionNotice']);
    }

    public function edit(int $id): void
    {
        if ($this->isProduction()) {
            return;
        }

        $this->reset(['isDetecting', 'detectionError', 'detectionNotice']);

        $source = Source::findOrFail($id);

        $this->editingId = $id;
        $this->name = $source->name;
        $this->url = $source->url;
        $this->parser_type = $source->parser_type?->value;
        $this->parser_config = $source->parser_config ?? [];
        $this->default_protocol = $source->default_protocol?->value;
        $this->showModal = true;
    }

    public function detectSource(): void
    {
        $this->validateOnly('url');

        $this->isDetecting = true;
        $this->detectionError = null;
        $this->detectionNotice = null;
        $this->parser_type = null;
        $this->parser_config = [];

        try {
            $format = app(DetectSourceFormat::class)($this->url);
        } catch (\Throwable) {
            $this->detectionError = __('Could not reach the URL. Please check that it is correct and the server is reachable.');
            $this->isDetecting = false;

            return;
        }

        $this->parser_type = $format->value;

        if ($format === SourceParserType::HtmlTable) {
            try {
                $config = app(DetectSourceColumns::class)($this->url);
            } catch (\Throwable) {
                $this->detectionError = __('The page was reached, but column detection failed. You can fill in the column configuration manually below.');
                $this->isDetecting = false;

                return;
            }

            if ($config !== null) {
                $this->parser_config = [
                    'row_selector' => $config->rowSelector,
                    'address_col' => $config->addressCol,
                    'port_col' => $config->portCol,
                ];
            } else {
                $this->detectionNotice = __('Detected as an HTML table, but couldn\'t confidently identify the columns. Please fill them in manually below.');
            }
        }

        $this->isDetecting = false;
    }

    public function save(): void
    {
        if ($this->isProduction()) {
            return;
        }

        $this->validate();

        $data = [
            'name' => $this->name,
            'url' => $this->url,
            'parser_type' => $this->parser_type ? SourceParserType::from($this->parser_type) : null,
            'parser_config' => $this->parser_config
                ? [
                    'row_selector' => $this->parser_config['row_selector'] ?? null,
                    'address_col' => isset($this->parser_config['address_col']) ? (int) $this->parser_config['address_col'] : null,
                    'port_col' => isset($this->parser_config['port_col']) ? (int) $this->parser_config['port_col'] : null,
                ]
                : null,
            'default_protocol' => $this->default_protocol ? Protocol::from($this->default_protocol) : null,
        ];

        if ($this->editingId) {
            Source::findOrFail($this->editingId)->update($data);
        } else {
            Source::create($data);
        }

        $this->reset(['showModal', 'editingId', 'name', 'url', 'parser_type', 'parser_config', 'default_protocol', 'isDetecting', 'detectionError', 'detectionNotice']);

        Flux::toast(__('Source saved'), variant: 'success');
    }

    public function delete(int $id): void
    {
        if ($this->isProduction()) {
            return;
        }

        Source::findOrFail($id)->delete();
    }

    public function updatedAllEnabled(bool $value): void
    {
        if ($this->isProduction()) {
            return;
        }

        Source::query()->update(['is_enabled' => $value]);
    }

    public bool $showClearModal = false;

    public function clearAll(): void
    {
        if ($this->isProduction()) {
            return;
        }

        Source::query()->delete();

        $this->showClearModal = false;
    }

    public function exportSources(): StreamedResponse
    {
        $sources = Source::all()->map(fn (Source $s) => [
            'name' => $s->name,
            'url' => $s->url,
            'is_enabled' => $s->is_enabled,
            'parser_type' => $s->parser_type?->value,
            'parser_config' => $s->parser_config,
            'default_protocol' => $s->default_protocol?->value,
        ]);

        return response()->streamDownload(fn () => print $sources->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 'sources.json');
    }

    public function updatedImportFile(): void
    {
        if ($this->isProduction()) {
            return;
        }

        $this->validateOnly('importFile');

        $data = json_decode(file_get_contents($this->importFile->getRealPath()), true);

        if (! is_array($data)) {
            Flux::toast(__('Invalid JSON file.'), variant: 'danger');
            $this->importFile = null;

            return;
        }

        $existingUrls = Source::pluck('url')->toArray();
        $seenUrls = [];
        $imported = 0;
        $skipped = 0;

        foreach ($data as $item) {
            if (! is_array($item) || empty($item['name']) || empty($item['url'])) {
                continue;
            }

            $url = $item['url'];

            if (in_array($url, $existingUrls, true) || in_array($url, $seenUrls, true)) {
                $skipped++;

                continue;
            }

            $seenUrls[] = $url;

            Source::create([
                'name' => $item['name'],
                'url' => $url,
                'is_enabled' => $item['is_enabled'] ?? true,
                'parser_type' => isset($item['parser_type']) ? SourceParserType::tryFrom($item['parser_type']) : null,
                'parser_config' => $item['parser_config'] ?? null,
                'default_protocol' => isset($item['default_protocol']) ? Protocol::tryFrom($item['default_protocol']) : null,
            ]);

            $imported++;
        }

        $this->importFile = null;

        $msg = __(':count sources imported.', ['count' => $imported]);

        if ($skipped > 0) {
            $msg .= ' '.__(':count duplicates skipped.', ['count' => $skipped]);
        }

        Flux::toast($msg, variant: $imported > 0 ? 'success' : 'warning');
    }

    protected function rules(): array
    {
        return [
            'parser_type' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $type = SourceParserType::tryFrom($value);
                    if ($type === null) {
                        $fail(__('The selected parser type is invalid.'));

                        return;
                    }
                    if (! $type->isSupported()) {
                        $fail(__('The :format format is not yet supported.', ['format' => $type->label()]));
                    }
                },
            ],
            'parser_config.row_selector' => [
                'required_if:parser_type,'.SourceParserType::HtmlTable->value,
                'string',
            ],
            'parser_config.address_col' => [
                'required_if:parser_type,'.SourceParserType::HtmlTable->value,
                'integer',
                'min:0',
            ],
            'parser_config.port_col' => [
                'required_if:parser_type,'.SourceParserType::HtmlTable->value,
                'integer',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'parser_type.required' => __('Please select a parser type, or use the Detect button.'),
            'parser_config.row_selector.required_if' => __('Row selector is required for HTML table sources.'),
            'parser_config.address_col.required_if' => __('Address column index is required for HTML table sources.'),
            'parser_config.port_col.required_if' => __('Port column index is required for HTML table sources.'),
        ];
    }

    public function render(): View
    {
        return view('livewire.sources-index', [
            'sources' => Source::latest('last_scraped_at')->paginate(20),
            'productionMode' => $this->isProduction(),
        ]);
    }

    private function isProduction(): bool
    {
        return config('app.production_mode', false);
    }
}
