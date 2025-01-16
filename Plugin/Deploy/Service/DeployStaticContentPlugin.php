<?php
namespace BronzeByte\Tailwind\Plugin\Deploy\Service;

use Magento\Deploy\Service\DeployStaticContent;
use BronzeByte\Tailwind\Model\Builder;
use Psr\Log\LoggerInterface;

class DeployStaticContentPlugin
{
    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var LoggerInterface
     */
    private $logger;

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
     * Before Deploy Plugin
     *
     * @param DeployStaticContent $subject
     * @param array $options
     * @return void
     */
    public function beforeDeploy(DeployStaticContent $subject, array $options)
    {
        try {
            $this->logger->info('Building Tailwind CSS before deployment.');
            $themeDetails = $this->builder->build(); 
            $this->logger->debug('Theme Details: ' . print_r($themeDetails, true));
        } catch (\Exception $e) {
            $this->logger->error('Error during Tailwind build: ' . $e->getMessage());
        }
    }
}