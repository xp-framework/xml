XML APIs for the XP Framework
========================================================================
The xml package provides APIs to handle XML.

XML data
--------
Most of the time, XML is used to hold data. In this case, a fully blown
DOM api is too much overhead to work with the data. This is where the
xml.Tree class comes in.

This example will print out a nicely formatted XML document:

```php
$t= new Tree('customer');
$t->root()->setAttribute('id', '6100');
$t->addChild(new Node('name', 'Timm Übercoder'));
$t->addChild(new Node('email', 'uebercoder@example.com'));

echo $t->getSource(INDENT_DEFAULT);
```

XSL Transformation
------------------
The DomXSLProcessor class uses LibXSLT and thus supports EXSLT features
like user functions, callbacks, string functions, dynamic evaluation and
more.

A simple example:

```php
use xml\DomXSLProcessor;
use xml\TransformerException;
use util\cmd\Console;

$proc= new DomXSLProcessor();
$proc->setXSLFile('test.xsl');
$proc->setXMLFile('test.xml');

try {
  $proc->run();
} catch (TransformerException $e) {
  // Handle
}

Console::writeLine($proc->output());
```

XPath queries
-------------

```php
use xml\XPath;
use util\cmd\Console;

$xml= '<dialog id="file.open">
 <caption>Open a file</caption>
   <buttons>
     <button name="ok"/>
     <button name="cancel"/>
   </buttons>
</dialog>';

Console::writeLine(create(new XPath($xml))->query('/dialog/buttons/button/@name')));
```