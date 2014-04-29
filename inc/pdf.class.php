<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginMreportingPdf extends TCPDF {

   /**
    * Create PDF header and initialize presentation
    */
   function Init() {
      global $LANG, $CFG_GLPI;
      $title   = $LANG['plugin_mreporting']['notification_subject'];
      $creator = $LANG['plugin_mreporting']['notification_creator'];
      $version = plugin_version_mreporting();
      $author  = $CFG_GLPI["version"].' - ' . $version['name'].' v' . $version['version'];

      $this->SetCreator($creator);
      $this->SetAuthor($author);
      $this->SetTitle($title);
      $this->SetFontSize(10);
      $this->SetMargins(20, 25);
      $this->SetAutoPageBreak(true);
      $this->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
      $this->SetHeaderMargin(PDF_MARGIN_HEADER);
      $this->AddPage();
   }

   /**
    * Insert content and graphs
    *
    * @param array $images Array of reports
    */
   function Content($images) {
      $images_lengh = sizeof($images);
      $i = 0;
      foreach($images as $image) {
         $i++;
         $file = '@' . base64_decode($image['base64']);
         $w    = 210 - PDF_MARGIN_LEFT * 2;

         if ($image['width'] == 0) continue;

         $h    = floor(($image['height'] * $w) / $image['width']);
         $this->Image($file, '', '',$w ,$h);
         $this->Ln($h);

         $this->writeHTMLCell('', '', '', '', $image['title'], 0, 1, false, true, 'C');
         if($i < $images_lengh) $this->AddPage();
      }
   }

   /**
    * Create the PDF footer
    */
   function Footer() {
      $this->SetY(-15);
      $this->SetFontSize(8);
      $this->writeHTMLCell('' , '', '', '', date('Y-m-d H:i:s'), 0, 0, false, true, 'R');
   }
}
