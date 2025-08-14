<?php

declare(strict_types=1);

namespace Zero1\LayoutXmlPlus\Model;

use Zero1\LayoutXmlPlus\Model\Config;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File as IO;
use Zero1\LayoutXmlPlus\Model\Config\Source\CollectStatus;

class Collector
{
    private const WITH_THEME = '/with_theme';
    private const WITHOUT_THEME = '/without_theme';

    /** @var Config */
    protected $config;

    /** @var DirectoryList */
    protected $directoryList;

    /** @var IO */
    protected $io;

    protected $lockHandle;

    public function __construct(
        Config $config,
        DirectoryList $directoryList,
        IO $io
    ){
        $this->config = $config;
        $this->directoryList = $directoryList;
        $this->io = $io;
    }

    public function clear($status = null)
    {
        if($status === null){
            $status = $this->config->getCollectStatus();
        }

        switch($status){
            case CollectStatus::STATUS_WITH_THEME:
                $dir = $this->baseDir(self::WITH_THEME);
                break;
            case CollectStatus::STATUS_WITHOUT_THEME:
                $dir = $this->baseDir(self::WITHOUT_THEME);
                break;
            case CollectStatus::STATUS_DISABLED:
                $dir = $this->baseDir();
                break;
        }

        $this->io->rmdirRecursive($dir);
    }

    /**
     * @param \Magento\Framework\View\Element\Template $block
     * @param string html
     */
    public function collect($block, $html)
    {
        if($this->config->isCollectStatusDisabled()){
            return;
        }

        $templateName = $block->getTemplate();
        $templatePath = $block->getTemplateFile();

        if(!$templateName || !$templatePath || !$block->getNameInLayout()){
            return;
        }

        $this->lock();
        $manifest = $this->getManifest();

        // maybe potential for this in future but for now ignoring
        // the complication
        if(isset($manifest[$templateName])){
            $this->unlock();
            return;
        }

        $manifest[$templateName] = $templatePath;
        $this->writeManifest($manifest);
        $this->writeOutput($templateName, $html);
        $this->unlock();
    }

    protected function baseDir($subDir = '')
    {
        $dir = $this->directoryList->getPath('var').'/layout-xml-plus'.$subDir;
        $this->io->checkAndCreateFolder($dir, 0775);
        return $dir;
    }

    protected function lock()
    {
        $lockFile = $this->baseDir().'/lock';
        if($this->lockHandle){
            throw new \Exception('Already locked');
        }
        $this->lockHandle = fopen($lockFile, 'w+');
        flock($this->lockHandle, LOCK_EX);
    }

    protected function unlock()
    {
        if(!$this->lockHandle){
            return;
        }
        flock($this->lockHandle, LOCK_UN);
        fclose($this->lockHandle);
        $this->lockHandle = null;
    }

    protected function collectDir()
    {
        $subDir = $this->config->isCollectStatusWithTheme()?
            self::WITH_THEME : self::WITHOUT_THEME;
        return $this->baseDir($subDir);
    }

    public function getWithThemeManifest()
    {
        return $this->getManifest(
            $this->baseDir(self::WITH_THEME)
        );
    }

    public function getWithoutThemeManifest()
    {
        return $this->getManifest(
            $this->baseDir(self::WITHOUT_THEME)
        );
    }

    protected function getManifest($dir = null)
    {
        if(!$dir){
            $dir = $this->collectDir();
        }
        $content = $this->io->read(
            $dir.'/data.json'
        );
        if(!$content){
            return [];
        }
        return json_decode($content, true);
    }

    protected function writeManifest($manifest)
    {
        $this->io->write(
            $this->collectDir().'/data.json',
            json_encode($manifest)
        );
    }

    protected function templateNameToPath($templateName)
    {
        return str_replace('/', '-', $templateName);
    }

    protected function writeOutput($templateName, $html)
    {
        $this->io->write(
            $this->collectDir().'/'.$this->templateNameToPath($templateName),
            $html
        );
    }

    public function getWithThemeOutputPath($templateName)
    {
        return $this->baseDir(self::WITH_THEME).'/'.$this->templateNameToPath($templateName);
    }

    public function getWithThemeOutput($templateName)
    {
        return $this->io->read(
            $this->getWithThemeOutputPath($templateName)
        );
    }

    public function getWithoutThemeOutputPath($templateName)
    {
        return $this->baseDir(self::WITHOUT_THEME).'/'.$this->templateNameToPath($templateName);
    }

    public function getWithoutThemeOutput($templateName)
    {
        return $this->io->read(
            $this->getWithoutThemeOutputPath($templateName)
        );
    }
}
