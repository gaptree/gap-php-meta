<?php
namespace Gap\Meta;

use Redis;
use Gap\Db\MySql\Cnn;

class Meta
{
    protected $cnn;
    protected $cache;

    protected $table = 'gap_meta';
    protected $defaultLocaleKey = 'zh-cn';

    public function __construct(Cnn $cnn, Redis $cache)
    {
        $this->cnn = $cnn;
        $this->cache = $cache;
    }

    public function setDefaultLocaleKey($localeKey)
    {
        $this->defaultLocaleKey = $localeKey;
    }

    public function get($str, $vars = [], $localeKey = '')
    {
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
    }

    public function delete($localeKey, $metaKey)
    {
        $this->cache->hDel($localeKey, $metaKey);
        $this->cnn->delete()
            ->from($this->table)
            ->where()
                ->expect('key')->beStr($metaKey)
                ->andExpect('localeKey')->beStr($localeKey)
            ->execute();
    }

    protected function lget($metaKey, $localeKey)
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
        $this->set($localeKey, $metaKey, $metaValue);
        return $metaValue;
    }

    public function set($localeKey, $metaKey, $metaValue)
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

    protected function findFromDb($localeKey, $metaKey)
    {
        $obj = $this->cnn->select('value')
            ->from($this->table)
            ->where()
                ->expect('localeKey')->beStr($localeKey)
                ->andExpect('key')->beStr($metaKey)
            ->fetchObj();

        if (!$obj) {
            return null;
        }

        return $obj->value;
    }

    protected function saveToDb($localeKey, $metaKey, $metaValue)
    {
        if ($this->findFromDb($localeKey, $metaKey)) {
            $this->cnn->update($this->table)
                ->set('value')->beStr($metaValue)
                ->where()
                    ->expect('localeKey')->beStr($localeKey)
                    ->andExpect('key')->beStr($metaKey)
                ->execute();

            return;
        }

        $this->cnn->insert($this->table)
            ->field('metaId', 'localeKey', 'key', 'value')
            ->value()
                ->addStr($this->cnn->zid())
                ->addStr($localeKey)
                ->addStr($metaKey)
                ->addStr($metaValue)
            ->execute();
    }
}
