<?php
namespace Gap\Meta;

use Redis;

class Meta
{
    protected $repo;
    protected $cache;

    protected $localeKey = 'zh-cn';

    public function __construct(Repo\MetaRepoInterface $repo, Redis $cache)
    {
        $this->repo = $repo;
        $this->cache = $cache;
    }

    public function setLocaleKey(string $localeKey): void
    {
        $this->localeKey = $localeKey;
    }

    public function localeGet(string $localeKey, string $key, string ...$vars): string
    {
        $this->setLocaleKey($localeKey);
        return $this->get($key, ...$vars);
    }

    public function localeSet(string $localeKey, string $key, string $value): void
    {
        $this->setLocaleKey($localeKey);
        $this->set($key, $value);
    }

    public function get(string $key, string ...$vars): string
    {
        if (!$vars) {
            return $this->getMetaValue($this->localeKey, $key);
        }

        //$key = $key . '-%$' . implode('$s-%$', array_keys($vars)) . '$s';
        $count = count($vars);
        for ($i = 1; $i <= $count; $i++) {
            $key .= '-%' . $i . '$s';
        }
        return sprintf($this->getMetaValue($this->localeKey, $key), ...$vars);

        /* todo delete lator
        if (!$localeKey) {
            $localeKey = $this->defaultLocaleKey;
        }

        if (!$vars) {
            return $this->lget($str, $localeKey);
        }

        if (is_string($vars)) {
            $vars = [$vars];
        }

        $index = 1;
        $args = [];
        $args[0] = '';

        foreach ($vars as $val) {
            $str .= "-%$index" . '$s';
            $args[$index] = $val;
            $index++;
        }
        $args[0] = $this->lget($str, $localeKey);

        return sprintf(...$args);
        */
    }

    public function set(string $key, string $value): void
    {
        $this->setMeta($this->localeKey, $key, $value);
    }

    public function delete($localeKey, $metaKey)
    {
        $this->cache->hDel($localeKey, $metaKey);
        $this->repo->delete($localeKey, $metaKey);
    }

    protected function findFromDb($localeKey, $metaKey): string
    {
        return $this->repo->fetchMetaValue($localeKey, $metaKey);
    }

    protected function saveToDb($localeKey, $metaKey, $metaValue): void
    {
        $this->repo->save($localeKey, $metaKey, $metaValue);
    }

    protected function getMetaValue($localeKey, $metaKey)
    {
        if (!$metaKey) {
            // todo
            throw new \Exception("cannot get empty metaKey");
        }

        if (!$localeKey) {
            // todo
            throw new \Exception("localeKey cannot be empty");
        }

        if ($metaValue = $this->cache->hGet($localeKey, $metaKey)) {
            return $metaValue;
        }

        if ($metaValue = $this->findFromDb($localeKey, $metaKey)) {
            $this->cache->hSet($localeKey, $metaKey, $metaValue);
            return $metaValue;
        }

        $metaValue = '#' . $metaKey;
        $this->setMeta($localeKey, $metaKey, $metaValue);
        return $metaValue;
    }

    protected function setMeta($localeKey, $metaKey, $metaValue)
    {
        if (!$metaKey) {
            // todo
            throw new \Exception("cannot empty metaKey");
        }

        if (!$metaValue) {
            // todo
            throw new \Exception("metaValue could not be empty");
        }

        if (!$localeKey) {
            // todo
            throw new \Exception("localeKey cannot be empty");
        }

        $this->cache->hSet($localeKey, $metaKey, $metaValue);
        $this->saveToDb($localeKey, $metaKey, $metaValue);
    }
}
