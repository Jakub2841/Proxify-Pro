<?php

namespace App\Http\Controllers;

use App\Actions\Export\ExportProxies;
use App\Models\Proxy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportProxiesController extends Controller
{
    public function __invoke(Request $request, string $format): StreamedResponse
    {
        $query = Proxy::query()->filtered([
            'protocols' => $request->query('protocols', []),
            'anonymity' => $request->query('anonymity', []),
            'checks' => $request->query('checks', []),
            'country' => $request->query('country', ''),
            'active_only' => $request->boolean('active_only'),
            'search' => $request->query('search', ''),
        ]);

        $dataFormat = $request->query('data_format', 'ip_port');

        return match ($format) {
            'csv' => (new ExportProxies)->toCsv($query, $dataFormat),
            'txt' => (new ExportProxies)->toTxt($query, $dataFormat),
            'json' => (new ExportProxies)->toJson($query, $dataFormat),
            default => abort(404),
        };
    }
}
