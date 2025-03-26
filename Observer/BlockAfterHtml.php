<?php
namespace Zero1\LayoutXmlPlus\Observer;

use DOMDocument;
use DOMXPath;
use Zero1\LayoutXmlPlus\Model\AfterHtmlProccesor;
use Zero1\LayoutXmlPlus\Model\Collector;

class BlockAfterHtml implements \Magento\Framework\Event\ObserverInterface
{
    protected AfterHtmlProccesor $afterHtmlProccesor;

    protected Collector $collector;

    public function __construct(
        AfterHtmlProccesor $afterHtmlProccesor,
        Collector $collector
    ){
        $this->afterHtmlProccesor = $afterHtmlProccesor;
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
}