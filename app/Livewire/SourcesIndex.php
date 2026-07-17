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
use Livewire\WithPagination;

#[Layout('layouts.app')]
class SourcesIndex extends Component
{
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
        Source::where('id', $id)->update(['is_enabled' => DB::raw('NOT is_enabled')]);
    }

    public function addNew(): void
    {
        $this->reset(['showModal', 'editingId', 'name', 'url', 'parser_type', 'parser_config', 'default_protocol', 'isDetecting', 'detectionError', 'detectionNotice']);
        $this->showModal = true;
    }

    public function cancel(): void
    {
        $this->reset(['showModal', 'editingId', 'name', 'url', 'parser_type', 'parser_config', 'default_protocol', 'isDetecting', 'detectionError', 'detectionNotice']);
    }

    public function edit(int $id): void
    {
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
        Source::findOrFail($id)->delete();
    }

    public function updatedAllEnabled(bool $value): void
    {
        Source::query()->update(['is_enabled' => $value]);
    }

    public bool $showClearModal = false;

    public function clearAll(): void
    {
        Source::query()->delete();

        $this->showClearModal = false;
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
        ]);
    }
}
