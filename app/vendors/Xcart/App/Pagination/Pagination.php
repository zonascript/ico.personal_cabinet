<?php

namespace Xcart\App\Pagination;

use Xcart\App\Pagination\DataSource\DataSourceInterface;
use Xcart\App\Pagination\Handler\NativePaginationHandler;
use Xcart\App\Traits\RenderTrait;

/**
 * Class Pagination
 * @package Xcart\App\Pagination
 */
class Pagination extends BasePagination
{
    use RenderTrait;
    
    public $view = "core/pager/pager.tpl";

    /**
     * Pagination constructor.
     *
     * @param $source
     * @param array $config
     * @param DataSourceInterface $dataSource
     */
    public function __construct($source, array $config = [], $dataSource)
    {
        if (!empty($config['view'])) {
            $this->view = $config['view'];
        }
        
        $handler = new NativePaginationHandler();
        parent::__construct($source, $config, $handler, $dataSource);
    }

    public function __toString()
    {
        return (string)$this->render();
    }

    public function toJson()
    {
        return [
            'objects' => $this->data,
            'meta' => [
                'total' => (int)$this->getTotal(),
                'pages_count' => $this->getPagesCount(),
                'page' => $this->getPage(),
                'page_size' => $this->getPageSize(),
            ]
        ];
    }

    public function render($view = null)
    {
        if (!$view) {
            $view = $this->view;
        }

        return $this->renderTemplate($view, [
            'this' => $this,
            'pager' => $this,
            'view' => $this->createView()
        ]);
    }
}
