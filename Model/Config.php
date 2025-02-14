<?php

declare(strict_types=1);

namespace Zero1\LayoutXmlPlus\Model;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Zero1\LayoutXmlPlus\Model\Config\Source\CollectStatus;

class Config
{
    public const CONFIG_PATH_ENABLED = 'dev/layout_xml_plus/enable';
    public const CONFIG_PATH_LOGGING_ENABLED = 'dev/layout_xml_plus/enable_logging';
    public const CONFIG_PATH_COLLECT_STATUS = 'dev/layout_xml_plus/collect_status';

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

    public function setCollectStatusWithTheme()
    {
        $this->setCollectStatus(CollectStatus::STATUS_WITH_THEME);
    }

    public function setCollectStatusWithoutTheme()
    {
        $this->setCollectStatus(CollectStatus::STATUS_WITHOUT_THEME);
    }

    public function setCollectStatusDisabled()
    {
        $this->setCollectStatus(CollectStatus::STATUS_DISABLED);
    }

    public function setCollectStatus($status)
    {
        if(!in_array($status, [CollectStatus::STATUS_DISABLED, CollectStatus::STATUS_WITH_THEME, CollectStatus::STATUS_WITHOUT_THEME])){
            throw new \InvalidArgumentException('Invalid status: '.$status.', must be one of: '.implode(', ', [
                CollectStatus::STATUS_DISABLED, CollectStatus::STATUS_WITH_THEME, CollectStatus::STATUS_WITHOUT_THEME
            ]));
        }
        $this->configWriter->save(self::CONFIG_PATH_COLLECT_STATUS, $status);
    }

    public function isCollectStatusWithTheme()
    {
        return CollectStatus::STATUS_WITH_THEME == $this->getCollectStatus();
    }

    public function isCollectStatusWithoutTheme()
    {
        return CollectStatus::STATUS_WITHOUT_THEME == $this->getCollectStatus();
    }

    public function isCollectStatusDisabled()
    {
        return CollectStatus::STATUS_DISABLED == $this->getCollectStatus();
    }

    public function getCollectStatus()
    {
        return (int)$this->config->getValue(self::CONFIG_PATH_COLLECT_STATUS);
    }
}
