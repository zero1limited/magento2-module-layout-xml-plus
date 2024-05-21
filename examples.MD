# Examples



```
        <referenceBlock name="category.description">
            <arguments>
                <argument name="after_html_actions" xsi:type="array">
                    <item name="replace-padding" xsi:type="array">
                        <item name="xpath" xsi:type="string"><![CDATA[/root//div[contains(@class, 'form-control')]]]></item>
                        <item name="action" xsi:type="string">attribute_value_replace</item>
                        <item name="attribute" xsi:type="string">class</item>
                        <item name="search" xsi:type="string">mb-1</item>
                        <item name="replace" xsi:type="string">mb-999</item>
                    </item>
                </argument>
            </arguments>
        </referenceBlock>
```
