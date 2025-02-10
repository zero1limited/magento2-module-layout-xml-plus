<?php

declare(strict_types=1);

namespace Zero1\LayoutXmlPlus\Model;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * @see https://developer.adobe.com/commerce/php/development/cli-commands/custom/
 */ 
class Config
{
    public const CONFIG_PATH_ENABLED = 'dev/layout_xml_plus/enable';
    public const CONFIG_PATH_LOGGING_ENABLED = 'dev/layout_xml_plus/enable_logging';

    protected $config;

    protected $configWriter;

    public function __construct(
        ScopeConfigInterface $config,
        WriterInterface $writer
    ){
        $this->config = $config;
        $this->configWriter = $writer;
    }

    public function getStatus(): bool
    {
        return (bool)$this->config->getValue(self::CONFIG_PATH_ENABLED);
    }

    public function isEnabled(): bool
    {
        return $this->getStatus() == true;
    }

    public function setStatus($status): void
    {
        $this->configWriter->save(self::CONFIG_PATH_ENABLED, ($status? 1 : 0));
    }

    public function getLoggingStatus(): bool
    {
        return (bool)$this->config->getValue(self::CONFIG_PATH_LOGGING_ENABLED);
    }

    public function isLoggingEnabled(): bool
    {
        return $this->getStatus() == true;
    }

    public function setLoggingStatus($status): void
    {
        $this->configWriter->save(self::CONFIG_PATH_LOGGING_ENABLED, ($status? 1 : 0));
    }
}
