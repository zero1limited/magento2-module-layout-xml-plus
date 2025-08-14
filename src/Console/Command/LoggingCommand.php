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
class LoggingCommand extends Command
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
        $this->setName('dev:layout-xml-plus:logging');
        $this->setDescription('Show/Update the current logging status');
        
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
        $status = $this->config->getLoggingStatus();

        $enable = $input->getOption(self::INPUT_ARGUMENT_ENABLE);
        $disable = $input->getOption(self::INPUT_ARGUMENT_DISABLE);

        if(!$status && $enable){
            $output->writeln('enabling logging');
            $this->config->setLoggingStatus(true);
        }
        
        if($status && $disable){
            $output->writeln('disabling logging');
            $this->config->setLoggingStatus(false);
        }

        if(
            (!$status && $enable)
            || ($status && $disable)
            ){
            $this->cacheManager->flush($this->cacheManager->getAvailableTypes());
            $output->writeln('cache cleared');
        }
        
        if(!$enable && !$disable){
            $output->writeln('Logging Status: '.($status? 'enabled' : 'disabled'));
        }
        
        return 0;
    }
}
