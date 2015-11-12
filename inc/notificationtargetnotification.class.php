<?php
/**
  * Special class
  **/
class PluginMreportingNotificationTargetNotification extends NotificationTarget {

   var $additionalData;

   function getEvents() {
      return array('sendReporting' => __('More Reporting', 'mreporting'));
   }
   
   function getTags() {
      $this->addTagToList(array('tag'   => 'mreporting.file_url',
                                'label' => __('Link'),
                                'value' => true));

      asort($this->tag_descriptions);
   }

   function getDatasForTemplate($event, $options = array()) {
      global $CFG_GLPI;

      //Note : no call to parent, no others markups (in notification)

      switch ($event) {
         case 'sendReporting':
            $file_name = $this->_buildPDF(mt_rand().'_', $options);

            $this->datas['##lang.mreporting.file_url##'] = __('Link');
            $this->datas['##mreporting.file_url##']      = $CFG_GLPI['url_base'].
                                                            "/index.php?redirect=plugin_mreporting_$file_name";
            
            $this->additionalData['attachment']['path'] = GLPI_PLUGIN_DOC_DIR."/mreporting/notifications/".$file_name;
            $this->additionalData['attachment']['name'] = $file_name;
            break;
      }
   }

   function showGraph($opt, $export = false, $mreporting_values = array()) {

      if (!isset($opt['hide_title'])) {
         self::title($opt);
         $opt['hide_title'] = false;
      }

      //check the format display charts configured in glpi
      $opt = $mreporting_values; //$this->initParams($opt, $export);
      $config = PluginMreportingConfig::initConfigParams($_REQUEST['f_name'], "PluginMreporting".$_REQUEST['short_classname']);

      //generate default date
      if (!isset($_SESSION['mreporting_values']['date1'.$config['randname']])) {
         $_SESSION['mreporting_values']['date1'.$config['randname']] = strftime("%Y-%m-%d",
            time() - ($config['delay'] * 24 * 60 * 60));
      }
      if (!isset($_SESSION['mreporting_values']['date2'.$config['randname']])) {
         $_SESSION['mreporting_values']['date2'.$config['randname']] = strftime("%Y-%m-%d");
      }

      //self::getSelectorValuesByUser();

      $config = array_merge($config, $mreporting_values);

      //dynamic instanciation of class passed by 'short_classname' GET parameter
      $classname = 'PluginMreporting'.$_REQUEST['short_classname'];

      if (!class_exists($classname)) {
         return '';
      }

      //For exemple for reportHgbarOpenedTicketNumberByCategory : $_POST['status_1'] = '1';
      foreach ($mreporting_values as $key => $value) {
         $_POST[$key] = $value;
      }

      //dynamic call of method passed by 'f_name' GET parameter with previously instancied class
      $obj = new $classname($config);
      //TODO : $datas ne tient pas compte des filtres !
      $datas = $obj->$_REQUEST['f_name'](array_merge($config, $_REQUEST));

      global $LANG;

      //show graph (pgrah type determined by first entry of explode of camelcase of function name
      $title_func = $LANG['plugin_mreporting'][$_REQUEST['short_classname']][$_REQUEST['f_name']]['title'];
      
      if (isset($LANG['plugin_mreporting'][$_REQUEST['short_classname']][$_REQUEST['f_name']]['desc'])) {
         $des_func = $LANG['plugin_mreporting'][$_REQUEST['short_classname']][$_REQUEST['f_name']]['desc'];
      } else {
         $des_func = "";
      }

      $opt['class'] = $classname;
      $opt['withdata'] = 1;
      $params = array("raw_datas"   => $datas,
                       "title"      => $title_func,
                       "desc"       => $des_func,
                       "export"     => $export,
                       "opt"        => array_merge($opt, $_REQUEST));

      $graph = new PluginMreportingGraphpng();
      return $graph->{'show'.$_REQUEST['gtype']}($params, $_REQUEST['hide_title'], false);
   }


   /**
    * Generate a PDF file (with mreporting reports)
    *
    * @return string hash Name of the created file
    */
   private function _buildPDF($user_name = '', $options = array()) {
      global $CFG_GLPI, $DB, $LANG;

      $dir = GLPI_PLUGIN_DOC_DIR.'/mreporting/notifications';

      if (!is_dir($dir)) {
         return false;
      }

      require_once GLPI_ROOT.'/plugins/mreporting/lib/tcpdf/tcpdf.php';

      // For have months in French (example : 'November' -> 'Novembre')
      setlocale(LC_TIME, 'fr_FR.utf8', 'fra');

      ini_set('memory_limit', '256M');
      set_time_limit(300);

      $CFG_GLPI['default_graphtype'] = "png";

      $images = array();

      $result = $DB->query('SELECT *
                           FROM glpi_plugin_mreporting_configs
                           WHERE is_active = 1 
                              AND id = '.$options['notification_id']['report']);

      $graphs = array();
      while ($graph = $result->fetch_array()) {
         $type = preg_split('/(?<=\\w)(?=[A-Z])/', $graph['name']);
         
         $graphs[] = array(
            'class'     => substr($graph['classname'], 16),
            'classname' => $graph['classname'],
            'method'    => $graph['name'],
            'type'      => $type[1],
            'start'     => date('Y-m-d', strtotime(date('Y-m-d 00:00:00').
                           ' -'.$graph['default_delay'].' day')),
            'end'       => date('Y-m-d', strtotime(date('Y-m-d 00:00:00').' -1 day')),
         );
      }

      if (Session::isCron()) {
         $entities = getSonsOf("glpi_entities", 0);
         $_SESSION['glpiactiveentities_string'] = "'".implode("', '", $entities)."'";
         $_SESSION['glpiparententities'] = array();
      }

      foreach ($graphs as $graph) {

         // Get values from criterias
         $values = PluginMreportingCriterias::getSelectorValuesByNotification_id($options['notification_id']['id']);

         if (isset($_SESSION['mreporting_values'])) { //never ?
            $_SESSION['mreporting_values'] = array_merge($_SESSION['mreporting_values'], $values);
         } else {
            $_SESSION['mreporting_values'] = $values;
         }

         $_REQUEST = array('switchto'        => 'png',
                  'short_classname' => $graph['class'],
                  'f_name'          => $graph['method'],
                  'gtype'           => $graph['type'],
                  'date1PluginMreporting'.$graph['class'].$graph['method'] => $graph['start'],
                  'date2PluginMreporting'.$graph['class'].$graph['method'] => $graph['end'],
                  'randname'        => 'PluginMreporting'.$graph['class'].$graph['method'],
                  'hide_title'      => false);

         ob_start();
         $common = new self();
         $common->showGraph($_REQUEST, false, $values);
         $content = ob_get_clean();

         preg_match_all('/<img .*?(?=src)src=\'([^\']+)\'/si', $content, $matches);

         // find image content
         if (!isset($matches[1][2])) {
            continue;
         }
         $image_base64 = $matches[1][2];
         if (strpos($image_base64, 'data:image/png;base64,') === false) {
            if (isset($matches[1][3])) {
               $image_base64 = $matches[1][3];
            }
         }
         if (strpos($image_base64, 'data:image/png;base64,') === false) {
            continue;
         }

         // clean image
         $image_base64  = str_replace('data:image/png;base64,', '', $image_base64);

         $image         = imagecreatefromstring(base64_decode($image_base64));
         $image_width   = imagesx($image);
         $image_height  = imagesy($image);

         $format = '%e';
         if (strftime('%Y', strtotime($graph['start'])) != strftime('%Y', strtotime($graph['end']))) {
            $format .= ' %B %Y';
         } elseif(strftime('%B', strtotime($graph['start'])) != strftime('%B', strtotime($graph['end']))) {
            $format .= ' %B';
         }

         $image_title  = $LANG['plugin_mreporting'][$graph['class']][$graph['method']]['title'];
         $image_title .= " du ".strftime($format, strtotime($graph['start']));
         $image_title .= " au ".strftime('%e %B %Y', strtotime($graph['end']));

         array_push($images, array('title'  => $image_title,
                                   'base64' => $image_base64,
                                   'width'  => $image_width,
                                   'height' => $image_height));
      }

      $file_name = 'glpi_report_'.$user_name.date('d-m-Y').'.pdf';

      $pdf = new PluginMreportingPdf();
      $pdf->Init();
      $pdf->Content($images);
      $pdf->Output($dir.'/'.$file_name, 'F');

      // Return the generated filename
      return $file_name;
   }

}
