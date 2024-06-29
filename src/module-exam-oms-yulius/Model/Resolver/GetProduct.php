<?php

namespace Icube\ExamOmsYulius\Model\Resolver;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class GetProduct implements ResolverInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory $collectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        CollectionFactory $collectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->productRepository = $productRepository;
        $this->collectionFactory = $collectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $search = $args['search'] ?? '';
        $filter = $args['filter'] ?? [];
        $sort = $args['sort'] ?? [];
        $pageSize = $args['pageSize'] ?? 5;
        $currentPage = $args['currentPage'] ?? 1;

        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('*');

        if ($search) {
            $collection->addAttributeToFilter(
                [
                    ['attribute' => 'name', 'like' => '%' . $search . '%'],
                    ['attribute' => 'sku', 'like' => '%' . $search . '%']
                ]
            );
        }

        foreach ($filter as $field => $condition) {
            $collection->addAttributeToFilter($field, $condition);
        }

        foreach ($sort as $field => $direction) {
            $collection->addOrder($field, $direction);
        }

        $collection->setPageSize($pageSize);
        $collection->setCurPage($currentPage);

        $items = [];
        foreach ($collection as $product) {
            $items[] = [
                'entity_id' => $product->getId(),
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'status' => $product->getStatus(),
                'description' => $product->getDescription(),
                'short_description' => $product->getShortDescription(),
                'weight' => $product->getWeight(),
                'dimension_package_height' => $product->getData('dimension_package_height'),
                'dimension_package_length' => $product->getData('dimension_package_length'),
                'dimension_package_width' => $product->getData('dimension_package_width'),
            ];
        }

        return [
            'items' => $items,
            'page_info' => [
                'current_page' => $currentPage,
                'page_size' => $pageSize,
                'total_pages' => ceil($collection->getSize() / $pageSize),
            ],
            'total_count' => $collection->getSize(),
        ];
    }
}
