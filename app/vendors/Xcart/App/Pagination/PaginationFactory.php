<?php
namespace Xcart\App\Pagination;

use Xcart\App\Pagination\DataSource\DataSourceInterface;
use Xcart\App\Pagination\Handler\NativePaginationHandler;
use Xcart\App\Pagination\Handler\PaginationHandlerInterface;

/**
 * Class PaginationFactory
 * @package Xcart\App\Pagination
 */
class PaginationFactory
{
    /**
     * @var DataSourceInterface[]
     */
    protected $dataSources = [];

    /**
     * @param array|DataSourceInterface $source
     * @param array $parameters
     * @param PaginationHandlerInterface $handler
     * @return Pagination
     */
    public function createPagination($source, array $parameters = array(), PaginationHandlerInterface $handler)
    {
        $handler = $handler ?: new NativePaginationHandler();
        return new Pagination($source, $parameters, $handler, $this->findDataSource($source));
    }

    /**
     * @param $source
     * @return DataSourceInterface
     */
    protected function findDataSource($source)
    {
        foreach ($this->dataSources as $dataSource) {
            if ($dataSource->supports($source)) {
                return $dataSource;
            }
        }

        throw new \RuntimeException('Unknown source type');
    }

    /**
     * @param DataSourceInterface $dataSource
     */
    public function addDataSource(DataSourceInterface $dataSource)
    {
        $this->dataSources[] = $dataSource;
    }
}
