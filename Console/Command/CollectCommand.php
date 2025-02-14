<?php

declare(strict_types=1);

namespace Zero1\LayoutXmlPlus\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Zero1\LayoutXmlPlus\Model\Config\Source\CollectStatus;

/**
 * @see https://developer.adobe.com/commerce/php/development/cli-commands/custom/
 */ 
class CollectCommand extends Command
{
    private const INPUT_OPTION_WITH_THEME = 'with-theme';
    private const INPUT_OPTION_WITHOUT_THEME = 'without-theme';
    private const INPUT_OPTION_DISABLE = 'disable';
    private const INPUT_OPTION_CLEAR = 'clear';

    /** @var \Magento\Framework\App\Cache\Manager */
    protected $cacheManager;

    /** @var \Zero1\LayoutXmlPlus\Model\Config */
    protected $config;

    /** @var \Zero1\LayoutXmlPlus\Model\Collector */
    protected $collector;

    public function __construct(
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Zero1\LayoutXmlPlus\Model\Config $config,
        \Zero1\LayoutXmlPlus\Model\Collector $collector
    )
    {
        $this->cacheManager = $cacheManager;
        $this->config = $config;
        $this->collector = $collector;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('dev:layout-xml-plus:collect');
        $this->setDescription('enable/disable template output collection');
        
        $this->addOption(
            self::INPUT_OPTION_WITH_THEME,
            null,
            InputOption::VALUE_NONE,
            'With Theme'
        );
        $this->addOption(
            self::INPUT_OPTION_WITHOUT_THEME,
            null,
            InputOption::VALUE_NONE,
            'Without Theme'
        );
        $this->addOption(
            self::INPUT_OPTION_DISABLE,
            null,
            InputOption::VALUE_NONE,
            'Disable'
        );
        $this->addOption(
            self::INPUT_OPTION_CLEAR,
            null,
            InputOption::VALUE_NONE,
            'Clear'
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
        $withTheme = $input->getOption(self::INPUT_OPTION_WITH_THEME);
        $withoutTheme = $input->getOption(self::INPUT_OPTION_WITHOUT_THEME);
        $disable = $input->getOption(self::INPUT_OPTION_DISABLE);
        $clear = $input->getOption(self::INPUT_OPTION_CLEAR);

        if(!$withTheme && !$withoutTheme && !$disable){
            switch(true){
                case $this->config->isCollectStatusDisabled():
                    $output->writeln('Collect status: Disabled');
                    break;
                case $this->config->isCollectStatusWithoutTheme():
                    $output->writeln('Collect status: Without Theme');
                    break;
                case $this->config->isCollectStatusWithTheme():
                    $output->writeln('Collect status: With Theme');
                    break;
            }
        }

        if($withTheme){
            $output->writeln('enabling collect for theme');
            $this->config->setCollectStatusWithTheme();
        }elseif($withoutTheme){
            $output->writeln('enabled collect for non theme');
            $this->config->setCollectStatusWithoutTheme();
        }elseif($disable){
            $output->writeln('disabling collection');
            $this->config->setCollectStatusDisabled();
        }

        if($withTheme || $withoutTheme || $disable){
            $this->cacheManager->flush($this->cacheManager->getAvailableTypes());
            $output->writeln('cache cleared');
        }

        if($clear){
            switch(true){
                case $withTheme:
                    $status = CollectStatus::STATUS_WITH_THEME;
                    break;
                case $withoutTheme:
                    $status = CollectStatus::STATUS_WITHOUT_THEME;
                    break;
                case $disable:
                    $status = CollectStatus::STATUS_DISABLED;
                    break;
                default:
                    $status = null;
                    break;
            }
            $this->collector->clear($status);
            $output->writeln('collected files cleared');
        }

        return 0;
    }
}
