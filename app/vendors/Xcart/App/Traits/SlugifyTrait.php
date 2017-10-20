<?php

namespace Xcart\App\Traits;

use Cocur\Slugify\Slugify;

/**
 * Class UniqueUrl.
 */
trait SlugifyTrait
{
    protected $slugify;

    /**
     * @param $source
     *
     * @return string
     */
    protected function createSlug($source)
    {
        if ($this->slugify === null) {
            $this->slugify = new Slugify();
        }

        return $this->slugify->slugify($source);
    }

    /**
     * @param $url
     * @param int  $count
     * @param null $pk
     *
     * @return string
     */
    public function uniqueUrl($url, $count = 0, $pk = null)
    {
        /* @var $model \Xcart\App\Orm\Model */
        $model = $this->getModel();
        $newUrl = $url;
        if ($count) {
            $newUrl .= '-'.$count;
        }

        $qs = call_user_func([$model, 'objects'])
            ->filter([$this->getName() => $newUrl]);
        if ($pk) {
            $qs = $qs->exclude(['pk' => $pk]);
        }
        if ($qs->count() > 0) {
            ++$count;

            return $this->uniqueUrl($url, $count, $pk);
        }

        return $newUrl;
    }
}
