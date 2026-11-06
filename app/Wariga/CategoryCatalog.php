<?php

declare(strict_types=1);

namespace App\Wariga;

use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Facades\DB;

/**
 * Daftar `activity_categories` (kecil & statis), dibaca SEKALI per request/job (scoped binding).
 * Dipakai prompt agent, deklarasi tool, ActivityMatcher, DateRanker, dan OutputGuard
 * agar tidak mengulang query yang sama (tiap query ke Supabase ±400 ms dari jaringan lambat).
 */
#[Scoped]
final class CategoryCatalog
{
    /** @var array<string, array{id: int, code: string, name: string, domain: string, parent_id: ?int, requires_cultural_notice: bool}>|null */
    private ?array $byCode = null;

    /**
     * @return array<string, array{id: int, code: string, name: string, domain: string, parent_id: ?int, requires_cultural_notice: bool}> kode → kategori, urut kode
     */
    public function all(): array
    {
        if ($this->byCode !== null) {
            return $this->byCode;
        }

        $this->byCode = [];
        foreach (DB::table('activity_categories')->orderBy('code')->get(['id', 'code', 'name', 'domain', 'parent_id', 'requires_cultural_notice']) as $c) {
            $this->byCode[(string) $c->code] = [
                'id' => (int) $c->id,
                'code' => (string) $c->code,
                'name' => (string) $c->name,
                'domain' => (string) $c->domain,
                'parent_id' => $c->parent_id === null ? null : (int) $c->parent_id,
                'requires_cultural_notice' => (bool) $c->requires_cultural_notice,
            ];
        }

        return $this->byCode;
    }

    /**
     * @return array{id: int, code: string, name: string, domain: string, parent_id: ?int, requires_cultural_notice: bool}|null
     */
    public function get(string $code): ?array
    {
        return $this->all()[$code] ?? null;
    }

    /**
     * @return array{id: int, code: string, name: string, domain: string, parent_id: ?int, requires_cultural_notice: bool}|null
     */
    public function byId(int $id): ?array
    {
        foreach ($this->all() as $c) {
            if ($c['id'] === $id) {
                return $c;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return array_keys($this->all());
    }

    /**
     * Id kategori beserta seluruh induknya (aturan "pertanian" berlaku juga untuk "pertanian.umbi").
     *
     * @return list<int>
     */
    public function idsWithAncestors(string $code): array
    {
        $ids = [];
        $c = $this->get($code);
        while ($c !== null) {
            $ids[] = $c['id'];
            $c = $c['parent_id'] === null ? null : $this->byId($c['parent_id']);
        }

        return $ids;
    }

    public function flush(): void
    {
        $this->byCode = null;
    }
}
