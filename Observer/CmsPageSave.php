<?php
/**
 * Copyright © 2019 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Magenest_CacheWarmer extension
 * NOTICE OF LICENSE
 *
 * @category Magenest
 * @package Magenest_CacheWarmer
 */

namespace Magenest\CacheWarmer\Observer;

use Magenest\CacheWarmer\Helper\Config;
use Magenest\CacheWarmer\Model\Queue;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;
use Magenest\CacheWarmer\Logger\Logger;

class CmsPageSave implements ObserverInterface
{
    protected $urlCollection;
    protected $queue;
    protected $config;
    protected $logger;

    public function __construct(
        UrlRewriteCollectionFactory $urlRewriteCollectionFactory,
        Queue $queue,
        Config $config,
        Logger $logger
    )
    {
        $this->urlCollection = $urlRewriteCollectionFactory;
        $this->queue = $queue;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        if (!$this->config->isModuleEnabled()) {
            return;
        }
        if (!$this->config->isGenerateProductSaveEnabled() && !$this->config->isHitProductSaveEnabled()) {
            return;
        }
        try {
            $pageId = $observer->getObject()->getPageId();
            $baseUrl = $this->queue->getBaseUrl();
            $urlCollection = $this->urlCollection->create()->addFieldToFilter('entity_type', 'cms-page')->addFieldToFilter('entity_id', $pageId)->getData();
            foreach ($urlCollection as $url) {
                $item = $baseUrl . $url['request_path'];
                if ($this->config->isGenerateProductSaveEnabled()) {
                    $this->queue->customEnqueue($item);
                }
                if ($this->config->isHitProductSaveEnabled()) {
                    $this->queue->customDequeue($item);
                }
            }
        } catch (\Exception $e) {
            $this->logger->info(__($e->getMessage()));
        }
    }
}