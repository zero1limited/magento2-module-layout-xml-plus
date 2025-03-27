# Zero1_LayoutXmlPlus

This module is aimed at reducing the need to override templates for blocks.
For example, changing a single class for a button shouldn't require you to override the template.
However for significant dom structure changes we would still recommend overriding the template.

## Installation
```
composer require zero1/layout-xml-plus
php bin/magento module:enable 
php bin/magento setup:upgrade

```

## Actions
The functionality has been split into "actions" different actions that can be taken on the output of a blocks html before it is passed to a user.

**Common Parameters**
- `action`: id of the action you wish to carry out
- `xpath`: an xpath expressing to identify elements within the template. For compatibilty all templates are rendered inside a `<root>` node, so if you wanted the first div you would want something like `(/root/div)[1]` or 2nd div: `(/root/div)[2]` or all divs `/root//div`.

### AttributeValueReplace
id: `attribute_value_replace`
Replace a value within an attributes value, a good example would be replacing a class with another class.

example
`catalog_category_view.xml`
```xml
<referenceBlock name="category.products.list">
    <arguments>
        <argument name="after_html_actions" xsi:type="array">
            <item name="replace-padding" xsi:type="array">
                <item name="xpath" xsi:type="string"><![CDATA[/root//section[@id="product-list"]]]></item>
                <item name="action" xsi:type="string">attribute_value_replace</item>
                <item name="attribute" xsi:type="string">class</item>
                <item name="search" xsi:type="string">py-8</item>
                <item name="replace" xsi:type="string">pb-8</item>
            </item>
        </argument>
    </arguments>
</referenceBlock>
```

### AttributeValueSet
id: `attribute_value_set`

Completely override the value of an attribute

example
`catalog_category_view.xml`
```xml
<referenceBlock name="category.products.list">
    <arguments>
        <argument name="after_html_actions" xsi:type="array">
            <item name="change-wrapper-id" xsi:type="array">
                <item name="xpath" xsi:type="string"><![CDATA[/root//section[@id="product-list"]]]></item>
                <item name="action" xsi:type="string">attribute_value_set</item>
                <item name="attribute" xsi:type="string">id</item>
                <item name="value" xsi:type="string">product-list-wrapper</item>
            </item>
        </argument>
    </arguments>
</referenceBlock>
```

### AttributeValueAppend
id: `attribute_value_append`

Add an item to an attributes list of values (e.g add a class)

example
`catalog_product_view.xml`
```xml
<referenceBlock name="product.detail.page">
    <arguments>
        <argument name="after_html_actions" xsi:type="array">
            <item name="add-section-class" xsi:type="array">
                <item name="xpath" xsi:type="string"><![CDATA[/root//section]]></item>
                <item name="action" xsi:type="string">attribute_value_append</item>
                <item name="attribute" xsi:type="string">class</item>
                <item name="value" xsi:type="string">c-pdp-container</item>
            </item>
        </argument>
    </arguments>
</referenceBlock>
```
example
`catalog_product_view.xml`
```xml
<referenceBlock name="product.detail.page">
    <arguments>
        <argument name="after_html_actions" xsi:type="array">
            <item name="add-classes" xsi:type="array">
                <item name="xpath" xsi:type="string"><![CDATA[(/root/section/div/div)[1]]]></item>
                <item name="action" xsi:type="string">attribute_value_append</item>
                <item name="attribute" xsi:type="string">class</item>
                <item name="value" xsi:type="array">
                    <item name="flex" xsi:type="string">flex</item>
                    <item name="flex-wrap" xsi:type="string">flex-wrap</item>
                    <item name="order-first" xsi:type="string">order-first</item>
                </item>
            </item>
        </argument>
    </arguments>
</referenceBlock>
```

### AttributeValueRemove
id: `attribute_value_remove`
Remove a value from an attributes list of values

example
`catalog_product_view.xml`
```xml
<referenceBlock name="product.detail.page">
    <arguments>
        <argument name="after_html_actions" xsi:type="array">
            <item name="remove-classes" xsi:type="array">
                <item name="xpath" xsi:type="string"><![CDATA[(/root/section/div/div)[1]]]></item>
                <item name="action" xsi:type="string">attribute_value_remove</item>
                <item name="attribute" xsi:type="string">class</item>
                <item name="value" xsi:type="array">
                    <item name="grid" xsi:type="string">grid</item>
                    <item name="grid-rows-auto" xsi:type="string">grid-rows-auto</item>
                    <item name="grid-cols-1" xsi:type="string">grid-cols-1</item>
                    <item name="md:gap-x-5" xsi:type="string">md:gap-x-5</item>
                </item>
            </item>
        </argument>
    </arguments>
</referenceBlock>
```

### AttributeRemove
id: `attribute_remove`

Completely remove an attribute

example
`catalog_product_view.xml`
```xml
<referenceBlock name="product.detail.page">
    <arguments>
        <argument name="after_html_actions" xsi:type="array">
            <item name="remove-attribute" xsi:type="array">
                <item name="xpath" xsi:type="string"><![CDATA[(/root/section/div/div)[1]]]></item>
                <item name="action" xsi:type="string">attribute_remove</item>
                <item name="attribute" xsi:type="string">@click</item>
            </item>
        </argument>
    </arguments>
</referenceBlock>
```

### ChildHtml
id: `child_html`

Insert a childs html into a specific part of the output.
This requires you to add the child block in layout xml.

Valid targets
- `start`: at the begining of the nodes content (before current children)
- `end`: at the end of the nodes content (after current children)
- `before`: before the current node
- `after`: after the current node
- `replace`: replace the target node with the content of the block

example
`default.xml`
```xml
<referenceBlock name="footer-content">
    <block name="footer.social_icons" template="Magento_Theme::html/footer/social_icons.phtml"/>
    <arguments>
        <argument name="after_html_actions" xsi:type="array">
            <item name="add-in-social-icons" xsi:type="array">
                <item name="xpath" xsi:type="string"><![CDATA[(/root/div)[1]]]></item>
                <item name="action" xsi:type="string">child_html</item>
                <item name="block" xsi:type="string">footer.social_icons</item>
                <item name="target" xsi:type="string">start</item>
            </item>
        </argument>
    </arguments>
</referenceBlock>
```


### Remove
id: `remove`

Remove an element from the dom.

example
`default.xml`
```xml
<referenceBlock name="footer-content">
    <block name="footer.social_icons" template="Magento_Theme::html/footer/social_icons.phtml"/>
    <arguments>
        <argument name="after_html_actions" xsi:type="array">
            <item name="zero1-remove-hyva-link" xsi:type="array">
                <item name="xpath" xsi:type="string"><![CDATA[(/root//a[contains(@class, "title-font")])]]></item>
                <item name="action" xsi:type="string">remove</item>
            </item>
        </argument>
    </arguments>
</referenceBlock>
```

## XPath Cheat Sheet
- select all labels with a class of 'swatch-option' `/root//label[contains(@class, 'swatch-option')]`

## Other Recommendations
When it comes to stopping blocks outputting content, using layout xml can often remove the requirement to change the template.
**N.B** when referencing a block you must use it's name and not it's alias
Example
dont do
```php
<?= // $block->getChildHtml('review_form') ?>
```
do
```xml
<referenceBlock name="product.review.form" display="false"></referenceBlock>
```

## CLI Commands

### Module Status
Disabling the module is a quick way to debug if layout-xml is responsible for a change you are seeing.
- Show module status: `php bin/magento dev:layout-xml-plus:status`
- Disable module: `php bin/magento dev:layout-xml-plus:status --disable`
- Enable module: `php bin/magento dev:layout-xml-plus:status --enable`

### Logging
Enabling logging will cause the module to log out all changes to output, as well as fails to change output (i.e when the xpath doesn't match anything in the content)
- Show logging status: `php bin/magento dev:layout-xml-plus:logging`
- Disable logging: `php bin/magento dev:layout-xml-plus:logging --disable`
- Enable logging: `php bin/magento dev:layout-xml-plus:logging --enable`

When enabled logging will output all blocks that have any layout-xml-plus directives into `var/layout-xml-plus/logging/*`
Each block will be stored as:
- `NAME_IN_LAYOUT.orig.html` - the original content of the block
- `NAME_IN_LAYOUT.new.html` - the content after modification


### Collection / Evaluation
The module also includes as way to find template overides that can be replaced with layout xml directives.

1. Magento setup
  ``bash
  php bin/magento deploy:mode:set developer \
    && php bin/magento cache:enable \
    && php bin/magento cache:flush
  ```
2. Enable collection (with theme)
  ```bash
  php bin/magento dev:layout-xml-plus:collect --with-theme --clear
  ```
3. Browse the site
  Visit a set of pages, carry out a specific set of actions.
  The order doesn't really matter but it's important you remember eaxtly what you did/do.
4. Enable collection (without theme)
  ```bash
  php bin/magento dev:layout-xml-plus:collect --without-theme --clear \
    && php bin/magento cache:flush
  ```
5. Clear out theme files
  ```bash
  find ./app/design/frontend/ -type f -name '*.phtml' -exec rm "{}" \;
  ```
6. Try to browse the site the same as you did in step 3.
  If you hit errors, like "missing template file"
  This will be for blocks that have been added by the theme and not included in Magento core.
  Either remove their declaration from layout xml, or restore the template file. (`git checkout app/design/frontend/theme/path/to/file.phtml`)
  Each time you hit an error you will need to re-run
  ```bash
  php bin/magento dev:layout-xml-plus:collect --without-theme --clear \
    && php bin/magento cache:flush
  ```
  and browse the site again.
  example error:
    ```
    1 exception(s):
    Exception #0 (Magento\Framework\Exception\ValidatorException): Invalid template file: 'Magento_Cms::static-blocks/pagetop.phtml' in module: '' block's name: 'pagetop'
    ```
  example fix:
    ```
    git checkout app/design/frontend/VENDOR/THEME/Magento_Cms/templates/static-blocks/pagetop.phtml
    ```
7. Once you are happy that you have managed to browse the site without the theme files, disable collection
  ```bash
  php bin/magento dev:layout-xml-plus:collect --disable
  ```
  restore your theme
  ```bash
  git checkout app/design/frontend
  ```
8. Get the anlysis
  ```bash
  php bin/magento dev:layout-xml-plus:analyse
  ```
  This will output a report `pub/layout-xml-report.html` which (depending on your web server configuration) should be viewable in your web browser.


## Testing XPath Values

If you cannot find a suitable Browser extension to validate the best XPath selector values, you can use the following via console

Run this once
```
var xpathExists = (xpath) => !!document.evaluate(xpath, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
```
Then run this and if there is a matching XPath result it will return true
```
pathExists('//header/div//div[@class="flex gap-4 items-center"]/div[@class="relative hidden lg:inline-block mr-4"][1]/a')
```
Before moving this into your layout file be sure to prepend the XPath value with '/root'



## Roadmap
- [x] make module disable-able
- [x] make log flag setable/env'able
- [x] record blocks and output, with/without theme
- [ ] generate analysis report.
- [ ] make play nice with hyva when prod mode (ccs classes)
- [ ] unit tests
- [ ] coding standards
- [ ] initial release

**Potentials**
- [ ] profiler with autowarning when block takes excessive time?





SDS testing

https://www-sdslondon-co-uk-21843.54.mdoq.dev/
https://www-sdslondon-co-uk-21843.54.mdoq.dev/british-made-bronze-door-knobs.html
https://www-sdslondon-co-uk-21843.54.mdoq.dev/bronze-beehive-morticerim-door-knob-50-mm.html

enable with theme
php bin/magento deploy:mode:set developer \
    && php bin/magento cache:enable \
    && php bin/magento cache:flush \
    && git checkout app/design/frontend \
    && php bin/magento dev:layout-xml-plus:collect --with-theme --clear

enabled without theme
php bin/magento deploy:mode:set developer \
    && php bin/magento cache:enable \
    && php bin/magento cache:flush \
    && php bin/magento dev:layout-xml-plus:collect --without-theme --clear  \
    && find ./app/design/frontend/ -type f -name '*.phtml' -exec rm "{}" \; \
    && git checkout app/design/frontend/z1/sds_hyva/Magento_Cms/templates/static-blocks/pagetop.phtml \
    && git checkout app/design/frontend/z1/sds_hyva/Magento_Theme/templates/html/header/menu/C-desktop.phtml \
    && git checkout app/design/frontend/z1/sds_hyva/Magento_Theme/templates/html/header/menu/C-desktop-item.phtml \
    && git checkout app/design/frontend/z1/sds_hyva/Magento_Cms/templates/static-blocks/usps.phtml \
    && git checkout app/design/frontend/z1/sds_hyva/Magento_Theme/templates/html/tradewidget.phtml \
    && git checkout app/design/frontend/z1/sds_hyva/Klaviyo_Reclaim/templates/product/viewed_hyva.phtml \
    && git checkout app/design/frontend/z1/sds_hyva/Hyva_Checkout/templates/section/custom-summary-header.phtml

disable
php bin/magento deploy:mode:set developer \
    && php bin/magento cache:enable \
    && php bin/magento cache:flush \
    && git checkout app/design/frontend \
    && php bin/magento dev:layout-xml-plus:collect --disable


Analyze
php bin/magento dev:layout-xml-plus:analyse