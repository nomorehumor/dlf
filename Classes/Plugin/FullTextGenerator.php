<?php

namespace Kitodo\Dlf\Plugin;
use DOMdocument;
use DOMattr;
use TYPO3\CMS\Core\Log\LogLevel;

class FullTextGenerator {

  const xmls_dir = "fileadmin/test_xmls/";
  const temp_text_dir = "fileadmin/_temp_/test_texts/";
  const temp_images_dir = "fileadmin/_temp_/test_images/";
  
  static function getDocLocalId($doc) {
    return $doc->toplevelId;
  }

  static function checkLocal($doc, $page_num) {
    $doc_id = FullTextGenerator::getDocLocalId($doc);
    return file_exists(self::xmls_dir . "{$doc_id}_$page_num.xml");
  }

  /*
  Main Method for cration of new Fulltext for a page
  Saves a XML file with fulltext
  */
  static function createFullText($doc, $image, $page_num) {
    $doc_id = FullTextGenerator::getDocLocalId($doc);
    $text = FullTextGenerator::executeOCR($image);
    FulltextGenerator::saveXML($doc_id, $text, $page_num);
  }

  # @return scanned text
  static function executeOCR($image) {
    $page_id = basename($image["url"]);
    $image_path = self::temp_images_dir . "{$page_id}_$";
    file_put_contents($image_path, file_get_contents($image["url"]));
    $text_path = self::temp_text_dir . $page_id; 
    $ocr_shell_command = "tesseract $image_path $text_path -l deu";
    exec($ocr_shell_command);
    // Removing unacceptable characters
    $text = preg_replace('/[\x00-\x1F\x7F]/', '', file_get_contents($text_path . ".txt"));
    return $text;
  }

  static private function deleteTemporalFiles() {
    
  }

  static function getDocLocalPath($doc, $page_num) {
    if (FullTextGenerator::checkLocal($doc, $page_num)) {
      $doc_id = FullTextGenerator::getDocLocalId($doc);
      $logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
      $logger->log(LogLevel::WARNING, "Returning local .xml");

      return "fileadmin/test_xmls/{$doc_id}_$page_num.xml";
    } else return ""; 
  }

  static function saveXML($doc_id, $text, $page_num) {
    $dom = new DOMdocument();

    $root = $dom->createelement("alto");
    $xmlns = new DOMattr("xmlns", "http://www.loc.gov/standards/alto/ns-v2#");
    $xmlns_xlink = new DOMattr("xmlns:xlink", "http://www.w3.org/1999/xlink");
    $xmlns_xsi = new DOMattr("xmlns:xlink", "http://www.w3.org/2001/XMLSchema-instance");

    $layout = $dom->createelement("Layout");
    $page = $dom->createelement("Page");
    $print_space = $dom->createelement("PrintSpace");
    $textblock = $dom->createelement("TextBlock");

  //foreach($text as $line) {
    $textline = $dom->createelement("TextLine");
    $string = $dom->createelement("String");
    $content_attr = new DOMattr("CONTENT", $text);
    $string->setattributenode($content_attr);
    $textline->appendchild($string);
    $textblock->appendchild($textline);
  //}
    
    $print_space->appendchild($textblock);
    $page->appendchild($print_space);
    $layout->appendchild($page);
    $root->appendchild($layout);
    $dom->appendchild($root);
    $dom->formatOutput = true;
    $dom->save(self::xmls_dir . "{$doc_id}_$page_num.xml");
  }
}
?>
