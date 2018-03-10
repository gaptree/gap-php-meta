<?php
namespace Gap\Meta\Repo;

interface MetaRepoInterface
{
    public function save(string $localeKey, string $metaKey, string $metaValue): void;
    public function delete(string $localeKey, string $metaKey): void;
    public function fetchMetaValue(string $localeKey, string $metaKey): string;
}
