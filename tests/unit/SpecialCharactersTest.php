<?php
namespace Zero1\LayoutXmlPlus\Unit;

use PHPUnit\Framework\TestCase;
use Zero1\LayoutXmlPlus\Model\AfterHtmlProccesor;
use Zero1\LayoutXmlPlus\Model\Processor\Sanitizer;

class SpecialCharactersTest extends TestCase
{
    public function testAttributeValueAppend()
    {
        $sanitizer = new Sanitizer();
        $processor = new AfterHtmlProccesor(
            $sanitizer,
            $this->createMock(\Psr\Log\LoggerInterface::class),
            $this->createMock(\Zero1\LayoutXmlPlus\Model\Config::class),
            $this->createMock(\Magento\Framework\Filesystem\DirectoryList::class),
            $this->createMock(\Magento\Framework\Filesystem\Io\File::class),
            [
                'attribute_value_replace' => new \Zero1\LayoutXmlPlus\Model\Processor\AttributeValueReplace($sanitizer),
                'attribute_value_set' => new \Zero1\LayoutXmlPlus\Model\Processor\AttributeValueSet($sanitizer),
                'attribute_value_append' => new \Zero1\LayoutXmlPlus\Model\Processor\AttributeValueAppend($sanitizer),
                'attribute_value_remove' => new \Zero1\LayoutXmlPlus\Model\Processor\AttributeValueRemove($sanitizer),
                'attribute_remove' => new \Zero1\LayoutXmlPlus\Model\Processor\AttributeRemove($sanitizer),
                'child_html' => new \Zero1\LayoutXmlPlus\Model\Processor\ChildHtml($sanitizer),
                'remove' => new \Zero1\LayoutXmlPlus\Model\Processor\Remove($sanitizer),
            ]
        );
        $input = file_get_contents(__DIR__ . '/SpecicalCharactersTest/input.html');
        $expectedOutput = file_get_contents(__DIR__ . '/SpecicalCharactersTest/attribute_value_append_output.html');
        
        $block = $this->getMockBuilder(\Magento\Framework\View\Element\Template::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $block->setData(AfterHtmlProccesor::DATA_KEY_ACTIONS, [
            'something' => [
                'xpath' => '/root//div',
                'action' => 'attribute_value_append',
                'attribute' => 'class',
                'value' => 'bar',
            ],
        ]);

        $output = $processor->process($block, $input);

        $this->assertEquals(
            $expectedOutput,
            $output,
            "The output HTML does not match the expected output when appending to attribute values with special characters."
        );
    }
}


