<?php
namespace Zero1\LayoutXmlPlus\Observer;

use DOMDocument;
use DOMXPath;
use Psr\Log\LoggerInterface;
use Zero1\LayoutXmlPlus\Model\AfterHtmlProccesor;
use Zero1\LayoutXmlPlus\Model\Collector;

class BlockAfterHtml implements \Magento\Framework\Event\ObserverInterface
{
    protected AfterHtmlProccesor $afterHtmlProccesor;

    protected LoggerInterface $logger;

    protected Collector $collector;

    public function __construct(
        AfterHtmlProccesor $afterHtmlProccesor,
        LoggerInterface $loggerInterface,
        Collector $collector
    ){
        $this->afterHtmlProccesor = $afterHtmlProccesor;
        $this->logger = $loggerInterface;
        $this->collector = $collector;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Framework\View\Element\AbstractBlock $block */
        /** @var \Magento\Framework\View\Element\Template $block */
        $block = $observer->getData('block');

        /** @var \Magento\Framework\DataObject $transport */
        $transport = $observer->getData('transport');
        $html = $transport->getData('html');
        $this->collector->collect($block, $html);

        if($this->afterHtmlProccesor->shouldProcess($block)){
            $html = $this->afterHtmlProccesor->process($block, $html);
            $transport->setData('html', $html);
        }
    }

    protected $dataKey = 'bar';

    /**
     * @param \Magento\Framework\View\Element\Template $block
     * @param string html
     */
    protected function logBlock($block, $html)
    {
        $templateName = $block->getTemplate();
        $templatePath = $block->getTemplateFile();

        if(!$templateName || !$templatePath || !$block->getNameInLayout()){
            return;
        }

        if(!is_dir('../var/layout-xml/'.$this->dataKey)){
            mkdir('../var/layout-xml/'.$this->dataKey, 0777, true);
            mkdir('../var/layout-xml/'.$this->dataKey.'/outputs', 0777, true);
            file_put_contents('../var/layout-xml/'.$this->dataKey.'/data.json', json_encode([]));
        }

        $data = json_decode(
            file_get_contents('../var/layout-xml/'.$this->dataKey.'/data.json'),
            true
        );
        
        if(isset($data[$templateName])){
            return;
        }

        $data[$templateName] = $templatePath;
        file_put_contents(
            '../var/layout-xml/'.$this->dataKey.'/outputs/'.str_replace('/', '-', $templateName),
            $html
        );
        file_put_contents(
            '../var/layout-xml/'.$this->dataKey.'/data.json',
            json_encode($data)
        );

    }
}