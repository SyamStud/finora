<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** NAV Sucorinvest Money Market Fund dari halaman publik Bibit. */
final class FundPriceService
{
    /**
     * @return array{name: string, code: string, nav: float, date: string, source: string, cached: bool}
     */
    public function currentNav(): array
    {
        $code = (string) config('funds.rdpu.code', 'RD1657');
        $cacheKey = 'fund:nav:'.$code;
        $cached = Cache::has($cacheKey);

        /** @var array{name: string, code: string, nav: float, date: string, source: string} $data */
        $data = Cache::remember($cacheKey, (int) config('funds.rdpu.cache_seconds', 3600), function (): array {
            return $this->fetch();
        });

        return array_merge($data, ['cached' => $cached]);
    }

    /** @return array{name: string, code: string, nav: float, date: string, source: string} */
    private function fetch(): array
    {
        try {
            $response = Http::timeout((int) config('funds.rdpu.timeout_seconds', 15))
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Finora/1.0)'])
                ->get((string) config('funds.rdpu.url'));
        } catch (RequestException $exception) {
            throw new RuntimeException('Gagal menghubungi sumber harga reksa dana.', 0, $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Sumber harga reksa dana tidak merespons (HTTP '.$response->status().').');
        }

        $body = $response->body();

        if (! preg_match('#<script id="__NEXT_DATA__" type="application/json">(.*?)</script>#s', $body, $matches)) {
            throw new RuntimeException('Data NAV tidak ditemukan di sumber harga.');
        }

        /** @var array<string, mixed>|null $payload */
        $payload = json_decode(html_entity_decode($matches[1]), true);

        /** @var array{date?: string, value?: int|float}|null $nav */
        $nav = $payload['props']['pageProps']['productDetail']['nav'] ?? null;

        if ($nav === null || ! isset($nav['value'], $nav['date'])) {
            throw new RuntimeException('Format data NAV tidak dikenali.');
        }

        return [
            'name' => (string) data_get($payload, 'props.pageProps.productDetail.name', config('funds.rdpu.name')),
            'code' => (string) config('funds.rdpu.code', 'RD1657'),
            'nav' => round((float) $nav['value'], 4),
            'date' => (string) $nav['date'],
            'source' => 'Bibit ('.$nav['date'].')',
        ];
    }
}
