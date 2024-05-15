# Zero1_LayoutXmlPlus

This module is aimed at reducing the need to override templates for blocks.
For example, changing a single class for a button shouldn't require you to override the template.
However for significant dom structure changes we would still recommend overriding the template.

## Installation
composer require
module enable
setup:upgrade

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

## TODO
- [ ] make debug flag setable/env'able
- [ ] make play nice with hyva when prod mode