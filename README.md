XML APIs for the XP Framework
=============================

[![Build Status on TravisCI](https://secure.travis-ci.org/xp-framework/xml.svg)](http://travis-ci.org/xp-framework/xml)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Required PHP 5.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-5_4plus.png)](http://php.net/)
[![Supports PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.png)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-framework/xml/version.png)](https://packagist.org/packages/xp-framework/xml)

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

Console::writeLine((new XPath($xml))->query('/dialog/buttons/button/@name')));
```
