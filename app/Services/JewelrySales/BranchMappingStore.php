<?php

namespace App\Services\JewelrySales;

class BranchMappingStore
{
    public function path(): string
    {
        return (string) config(
            'jewelry_sales.branch_mapping_file',
            storage_path('app/jewelry_sales_branch_mappings.json')
        );
    }

    /**
     * @return array<string, int> Normalized lowercase alias => branch_id
     */
    public function all(): array
    {
        $path = $this->path();
        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            return [];
        }

        $mappings = [];
        foreach ($decoded as $alias => $branchId) {
            $key = $this->normalizeAlias((string) $alias);
            $id = (int) $branchId;
            if ($key !== '' && $id > 0) {
                $mappings[$key] = $id;
            }
        }

        return $mappings;
    }

    /**
     * @param array<int, array{alias: string, branch_id: int}> $entries
     */
    public function saveMappings(array $entries): void
    {
        $mappings = $this->all();

        foreach ($entries as $entry) {
            $key = $this->normalizeAlias((string) ($entry['alias'] ?? ''));
            $branchId = (int) ($entry['branch_id'] ?? 0);
            if ($key !== '' && $branchId > 0) {
                $mappings[$key] = $branchId;
            }
        }

        ksort($mappings);

        $path = $this->path();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $path,
            json_encode($mappings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL
        );
    }

    public function normalizeAlias(string $alias): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $alias) ?? ''));
    }
}
