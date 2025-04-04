<?php
namespace Zero1\LayoutXmlPlus\Model;

use DOMDocument;
use DOMXPath;
use Psr\Log\LoggerInterface;
use Zero1\LayoutXmlPlus\Model\Processor\Sanitizer;
use Zero1\LayoutXmlPlus\Model\Config;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File as IO;

class AfterHtmlProccesor
{
    public const DATA_KEY_ACTIONS = 'after_html_actions';

    protected Sanitizer $sanitizer;

    protected LoggerInterface $logger;

    /** @var array<\Zero1\LayoutXmlPlus\Model\ProcessorInterface> */
    protected $processorPool;

    /** @var Config */
    protected $config;

    protected $debugEnabled;

    /** @var DirectoryList */
    protected $directoryList;

    /** @var IO */
    protected $io;

    public function __construct(
        Sanitizer $sanitizer,
        LoggerInterface $loggerInterface,
        Config $config,
        DirectoryList $directoryList,
        IO $io,
        $processorPool = []
    ){
        $this->sanitizer = $sanitizer;
        $this->logger = $loggerInterface;
        $this->config = $config;
        $this->processorPool = $processorPool;
        $this->directoryList = $directoryList;
        $this->io = $io;
        
        $this->debugEnabled = $this->config->isLoggingEnabled();
    }

    protected function sanitize($value)
    {
        return $this->sanitizer->sanitize($value);
    }

    protected function unsanitize($value)
    {
        return $this->sanitizer->unsanitize($value);
    }

    /**
     * @return void
     */
    protected function debug($message, $context)
    {
        if($this->debugEnabled){
            $this->logger->debug($message, $context);
        }
    }

    /**
     * @return ProcessorInterface
     */
    protected function getProcessor($id)
    {
        if(!isset($this->processorPool[$id])){
            throw new \InvalidArgumentException('Unable to find processor with id: "'.$id.'"');
        }
        return $this->processorPool[$id];
    }

    /**
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @return boolean
     */
    public function shouldProcess($block)
    {
        return $block->hasData(self::DATA_KEY_ACTIONS) && $this->config->isEnabled();
    }

    protected function baseDir()
    {
        $dir = $this->directoryList->getPath('var').'/layout-xml-plus/logging';
        $this->io->checkAndCreateFolder($dir, 0775);
        return $dir;
    }

    protected function saveToFile($filename, $html)
    {
        $this->io->write(
            $this->baseDir().'/'.$filename,
            $html
        );
        return $this->baseDir().'/'.$filename;
    }

    /**
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @param string $html
     * @return string
     */
    public function process($block, $html)
    {
        $nameInLayout = $block->getNameInLayout();
        
        $debugInfo = [
            'block_name' => $nameInLayout,
        ];

        if($this->debugEnabled){
            $filepath = $this->saveToFile($nameInLayout.'.orig.html', $html);
            $debugInfo['original_html'] = $filepath;
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $htmlToUse = '<root>'.$html.'</root>';
        $htmlToUse = $this->sanitize($htmlToUse);
        $dom->loadHTML($htmlToUse, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $actions = $block->getData(self::DATA_KEY_ACTIONS);

        foreach($actions as $actionId => $options){
            $debugInfo['action_id'] = $actionId;
            $debugInfo['options'] = $options;

            $this->debug('processing action', $debugInfo);

            if(!isset($options['xpath'])){
                $this->logger->error('"xpath" missing from options', $debugInfo);
                continue;
            }
            if(!isset($options['action'])){
                $this->logger->error('"action" missing from options', $debugInfo);
                continue;
            }

            /** @var \DOMNodeList $nodes */
            $nodes = $xpath->query($options['xpath']);
            if(!$nodes || !$nodes->length){
                $this->logger->warning('xpath matched no elements', $debugInfo);
                continue;
            }

            $debugInfo['nodes_matched'] = $nodes->length;
            $this->debug('xpath matched elements', $debugInfo);

            /** @var \DOMElement $node */
            foreach($nodes as $node){
                try{
                    $processor = $this->getProcessor($options['action']);
                    $processor->process($node, $dom, $options, $block);
                }catch(\InvalidArgumentException $e){
                    $debugInfo['error'] = $e->getMessage();
                    $this->logger->error('unable to process action', $debugInfo);
                }
            }
        }

        $html = $xpath->document->saveHTML(
            $xpath->document->getElementsByTagName('root')[0]
        );
        $html = str_replace(['<root>', '</root>'], '', $html);
        $html = $this->unsanitize($html);

        if($this->debugEnabled){
            $filepath = $this->saveToFile($nameInLayout.'.new.html', $html);
            $debugInfo['new_html'] = $filepath;
            $this->debug('finished processing', $debugInfo);
        }

        return $html;
    }
}
