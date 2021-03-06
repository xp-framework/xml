<?php namespace xml\meta;

use io\streams\Streams;
use xml\{XMLFormatException, XPath};
use xml\parser\InputSource;

/**
 * Creates objects from XML by using annotations.
 *
 * Example:
 * ```php
 * // [...load $xml from a file or a stream...]
 *
 * $transmission= Unmarshaller::unmarshal($xml, 'com.1and1.qf.xml.types.TransmissionType');
 * ```
 *
 * @test  xp://xml.unittest.UnmarshallerTest
 * @ext   dom
 * @see   http://castor.org/xml-mapping.html
 */
class Unmarshaller {

  static function __static() {
    libxml_use_internal_errors(true);
  }

  /**
   * Retrieve content of a DomElement
   *
   * @param   php.DomElement element
   * @return  string
   */
  protected static function contentOf($element) {
    if ($element instanceof \DOMNodeList) {
      return $element->length ? iconv('utf-8', \xp::ENCODING, $element->item(0)->textContent) : null;
    
    } else if (is_scalar($element)) {
      return $element;
      
    } else if ($element instanceof \DOMNode) {
      switch ($element->nodeType) {
        case 1:   // DOMElement
          return iconv('utf-8', \xp::ENCODING, $element->textContent);

        case 2:   // DOMAttr
          return iconv('utf-8', \xp::ENCODING, $element->value);

        case 3:   // DOMText
        case 4:   // DOMCharacterData
          return iconv('utf-8', \xp::ENCODING, $element->data);
      }
    } else return null;
  }

  /**
   * Recursively unmarshal
   *
   * @param   xml.XPath xpath
   * @param   php.DomElement element
   * @param   lang.XPClass classname
   * @param   [:var] inject
   * @return  object
   * @throws  lang.ClassNotFoundException
   * @throws  xml.XPathException
   */
  protected static function recurse($xpath, $element, $class, $inject) {

    // Namespace handling
    if ($class->hasAnnotation('xmlns')) {
      foreach ($class->getAnnotation('xmlns') as $prefix => $url) {
        $xpath->context->registerNamespace($prefix, $url);
      }
    }
    
    $instance= $class->newInstance();
    foreach ($class->getMethods() as $method) {
      if (!$method->hasAnnotation('xmlmapping', 'element')) continue;

      // Perform XPath query
      $result= $xpath->query($method->getAnnotation('xmlmapping', 'element'), $element);

      // Iterate over results, invoking the method for each node.
      foreach ($result as $node) {
        if ($method->hasAnnotation('xmlmapping', 'class')) {

          // * If the xmlmapping annotation has a key "class", call recurse()
          //   with the given XPath, the node as context and the key's value
          //   as classname
          $arguments= [self::recurse(
            $xpath, 
            $node, 
            \lang\XPClass::forName($method->getAnnotation('xmlmapping', 'class')),
            $inject
          )];
        } else if ($method->hasAnnotation('xmlmapping', 'factory')) {

          // * If the xmlmapping annotation has a key "factory", call recurse()
          //   with the given XPath, the node as context and the results from
          //   the specified method as class name. The specified factory method 
          //   is passed the node's tag name if no "pass" key is available.
          //   In case it is, call the factory method with the arguments 
          //   constructed from the "pass" key.
          if ($method->hasAnnotation('xmlmapping', 'pass')) {
            $factoryArgs= [];
            foreach ($method->getAnnotation('xmlmapping', 'pass') as $pass) {
              $factoryArgs[]= self::contentOf($xpath->query($pass, $node));
            }
          } else {
            $factoryArgs= [$node->nodeName];
          }
          $arguments= [self::recurse(
            $xpath, 
            $node, 
            \lang\XPClass::forName(call_user_func_array(
              [$instance, $method->getAnnotation('xmlmapping', 'factory')], 
              $factoryArgs
            )),
            $inject
          )];
        } else if ($method->hasAnnotation('xmlmapping', 'pass')) {
        
          // * If the xmlmapping annotation has a key "pass" (expected to be an
          //   array of XPaths relative to the node), construct the method's
          //   argument list from the XPaths' results.
          $arguments= [];
          foreach ($method->getAnnotation('xmlmapping', 'pass') as $pass) {
            $arguments[]= self::contentOf($xpath->query($pass, $node));
          }
        } else if ($method->hasAnnotation('xmlmapping', 'cast')) {
          $cast= $method->getAnnotation('xmlmapping', 'cast');
          switch (sscanf($cast, '%[^:]::%s', $c, $m)) {
            case 1: $target= [$instance, $c]; break;
            case 2: $target= [$c, $m]; break;
            default: throw new \lang\IllegalArgumentException('Unparseable cast "'.$cast.'"');
          }

          // * If the xmlmapping annotation contains a key "convert", cast the node's
          //   contents using the given callback method before passing it to the method.
          $arguments= call_user_func($target, iconv('utf-8', \xp::ENCODING, $node->textContent));
        } else if ($method->hasAnnotation('xmlmapping', 'type')) {

          // * If the xmlmapping annotation contains a key "type", cast the node's
          //   contents to the specified type before passing it to the method.
          $value= iconv('utf-8', \xp::ENCODING, $node->textContent);
          settype($value, $method->getAnnotation('xmlmapping', 'type'));
          $arguments= [$value];
        } else {

          // * Otherwise, pass the node's content to the method
          $arguments= [iconv('utf-8', \xp::ENCODING, $node->textContent)];
        }
        
        // Pass injection parameters at end of list
        if ($method->hasAnnotation('xmlmapping', 'inject')) {
          foreach ($method->getAnnotation('xmlmapping', 'inject') as $name) {
            if (!isset($inject[$name])) throw new \lang\IllegalArgumentException(
              'Injection parameter "'.$name.'" not found for '.$method->toString()
            );
            $arguments[]= $inject[$name];
          }
        }
        
        $method->invoke($instance, $arguments);
      }
    }

    return $instance;
  }

  /**
   * Unmarshal XML to an object
   *
   * @param   string xml
   * @param   string classname
   * @return  object
   * @throws  lang.ClassNotFoundException
   * @throws  xml.XMLFormatException
   * @deprecated  Use unmarshalFrom() instead
   */
  public static function unmarshal($xml, $classname) {
    libxml_clear_errors();
    $doc= new \DOMDocument();
    $source= '(string)';
    if ('' === (string)$xml) {    // Handle empty string, raise XML_IO_NO_INPUT
      throw new XMLFormatException('Empty string supplied as input', 1547, $source, 0, 0);
    }
    if (!$doc->loadXML($xml)) {
      $e= libxml_get_last_error();
      throw new XMLFormatException(trim($e->message), $e->code, $source, $e->line, $e->column);
    }
    return self::recurse(new XPath($doc), $doc->documentElement, \lang\XPClass::forName($classname), []);
  }

  /**
   * Unmarshal XML to an object
   *
   * @param   xml.parser.InputSource source
   * @param   string classname
   * @param   [:var] inject
   * @return  object
   * @throws  lang.ClassNotFoundException
   * @throws  xml.XMLFormatException
   * @throws  lang.reflect.TargetInvocationException
   * @throws  lang.IllegalArgumentException
   */
  public function unmarshalFrom(InputSource $input, $classname, $inject= []) {
    libxml_clear_errors();
    $doc= new \DOMDocument();
    if (!$doc->load(Streams::readableUri($input->getStream()))) {
      $e= libxml_get_last_error();
      throw new XMLFormatException(trim($e->message), $e->code, $input->getSource(), $e->line, $e->column);
    }

    $xpath= new XPath($doc);

    // Class factory based on tag name, reference to a static method which is called with 
    // the class name and returns an XPClass instance.
    $class= \lang\XPClass::forName($classname);
    if ($class->hasAnnotation('xmlmapping', 'factory')) {
      if ($class->hasAnnotation('xmlmapping', 'pass')) {
        $factoryArgs= [];
        foreach ($class->getAnnotation('xmlmapping', 'pass') as $pass) {
          $factoryArgs[]= self::contentOf($xpath->query($pass, $doc->documentElement));
        }
      } else {
        $factoryArgs= [$doc->documentElement->nodeName];
      }
      $class= $class->getMethod($class->getAnnotation('xmlmapping', 'factory'))->invoke(null, $factoryArgs);
    }

    return self::recurse($xpath, $doc->documentElement, $class, $inject);
  }
}