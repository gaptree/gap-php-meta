<?php
namespace Gap\Meta\Repo;

use Gap\Db\MySql\Cnn;

class MetaRepo implements MetaRepoInterface
{
    protected $cnn;

    protected $table = 'gap_meta';


    public function __construct(Cnn $cnn)
    {
        $this->cnn = $cnn;
    }

    public function save(string $localeKey, string $metaKey, string $metaValue): void
    {
        if ($this->fetchMetaValue($localeKey, $metaKey)) {
            $this->cnn->update($this->table)
                ->set('`value`')->beStr($metaValue)
                ->where()
                    ->expect('localeKey')->beStr($localeKey)
                    ->andExpect('`key`')->beStr($metaKey)
                ->execute();

            return;
        }

        $this->cnn->insert($this->table)
            ->field('metaId', 'localeKey', '`key`', '`value`')
            ->value()
                ->addStr($this->cnn->zid())
                ->addStr($localeKey)
                ->addStr($metaKey)
                ->addStr($metaValue)
            ->execute();
    }

    public function delete(string $localeKey, string $metaKey): void
    {
        $this->cnn->delete()
            ->from($this->table)
            ->where()
                ->expect('`key`')->beStr($metaKey)
                ->andExpect('localeKey')->beStr($localeKey)
            ->execute();
    }

    public function fetchMetaValue(string $localeKey, string $metaKey): string
    {
        $metaArr = $this->cnn->select('`value`')
            ->from($this->table)
            ->where()
                ->expect('localeKey')->beStr($localeKey)
                ->andExpect('`key`')->beStr($metaKey)
            ->limit(1)
            ->execute()
            ->fetchAssoc();


        if (!$metaArr) {
            return '';
        }

        return $metaArr['`value`'];
    }
}
