<?php
namespace Zero1\LayoutXmlPlus\Observer;

use DOMDocument;
use DOMXPath;
use Psr\Log\LoggerInterface;
use Zero1\LayoutXmlPlus\Model\AfterHtmlProccesor;

class BlockAfterHtml implements \Magento\Framework\Event\ObserverInterface
{
    protected AfterHtmlProccesor $afterHtmlProccesor;

    protected LoggerInterface $logger;

    public function __construct(
        AfterHtmlProccesor $afterHtmlProccesor,
        LoggerInterface $loggerInterface   
    ){
        $this->afterHtmlProccesor = $afterHtmlProccesor;
        $this->logger = $loggerInterface;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Framework\View\Element\AbstractBlock $block */
        $block = $observer->getData('block');

        if(!$this->afterHtmlProccesor->shouldProcess($block)){
            return;
        }

        /** @var \Magento\Framework\DataObject $transport */
        $transport = $observer->getData('transport');
        $html = $transport->getData('html');
        $html = $this->afterHtmlProccesor->process($block, $html);
        $transport->setData('html', $html);
    }
}