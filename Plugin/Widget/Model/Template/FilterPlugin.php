<?php

namespace BronzeByte\Tailwind\Plugin\Widget\Model\Template;

use BronzeByte\Tailwind\Model\Builder;
use Magento\Widget\Model\Template\Filter;
use Psr\Log\LoggerInterface;

class FilterPlugin
{
    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param Builder $builder
     * @param LoggerInterface $logger
     */
    public function __construct(
        Builder $builder,
        LoggerInterface $logger
    ) {
        $this->builder = $builder;
        $this->logger = $logger;
    }

    /**
     * Around Plugin for generateWidget
     *
     * @param Filter $subject
     * @param \Closure $proceed
     * @param array $construction
     * @return string
     */
    public function aroundGenerateWidget(Filter $subject, \Closure $proceed, $construction)
    {  
        try{
        $this->logger->info('WidgetTemplateFilterPlugin: aroundGenerateWidget called.');

        $originalHtml = $proceed($construction);

        if (!empty($originalHtml)) {
            $themeDetails = $this->builder->buildcss($originalHtml);
            $styledHtml = '<style>' . $themeDetails . '</style>' . $originalHtml;
            $this->logger->info('WidgetTemplateFilterPlugin: Tailwind CSS added to widget.');
            return $styledHtml;
        }
        return $originalHtml;
        } catch(\Exception $e){
        	 $this->logger->error('Error during Tailwind build: ' . $e->getMessage());
        }

     }    

}
