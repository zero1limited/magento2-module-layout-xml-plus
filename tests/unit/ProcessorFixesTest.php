<?php
namespace Zero1\LayoutXmlPlus\Unit;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;
use Zero1\LayoutXmlPlus\Model\AfterHtmlProccesor;
use Zero1\LayoutXmlPlus\Model\Config;
use Zero1\LayoutXmlPlus\Model\Processor;

class ProcessorFixesTest extends TestCase
{
    /** @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    private function processor(): AfterHtmlProccesor
    {
        $sanitizer = new Processor\Sanitizer();
        $masker = new Processor\ScriptMasker();
        $this->logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $config = $this->createMock(Config::class);
        $config->method('isLoggingEnabled')->willReturn(false);
        $config->method('isEnabled')->willReturn(true);

        return new AfterHtmlProccesor(
            $sanitizer,
            $this->logger,
            $config,
            $this->createMock(\Magento\Framework\Filesystem\DirectoryList::class),
            $this->createMock(\Magento\Framework\Filesystem\Io\File::class),
            [
                'attribute_value_replace' => new Processor\AttributeValueReplace($sanitizer),
                'attribute_value_set' => new Processor\AttributeValueSet($sanitizer),
                'attribute_value_append' => new Processor\AttributeValueAppend($sanitizer),
                'attribute_value_remove' => new Processor\AttributeValueRemove($sanitizer),
                'attribute_remove' => new Processor\AttributeRemove($sanitizer),
                'child_html' => new Processor\ChildHtml($sanitizer, $masker),
                'remove' => new Processor\Remove($sanitizer),
            ],
            $masker
        );
    }

    private function block(array $actions, array $children = []): DataObject
    {
        return new class(['name_in_layout' => 'test.block', 'after_html_actions' => $actions], $children) extends DataObject {
            private $kids;
            public function __construct(array $data, array $kids) { parent::__construct($data); $this->kids = $kids; }
            public function getChildBlock($name) { return isset($this->kids[$name]) ? new DataObject() : false; }
            public function getChildHtml($name = '', $useCache = true) { return $this->kids[$name] ?? ''; }
        };
    }

    public function testInlineScriptWithClosingTagSequencesSurvives()
    {
        $script = "<script>\n    const tpl = '<b>%s</b>' + `</span>` + \"a && b\" + '@click';\n    if (1 < 2) {}\n</script>";
        $html = '<div class="a">x</div>' . $script . '<style>.x > .y { content: "</p>"; }</style>';

        $output = $this->processor()->process($this->block([
            'add' => ['xpath' => '/root/div', 'action' => 'attribute_value_append', 'attribute' => 'class', 'value' => 'b'],
        ]), $html);

        $this->assertStringContainsString($script, $output);
        $this->assertStringContainsString('<style>.x > .y { content: "</p>"; }</style>', $output);
        $this->assertStringContainsString('<div class="a b">x</div>', $output);
    }

    public function testAttributeValueRemoveOnlyRemovesWholeTokens()
    {
        $output = $this->processor()->process($this->block([
            'rm' => ['xpath' => '/root/div', 'action' => 'attribute_value_remove', 'attribute' => 'class', 'value' => ['p-4', 'flex']],
        ]), '<div class="p-4 md:p-4 group-p-4 flex flex-col">x</div>');

        $this->assertStringContainsString('<div class="md:p-4 group-p-4 flex-col">x</div>', $output);
    }

    public function testEscapedSpacesInAttributesDoNotBreakClassMatching()
    {
        $output = $this->processor()->process($this->block([
            'add' => [
                'xpath' => "/root/div[contains(concat(' ', normalize-space(@class), ' '), ' p-8 ')]",
                'action' => 'attribute_value_append', 'attribute' => 'class', 'value' => 'pos',
            ],
        ]), '<div class="flex&#x20;p-8&#x20;gap-2">x</div>');

        $this->assertStringContainsString('<div class="flex p-8 gap-2 pos">x</div>', $output);
    }

    public function testChildHtmlKeepsAllNodesAndUtf8AndReplaceDoesNotError()
    {
        $child = 'Prix&nbsp;: <span class="c">Panier € — ✓</span> tail <script>if (a < b) { x = "</i>"; }</script>';
        $processor = $this->processor();
        $this->logger->expects($this->never())->method('error');

        $output = $processor->process($this->block([
            'swap' => ['xpath' => '/root/div/em', 'action' => 'child_html', 'block' => 'kid', 'target' => 'replace'],
        ], ['kid' => $child]), '<div><em>old</em></div>');

        $this->assertStringNotContainsString('<em>', $output);
        $this->assertStringContainsString('<span class="c">Panier € — ✓</span>', $output);
        $this->assertStringContainsString(' tail ', $output);
        $this->assertStringContainsString('<script>if (a < b) { x = "</i>"; }</script>', $output);
        $this->assertStringNotContainsString('<p>', $output);
    }

    public function testOptionalActionDoesNotWarnWhenNothingMatches()
    {
        $processor = $this->processor();
        $this->logger->expects($this->never())->method('warning');

        $processor->process($this->block([
            'opt' => ['xpath' => '/root/section', 'action' => 'remove', 'optional' => true],
        ]), '<div>x</div>');
    }

    public function testLoggingFollowsTheLoggingFlagNotTheEnableFlag()
    {
        $values = [Config::CONFIG_PATH_ENABLED => '1', Config::CONFIG_PATH_LOGGING_ENABLED => '0'];
        $scope = $this->createMock(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturnCallback(function ($path) use (&$values) {
            return $values[$path] ?? null;
        });
        $config = new Config($scope, $this->createMock(WriterInterface::class));

        $this->assertTrue($config->isEnabled());
        $this->assertFalse($config->isLoggingEnabled());

        $values[Config::CONFIG_PATH_LOGGING_ENABLED] = '1';
        $this->assertTrue($config->isLoggingEnabled());

        $values[Config::CONFIG_PATH_ENABLED] = '0';
        $this->assertFalse($config->isLoggingEnabled());
    }
}
