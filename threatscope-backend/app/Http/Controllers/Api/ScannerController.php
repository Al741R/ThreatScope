<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class ScannerController extends Controller
{
    private const CIRCL_BASE_URL = 'https://vulnerability.circl.lu/api/search';

    /**
     * Scan a saved asset from the local database against CIRCL.
     * GET /api/assets/{asset}/scan
     */
    public function scanAsset(Asset $asset): JsonResponse
    {
        return $this->fetchVulnerabilities(
            $asset->vendor,
            $asset->product,
            $asset
        );
    }

    /**
     * Ad-hoc lookup without saving to the database first.
     * GET /api/scan?vendor=php&product=php
     */
    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor' => ['required', 'string', 'max:100'],
            'product' => ['required', 'string', 'max:100'],
        ]);

        return $this->fetchVulnerabilities(
            strtolower(trim($validated['vendor'])),
            strtolower(trim($validated['product']))
        );
    }

    private function fetchVulnerabilities(string $vendor, string $product, ?Asset $asset = null): JsonResponse
    {
        $endpoint = sprintf(
            '%s/%s/%s',
            self::CIRCL_BASE_URL,
            rawurlencode($vendor),
            rawurlencode($product)
        );

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->get($endpoint);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to reach CIRCL Vulnerability-Lookup API.',
                'error' => $e->getMessage(),
            ], 502);
        }

        if ($response->failed()) {
            return response()->json([
                'message' => 'CIRCL API returned an error.',
                'status' => $response->status(),
                'endpoint' => $endpoint,
            ], 502);
        }

        $payload = $response->json();
        $normalized = $this->normalizeResults(is_array($payload) ? $payload : []);

        // Sort highest CVSS score first, then newest published date
        usort($normalized, function (array $a, array $b) {
            $scoreCmp = ($b['cvss_score'] ?? -1) <=> ($a['cvss_score'] ?? -1);
            if ($scoreCmp !== 0) {
                return $scoreCmp;
            }
            return strcmp($b['published'] ?? '', $a['published'] ?? '');
        });

        $summary = [
            'total' => count($normalized),
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'unknown' => 0,
        ];

        foreach ($normalized as $vuln) {
            $key = strtolower($vuln['severity']);
            if (isset($summary[$key])) {
                $summary[$key]++;
            }
        }

        return response()->json([
            'asset' => $asset,
            'query' => [
                'vendor' => $vendor,
                'product' => $product,
                'endpoint' => $endpoint,
            ],
            'summary' => $summary,
            'vulnerabilities' => array_slice($normalized, 0, 25),
        ], 200);
    }

    private function normalizeResults(array $payload): array
    {
        $rawResults = $payload['results'] ?? $payload;

        if (!is_array($rawResults) || empty($rawResults)) {
            return [];
        }

        // Handle both grouped sources ({ cvelistv5: [...], nvd: [...] }) and flat arrays
        $grouped = Arr::isAssoc($rawResults)
            ? $rawResults
            : ['circl' => $rawResults];

        $deduped = [];

        foreach ($grouped as $source => $entries) {
            if (!is_array($entries)) {
                continue;
            }

            foreach ($entries as $entry) {
                $fallbackId = null;
                $record = $entry;

                // CIRCL often returns entries as ["cve-2024-xxxx", { ...record... }]
                if (is_array($entry) && isset($entry[0], $entry[1]) && is_string($entry[0]) && is_array($entry[1])) {
                    $fallbackId = strtoupper($entry[0]);
                    $record = $entry[1];
                }

                if (!is_array($record)) {
                    continue;
                }

                $cveId = strtoupper((string) (
                    data_get($record, 'cveMetadata.cveId')
                    ?? data_get($record, 'id')
                    ?? data_get($record, 'cve.id')
                    ?? $fallbackId
                    ?? ''
                ));

                if ($cveId === '') {
                    continue;
                }

                $score = $this->extractCvssScore($record);
                $description = $this->extractDescription($record);
                $published = $this->extractPublishedDate($record);

                $parsed = [
                    'cve_id' => $cveId,
                    'cvss_score' => $score,
                    'severity' => $this->classifySeverity($score),
                    'summary' => $description,
                    'published' => $published,
                    'source' => (string) $source,
                    'url' => 'https://vulnerability.circl.lu/vuln/' . rawurlencode($cveId),
                ];

                // Keep the richer record when multiple feeds report the same CVE
                if (!isset($deduped[$cveId])) {
                    $deduped[$cveId] = $parsed;
                } else {
                    if ($deduped[$cveId]['cvss_score'] === null && $parsed['cvss_score'] !== null) {
                        $deduped[$cveId]['cvss_score'] = $parsed['cvss_score'];
                        $deduped[$cveId]['severity'] = $parsed['severity'];
                    }
                    if ($deduped[$cveId]['summary'] === 'No description available.' && $parsed['summary'] !== 'No description available.') {
                        $deduped[$cveId]['summary'] = $parsed['summary'];
                    }
                    if ($deduped[$cveId]['published'] === null && $parsed['published'] !== null) {
                        $deduped[$cveId]['published'] = $parsed['published'];
                    }
                }
            }
        }

        return array_values($deduped);
    }

    private function extractCvssScore(array $record): ?float
    {
        // 1. CVE 5.x format (containers.cna.metrics & containers.adp[].metrics)
        $metricGroups = [];
        $cnaMetrics = data_get($record, 'containers.cna.metrics');
        if (is_array($cnaMetrics)) {
            $metricGroups = array_merge($metricGroups, $cnaMetrics);
        }

        $adpList = data_get($record, 'containers.adp');
        if (is_array($adpList)) {
            foreach ($adpList as $adp) {
                if (isset($adp['metrics']) && is_array($adp['metrics'])) {
                    $metricGroups = array_merge($metricGroups, $adp['metrics']);
                }
            }
        }

        foreach ($metricGroups as $metric) {
            foreach (['cvssV4_0', 'cvssV3_1', 'cvssV3_0', 'cvssV2_0'] as $key) {
                $val = data_get($metric, "{$key}.baseScore");
                if (is_numeric($val)) {
                    return round((float) $val, 1);
                }
            }
        }

        // 2. NVD 2.0 format
        foreach ([
            'metrics.cvssMetricV40.0.cvssData.baseScore',
            'metrics.cvssMetricV31.0.cvssData.baseScore',
            'metrics.cvssMetricV30.0.cvssData.baseScore',
            'metrics.cvssMetricV2.0.cvssData.baseScore',
        ] as $path) {
            $val = data_get($record, $path);
            if (is_numeric($val)) {
                return round((float) $val, 1);
            }
        }

        // 3. Flat / legacy format
        foreach (['cvss3', 'cvss', 'cvss_score'] as $field) {
            if (isset($record[$field]) && is_numeric($record[$field])) {
                return round((float) $record[$field], 1);
            }
        }

        return null;
    }

    private function extractDescription(array $record): string
    {
        $candidates = [
            data_get($record, 'containers.cna.descriptions'),
            data_get($record, 'descriptions'),
        ];

        foreach ($candidates as $list) {
            if (is_array($list)) {
                foreach ($list as $desc) {
                    if (is_array($desc) && str_starts_with(strtolower((string) ($desc['lang'] ?? '')), 'en') && !empty($desc['value'])) {
                        return trim((string) $desc['value']);
                    }
                }
                if (!empty($list[0]['value'])) {
                    return trim((string) $list[0]['value']);
                }
            }
        }

        foreach (['containers.cna.title', 'summary', 'details', 'description'] as $path) {
            $val = data_get($record, $path);
            if (is_string($val) && trim($val) !== '') {
                return trim($val);
            }
        }

        return 'No description available.';
    }

    private function extractPublishedDate(array $record): ?string
    {
        foreach ([
            'cveMetadata.datePublished',
            'published',
            'Published',
            'published_at',
        ] as $path) {
            $val = data_get($record, $path);
            if (is_string($val) && strlen($val) >= 10) {
                return substr($val, 0, 10);
            }
        }

        return null;
    }

    private function classifySeverity(?float $score): string
    {
        if ($score === null) {
            return 'Unknown';
        }
        if ($score >= 9.0) {
            return 'Critical';
        }
        if ($score >= 7.0) {
            return 'High';
        }
        if ($score >= 4.0) {
            return 'Medium';
        }
        return 'Low';
    }
}
