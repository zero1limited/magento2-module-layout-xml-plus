<?php

declare(strict_types=1);

namespace Zero1\LayoutXmlPlus\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @see https://developer.adobe.com/commerce/php/development/cli-commands/custom/
 */ 
class StatusCommand extends Command
{
    private const INPUT_ARGUMENT_ENABLE = 'enable';

    private const INPUT_ARGUMENT_DISABLE = 'disable';

    /** @var \Magento\Framework\App\Cache\Manager */
    protected $cacheManager;

    /** @var \Zero1\LayoutXmlPlus\Model\Config */
    protected $config;

    public function __construct(
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Zero1\LayoutXmlPlus\Model\Config $config
    )
    {
        $this->cacheManager = $cacheManager;
        $this->config = $config;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('dev:layout-xml-plus:status');
        $this->setDescription('Show/Update the current status');
        
        $this->addOption(
            self::INPUT_ARGUMENT_ENABLE,
            null,
            InputOption::VALUE_NONE,
            'Enable'
        );

        $this->addOption(
            self::INPUT_ARGUMENT_DISABLE,
            null,
            InputOption::VALUE_NONE,
            'Disable'
        );
        parent::configure();
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $status = $this->config->getStatus();

        $enable = $input->getOption(self::INPUT_ARGUMENT_ENABLE);
        $disable = $input->getOption(self::INPUT_ARGUMENT_DISABLE);

        if(!$status && $enable){
            $output->writeln('enabling module');
            $this->config->setStatus(true);
        }
        
        if($status && $disable){
            $output->writeln('disabling module');
            $this->config->setStatus(false);
        }

        if(
            (!$status && $enable)
            || ($status && $disable)
            ){
            $this->cacheManager->flush($this->cacheManager->getAvailableTypes());
            $output->writeln('cache cleared');
        }
        
        if(!$enable && !$disable){
            $output->writeln('Status: '.($status? 'enabled' : 'disabled'));
        }
        
        return 0;
    }
}
