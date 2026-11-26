<?php

namespace App\Services;

use App\Models\PendingTransaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class NotificationIngestService
{
    public function __construct(private readonly NotificationParser $parser) {}

    /** @param array<string, mixed> $data */
    public function ingest(User $user, array $data): PendingTransaction
    {
        $raw = trim((string) $data['raw_text']);
        if ($raw === '') {
            throw ValidationException::withMessages(['raw_text' => 'Teks notifikasi kosong.']);
        }

        $receivedAt = isset($data['received_at'])
            ? Carbon::parse($data['received_at'])
            : now();
        $package = $data['package_name'] ?? null;

        // Idempoten: notifikasi yang sama (user + teks + hari) tidak dibuat dua kali.
        $fingerprint = hash('sha256', implode('|', [
            $user->id,
            $package ?? '',
            $raw,
            $receivedAt->toDateString(),
        ]));

        /** @var PendingTransaction|null $existing */
        $existing = PendingTransaction::query()
            ->where('fingerprint', $fingerprint)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $parsed = $this->parser->parse($user, $raw, $receivedAt);

        return DB::transaction(fn (): PendingTransaction => PendingTransaction::query()->create(array_merge($parsed, [
            'user_id' => $user->id,
            'source' => $data['source'] ?? 'notification',
            'package_name' => $package,
            'raw_text' => $raw,
            'fingerprint' => $fingerprint,
            'status' => 'pending',
            'received_at' => $receivedAt,
        ])));
    }
}
