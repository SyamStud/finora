<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Carbon;

/** Parser teks notifikasi bahasa Indonesia menjadi calon transaksi. */
final class NotificationParser
{
    /**
     * @return array{
     *     type: string, amount: float|null, description: string|null,
     *     account_id: string|null, account_name: string|null,
     *     category_id: string|null, category_name: string|null,
     *     transaction_date: string, confidence: float
     * }
     */
    public function parse(User $user, string $rawText, ?Carbon $receivedAt = null): array
    {
        $text = $this->normalize($rawText);
        $amount = $this->extractAmount($text);
        $type = $this->extractType($text);

        $account = $this->matchAccount($user, $text);
        $category = $this->matchCategory($user, $text);
        $date = $this->extractDate($text, $receivedAt);

        $confidence = 0.40;
        if ($amount !== null) {
            $confidence += 0.25;
        }
        if ($account !== null) {
            $confidence += 0.15;
        }
        if ($category !== null) {
            $confidence += 0.10;
        }
        if ($type !== null) {
            $confidence += 0.10;
        }

        return [
            'type' => $type ?? 'expense',
            'amount' => $amount,
            'description' => $this->extractDescription($text),
            'account_id' => $account?->id,
            'account_name' => $account?->name,
            'category_id' => $category?->id,
            'category_name' => $category?->name,
            'transaction_date' => $date->toDateTimeString(),
            'confidence' => round(min(1, $confidence), 2),
        ];
    }

    private function normalize(string $text): string
    {
        $text = str_replace(["\u{00a0}", "\r", "\n"], ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim(mb_strtolower($text));
    }

    private function extractAmount(string $text): ?float
    {
        if (preg_match('/(\d[\d.,]*)\s*(miliar|mlyar)\b/u', $text, $m)) {
            return $this->number($m[1]) * 1_000_000_000;
        }
        if (preg_match('/(\d[\d.,]*)\s*(juta|jt|jut)\b/u', $text, $m)) {
            return $this->number($m[1]) * 1_000_000;
        }
        if (preg_match('/(\d[\d.,]*)\s*(ribu|rb|rbu)\b/u', $text, $m)) {
            return $this->number($m[1]) * 1_000;
        }
        if (preg_match('/(?:rp|idr)\s*([\d.,]+)/u', $text, $m)) {
            return $this->number($m[1]);
        }
        if (preg_match('/\b(\d{1,3}(?:[.,]\d{3})+)\b/u', $text, $m)) {
            return $this->number($m[1]);
        }

        return null;
    }

    private function number(string $value): float
    {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }

    private function extractType(string $text): ?string
    {
        if (str_contains($text, 'transfer') || str_contains($text, 'kirim') || str_contains($text, 'pindah')) {
            return 'expense';
        }

        foreach (['gaji', 'cashback', 'refund', 'bunga', 'dapat', 'diterima', 'masuk', 'salary', 'pemasukan'] as $word) {
            if (str_contains($text, $word)) {
                return 'income';
            }
        }

        foreach (['beli', 'bayar', 'makan', 'kopi', 'belanja', 'tagihan', 'top up', 'pengeluaran'] as $word) {
            if (str_contains($text, $word)) {
                return 'expense';
            }
        }

        return null;
    }

    private function matchAccount(User $user, string $text): ?Account
    {
        /** @var Account|null $best */
        $best = null;
        $score = 0;

        foreach ($user->accounts as $account) {
            $name = mb_strtolower($account->name);
            if ($name !== '' && str_contains($text, $name)) {
                return $account;
            }

            foreach (explode(' ', $name) as $part) {
                if (strlen($part) >= 3 && str_contains($text, $part) && strlen($part) > $score) {
                    $best = $account;
                    $score = strlen($part);
                }
            }
        }

        return $best;
    }

    private function matchCategory(User $user, string $text): ?Category
    {
        foreach ($user->categories as $category) {
            $name = mb_strtolower($category->name);
            if ($name !== '' && str_contains($text, $name)) {
                return $category;
            }

            $first = explode(' ', $name)[0] ?? '';
            if (strlen($first) >= 4 && str_contains($text, $first)) {
                return $category;
            }
        }

        return null;
    }

    private function extractDate(string $text, ?Carbon $receivedAt): Carbon
    {
        if (str_contains($text, 'kemarin')) {
            return now()->subDay();
        }
        if (str_contains($text, 'hari ini') || str_contains($text, 'today')) {
            return now();
        }

        return $receivedAt?->copy() ?? now();
    }

    private function extractDescription(string $text): string
    {
        $clean = preg_replace('/(rp|idr)?\s*[\d.,]+\s*(ribu|rb|rbu|juta|jt|jut|miliar)?/u', '', $text) ?? $text;
        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? $clean);

        return mb_substr($clean !== '' ? $clean : 'Transaksi dari notifikasi', 0, 255);
    }
}
