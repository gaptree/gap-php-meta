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
        $cnn = $this->cnn;

        if ($this->fetchMetaValue($localeKey, $metaKey)) {
            $cnn->update($cnn->table($this->table))
                ->set('`value`', $cnn->str($metaValue))
                ->where(
                    $cnn->cond()
                        ->expect('localeKey')->equal($cnn->str($localeKey))
                        ->andExpect('`key`')->equal($cnn->str($metaKey))
                )
                ->execute();

            return;
        }

        $cnn->insert($this->table)
            ->field('metaId', 'localeKey', '`key`', '`value`')
            ->value(
                $cnn->value()
                    ->add($cnn->str($cnn->zid()))
                    ->add($cnn->str($localeKey))
                    ->add($cnn->str($metaKey))
                    ->add($cnn->str($metaValue))
            )
            ->execute();
    }

    public function delete(string $localeKey, string $metaKey): void
    {
        $cnn = $this->cnn;
        $this->cnn->delete()
            ->from($cnn->table($this->table))
            ->where(
                $cnn->cond()
                    ->expect('`key`')->equal($cnn->str($metaKey))
                    ->andExpect('localeKey')->equal($cnn->str($localeKey))
            )
            ->execute();
    }

    public function fetchMetaValue(string $localeKey, string $metaKey): string
    {
        $cnn = $this->cnn;
        $metaArr = $cnn->select('`value`')
            ->from($cnn->table($this->table))
            ->where(
                $cnn->cond()
                    ->expect('localeKey')->equal($cnn->str($localeKey))
                    ->andExpect('`key`')->equal($cnn->str($metaKey))
            )
            ->limit(1)
            ->fetchAssoc();


        if (!$metaArr) {
            return '';
        }

        return $metaArr['value'];
    }
}
