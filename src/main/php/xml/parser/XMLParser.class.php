<?php
/* This class is part of the XP framework
 *
 * $Id$
 */

  uses('xml.XMLFormatException');
  
  /**
   * XML Parser
   *
   * Example:
   * <code>
   *   uses('xml.parser.XMLParser');
   *
   *   $parser= new XMLParser();
   *   $parser->setCallback(...);
   *   try {
   *     $parser->parse($xml);
   *   } catch (XMLFormatException $e) {
   *     $e->printStackTrace();
   *     exit(-1);
   *   }
   * </code>
   *
   * @ext     xml
   * @test    xp://net.xp_framework.unittest.xml.StreamXMLParserTest
   * @test    xp://net.xp_framework.unittest.xml.StringXMLParserTest
   */
  class XMLParser extends Object {
    public
      $encoding     = '',
      $callback     = NULL;

    /**
     * Constructor
     *
     * @param   string encoding defaults to XP default encoding
     */
    public function __construct($encoding= xp::ENCODING) {
      $this->encoding= $encoding;
    }

    /**
     * Set callback
     *
     * @param   xml.parser.ParserCallback callback
     */
    public function setCallback($callback) {
      $this->callback= $callback;
    }

    /**
     * Set callback
     *
     * @param   xml.parser.ParserCallback callback
     * @return  xml.parser.XMLParser this
     */
    public function withCallback($callback) {
      $this->callback= $callback;
      return $this;
    }

    /**
     * Set Encoding
     *
     * @param   string encoding
     */
    public function setEncoding($encoding) {
      $this->encoding= $encoding;
    }

    /**
     * Set Encoding
     *
     * @param   string encoding
     * @return  xml.parser.XMLParser this
     */
    public function withEncoding($encoding) {
      $this->encoding= $encoding;
      return $this;
    }

    /**
     * Get Encoding
     *
     * @return  string
     */
    public function getEncoding() {
      return $this->encoding;
    }
    
    /**
     * Parse XML data
     *
     * @param   var data either a string or an xml.parser.InputSource
     * @param   string source default NULL optional source identifier, will show up in exception
     * @return  bool
     * @throws  xml.XMLFormatException in case the data could not be parsed
     * @throws  lang.NullPointerException in case a parser could not be created
     */
    public function parse($data, $source= NULL) {
      if ($parser= xml_parser_create('')) {
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, FALSE);
        if (NULL === $this->encoding) {
          $this->encoding= xml_parser_get_option($parser, XML_OPTION_TARGET_ENCODING);
        } else {
          xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, $this->encoding);
        }
        
        // Register callback
        if ($this->callback) {
          xml_set_object($parser, $this->callback);
          $this->callback->onBegin($this);
          xml_set_element_handler($parser, 'onStartElement', 'onEndElement');
          xml_set_character_data_handler($parser, 'onCData');
          xml_set_default_handler($parser, 'onDefault');
        }
        
        // Parse streams while reading data
        if ($data instanceof InputSource) {
          $stream= $data->getStream();
          $source || $source= $data->getSource();
          do {
            if ($stream->available()) {
              $r= xml_parse($parser, $stream->read(), FALSE);
            } else {
              $r= xml_parse($parser, '', TRUE);
              break;
            }
          } while ($r);
        } else {
          $r= xml_parse($parser, $data, TRUE);
        }
        
        // Check for errors
        if (!$r) {
          $type= xml_get_error_code($parser);
          $line= xml_get_current_line_number($parser);
          $column= xml_get_current_column_number($parser);
          $message= xml_error_string($type);
          xml_parser_free($parser);
          libxml_clear_errors();

          $e= new XMLFormatException($message, $type, $source, $line, $column);
          $this->callback && $this->callback->onError($this, $e);
          throw $e;
        }
        xml_parser_free($parser);
        $r= TRUE;
        $this->callback && $r= $this->callback->onFinish($this);
        return $r;
      }

      throw new NullPointerException('Could not create parser');
    }
  }
?>
