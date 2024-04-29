<?php
namespace Zero1\LayoutXmlPlus\Model;

use DOMDocument;
use DOMXPath;
use Psr\Log\LoggerInterface;
use Zero1\LayoutXmlPlus\Model\Processor\Sanitizer;

class AfterHtmlProccesor
{
    public const DATA_KEY_ACTIONS = 'after_html_actions';

    protected Sanitizer $sanitizer;

    protected LoggerInterface $logger;

    /** @var array<\Zero1\LayoutXmlPlus\Model\ProcessorInterface> */
    protected $processorPool;

    protected $debugEnabled;

    public function __construct(
        Sanitizer $sanitizer,
        LoggerInterface $loggerInterface,
        $processorPool = [],
        $debugEnabled = false
    ){
        $this->sanitizer = $sanitizer;
        $this->logger = $loggerInterface;
        $this->processorPool = $processorPool;
        $this->debugEnabled = $debugEnabled;
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
    protected function debug()
    {
        return $this->debugEnabled;
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
        return $block->hasData(self::DATA_KEY_ACTIONS);
    }

    protected function saveToFile($filename, $html)
    {
        if(!is_dir('../var/layout-xml-plus')){
            mkdir('../var/layout-xml-plus', 0777, true);
        }
        $filepath = '../var/layout-xml-plus/'.$filename;
        file_put_contents($filepath, $html);
        return $filepath;
    }

    /**
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @param string $html
     * @return string
     */
    public function process($block, $html)
    {
        $this->debugEnabled = true;

        $nameInLayout = $block->getNameInLayout();
        
        $debugInfo = [
            'block_name' => $nameInLayout,
        ];

        if($this->debug()){
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

            if($this->debug()){
                $this->logger->debug('processing action', $debugInfo);
            }
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
                if($this->debug()){
                    $this->logger->debug('xpath matched no elements', $debugInfo);
                }
                continue;
            }

            $debugInfo['nodes_matched'] = $nodes->length;
            if($this->debug()){
                $this->logger->debug('xpath matched elements', $debugInfo);
            }

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

        if($this->debug()){
            $filepath = $this->saveToFile($nameInLayout.'.new.html', $html);
            $debugInfo['new_html'] = $filepath;
            $this->logger->debug('finished processing', $debugInfo);
        }

        return $html;
    }
}
