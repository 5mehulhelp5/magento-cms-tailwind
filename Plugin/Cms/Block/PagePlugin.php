<?php
namespace BronzeByte\Tailwind\Plugin\Cms\Block;

use BronzeByte\Tailwind\Model\Builder;
use Psr\Log\LoggerInterface;

class PagePlugin
{
    protected $builder;
    protected $logger;

    public function __construct(
        Builder $builder,
        LoggerInterface $logger 
    ) {
        $this->builder = $builder;
        $this->logger = $logger; 
    }

    public function aroundToHtml(
        \Magento\Cms\Block\Page $subject,
        \Closure $proceed
    ) {

    	try {
        $this->logger->info('PagePlugin: aroundToHtml called.');
        $originalHtml = $proceed();
        $themeDetails = $this->builder->buildcss($originalHtml);   
        $styledHtml = '<style>' . $themeDetails . '</style>' . $originalHtml;
        return $styledHtml;
        }
        catch (\Exception $e) {
            $this->logger->error('Error during Tailwind build: ' . $e->getMessage());
        }
    }
}