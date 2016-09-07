<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Mreporting plugin for GLPI
 Copyright (C) 2003-2011 by the mreporting Development Team.

 https://forge.indepnet.net/projects/mreporting
 -------------------------------------------------------------------------

 LICENSE

 This file is part of mreporting.

 mreporting is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 mreporting is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with mreporting. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

class PluginMreportingBaseclass {

   protected $sql_date,
             $sql_date_create,
             $sql_date_solve,
             $sql_date_closed,
             $filters,
             $where_entities,
             $sql_type,
             $sql_itilcat,
             $sql_user_assign,
             $sql_group_assign,
             $sql_group_request;

   function __construct($config = array()) {
      global $DB, $LANG;

      //force MySQL DATE_FORMAT in user locale
      $query = "SET lc_time_names = '".$_SESSION['glpilanguage']."'";
      $DB->query($query);

      if (empty($config)) {
         return true;
      }

      $this->filters = array(
         'open' => array(
            'label' => $LANG['plugin_mreporting']['Helpdeskplus']['opened'],
            'status' => array(
               CommonITILObject::INCOMING => _x('status', 'New'),
               CommonITILObject::ASSIGNED => _x('status', 'Processing (assigned)'),
               CommonITILObject::PLANNED  => _x('status', 'Processing (planned)'),
               CommonITILObject::WAITING  => __('Pending')
            )
         ),
         'close' => array(
            'label' => _x('status', 'Closed'),
            'status' => array(
               CommonITILObject::SOLVED => _x('status', 'Solved'),
               CommonITILObject::CLOSED => _x('status', 'Closed')
            )
         )
      );
      $this->status = array(CommonITILObject::INCOMING,
                            CommonITILObject::ASSIGNED,
                            CommonITILObject::PLANNED,
                            CommonITILObject::WAITING,
                            CommonITILObject::SOLVED,
                            CommonITILObject::CLOSED);

      if (isset( $_SESSION['glpiactiveentities'])) {
         $this->where_entities = "'".implode("', '", $_SESSION['glpiactiveentities'])."'";
      } else { // maybe cron mode
         $entities = array();
         $entity = new Entity;
         $found_entities = $entity->find();
         foreach($found_entities as $entities_id => $current_entity) {
            $entities[] = $entities_id;
         }
         $this->where_entities = "'".implode("', '", $entities)."'";
      }

      // init default value for status selector
      if (!isset($_SESSION['mreporting_values']['status_1'])) {
          $_SESSION['mreporting_values']['status_1']
        = $_SESSION['mreporting_values']['status_2']
        = $_SESSION['mreporting_values']['status_3']
        = $_SESSION['mreporting_values']['status_4'] = 1;
          $_SESSION['mreporting_values']['status_5']
        = $_SESSION['mreporting_values']['status_6'] = 0;
      }

      if (!isset($_SESSION['mreporting_values']['show_new'])) {
        $_SESSION['mreporting_values']['show_new']      = 1;
        $_SESSION['mreporting_values']['show_solved']   = 1;
        $_SESSION['mreporting_values']['show_backlog']  = 1;
        $_SESSION['mreporting_values']['show_closed']   = 1;
      }

      if (!isset($_SESSION['mreporting_values']['period']))  {
         $_SESSION['mreporting_values']['period'] = 'month';
      }

      $period = $_SESSION['mreporting_values']['period'];

      // Check if we're in dashboard
      if (PluginMreportingDashboard::isDashboard($config)) {

        $widget_config = $_SESSION['mreporting_values_dashboard'][$config['widget_id']];

        if (isset($widget_config['period'])) {
          $period = $widget_config['period'];
        }

        if (isset($widget_config['type']) && $widget_config['type'] > 0) {
          $this->sql_type = 'glpi_tickets.type = '.$widget_config['type'];
        }

        if (isset($widget_config['itilcategories_id']) && $widget_config['itilcategories_id'] > 0) {
          $this->sql_itilcat = 'glpi_tickets.itilcategories_id = '.
                                $widget_config['itilcategories_id'];
        }

        if (isset($widget_config['users_assign_id']) && $widget_config['users_assign_id'] > 0) {
          $this->sql_user_assign = 'tu.users_id = '.$widget_config['users_assign_id'];
        }

        if (isset($widget_config['groups_assign_id'])) {
          $this->sql_group_assign = 'gt.groups_id IN ('.
                                    implode(', ', $widget_config['groups_assign_id']).
                                    ')';
        }

        if (isset($widget_config['groups_request_id'])) {
          $this->sql_group_request = 'gtr.groups_id IN ('.
                                     implode(', ', $widget_config['groups_request_id']).
                                     ')';
        }

      }

      switch($period) {
         case 'day':
            $this->period_sort = '%y%m%d';
            $this->period_sort_php = $this->period_sort = '%y%m%d';
            $this->period_datetime = '%Y-%m-%d 23:59:59';
            $this->period_label = '%d %b';
            $this->period_interval = 'DAY';
            $this->sql_list_date = "DISTINCT DATE_FORMAT(`date` , '{$this->period_datetime}') as period_l";
            break;
         case 'week':
            $this->period_sort = '%x%v';
            $this->period_sort_php = '%Y%V';
            $this->period_datetime = "%Y-%m-%d 23:59:59";
            $this->period_label = 'S%v %x';
            $this->period_interval = 'WEEK';
            $this->sql_list_date = "DISTINCT DATE_FORMAT(`date` - INTERVAL (WEEKDAY(`date`)) DAY, '{$this->period_datetime}') as period_l";
            break;
         case 'month':
            $this->period_sort = '%y%m';
            $this->period_sort_php = $this->period_sort = '%y%m';
            $this->period_datetime = '%Y-%m-01 23:59:59';
            $this->period_label = '%b %Y';
            $this->period_interval = 'MONTH';
            $this->sql_list_date = "DISTINCT CONCAT(LAST_DAY(DATE_FORMAT(`date` , '{$this->period_datetime}')), ' 23:59:59') as period_l";
            break;
         case 'year':
            $this->period_sort = '%Y';
            $this->period_sort_php = $this->period_sort = '%Y';
            $this->period_datetime = '%Y-12-31 23:59:59';
            $this->period_label = '%Y';
            $this->period_interval = 'YEAR';
            $this->sql_list_date = "DISTINCT DATE_FORMAT(`date` , '{$this->period_datetime}') as period_l";
            break;
         default :
            $this->period_sort = '%y%u';
            $this->period_label = 'S-%u %y';
            break;
      }

      $this->sql_date_create = PluginMreportingCommon::getSQLDate("glpi_tickets.date",
                                                                  $config['delay'],
                                                                  $config['randname']);
      $this->sql_date_solve =  PluginMreportingCommon::getSQLDate("glpi_tickets.solvedate",
                                                                  $config['delay'],
                                                                  $config['randname']);
      $this->sql_date_closed = PluginMreportingCommon::getSQLDate("glpi_tickets.closedate",
                                                                  $config['delay'],
                                                                  $config['randname']);

   }
}
