<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 addressing plugin for GLPI
 Copyright (C) 2009-2016 by the addressing Development Team.

 https://github.com/pluginsGLPI/addressing
 -------------------------------------------------------------------------

 LICENSE

 This file is part of addressing.

 addressing is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 addressing is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with addressing. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginAddressingAddressing
 */
class PluginAddressingAddressing extends CommonDBTM {

   static $rightname = "plugin_addressing";

   static $types = ['Computer', 'NetworkEquipment', 'Peripheral', 'Phone', 'Printer'];

   static function getTypeName($nb = 0) {

      return _n('IP Adressing', 'IP Adressing', $nb, 'addressing');
   }

   public function rawSearchOptions() {

      $tab[] = [
         'id'                 => 'common',
         'name'               => self::getTypeName(2)
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => 'glpi_networks',
         'field'              => 'name',
         'name'               => _n('Network', 'Networks', 2),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'use_ping',
         'name'               => __('Ping free Ip', 'addressing'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => 'glpi_locations',
         'field'              => 'name',
         'name'               => __('Location'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => 'glpi_fqdns',
         'field'              => 'name',
         'name'               => FQDN::getTypeName(1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '30',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '1000',
         'table'              => $this->getTable(),
         'field'              => 'begin_ip',
         'name'               => __('First IP', 'addressing'),
         'nosearch'           => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '1001',
         'table'              => $this->getTable(),
         'field'              => 'end_ip',
         'name'               => __('Last IP', 'addressing'),
         'nosearch'           => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false
      ];

      return $tab;
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('PluginAddressingFilter', $ong, $options);
      return $ong;
   }


   /**
    * @return string
    */
   function getTitle() {

      return __('Report for the IP Range', 'addressing')." ".$this->fields["begin_ip"]." ".
             __('to')." ".$this->fields["end_ip"];
   }


   /**
    * @param $entity
    */
   function dropdownSubnet($entity) {
      global $DB;

      $dbu = new DbUtils();
      $sql = "SELECT DISTINCT `completename`
              FROM `glpi_ipnetworks`" .
             $dbu->getEntitiesRestrictRequest(" WHERE ", "glpi_ipnetworks", "entities_id", $entity);
      $networkList = [0 => Dropdown::EMPTY_VALUE];
      foreach ($DB->request($sql) as $network) {
         $networkList += [$network["completename"] => $network["completename"]];
      }
      $rand = mt_rand();
      $name = "_subnet";
      Dropdown::ShowFromArray($name, $networkList, ['rand' => $rand,
                                                               'on_change' => 'plugaddr_ChangeList("dropdown_'.$name.$rand.'");']);
   }


   function showForm ($ID, $options = []) {

      Html::requireJs("addressing");
      $this->initForm($ID, $options);

      $options['formoptions']
            = "onSubmit='return plugaddr_Check(\"".__('Invalid data !!', 'addressing')."\")'";
      $this->showFormHeader($options);

      $PluginAddressingConfig = new PluginAddressingConfig();
      $PluginAddressingConfig->getFromDB('1');

      echo "<tr class='tab_bg_1'>";

      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";

      if ($PluginAddressingConfig->fields["alloted_ip"]) {
         echo "<td>".__('Assigned IP', 'addressing')."</td><td>";
         Dropdown::showYesNo('alloted_ip', $this->fields["alloted_ip"]);
         echo "</td>";
      } else {
         echo "<td>";
         echo Html::hidden('alloted_ip', ['value' => 0]);
         echo "</td><td></td>";
      }

      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Location')."</td>";
      echo "<td>";
      Dropdown::show('Location', ['name'   => "locations_id",
                                       'value'  => $this->fields["locations_id"],
                                       'entity' => $this->fields['entities_id']]);
      echo "</td>";

      if ($PluginAddressingConfig->fields["free_ip"]) {
         echo "<td>".__('Free Ip', 'addressing')."</td><td>";
         Dropdown::showYesNo('free_ip', $this->fields["free_ip"]);
         echo "</td>";
      } else {
         echo "<td>";
         echo Html::hidden('free_ip', ['value' => 0]);
         echo "</td><td></td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".FQDN::getTypeName(1)."</td>";
      echo "<td>";
      Dropdown::show('FQDN', ['name'  => "fqdns_id",
                                   'value' => $this->fields["fqdns_id"],
                                   'entity'=> $this->fields['entities_id']]);
      echo "</td>";

      if ($PluginAddressingConfig->fields["double_ip"]) {
         echo "<td>".__('Same IP', 'addressing')."</td><td>";
         Dropdown::showYesNo('double_ip', $this->fields["double_ip"]);
         echo "</td>";
      } else {
         echo "<td>";
         echo Html::hidden('double_ip', ['value' => 0]);
         echo "</td><td></td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Select the network', 'addressing')."</td>";
      echo "<td>";
      Dropdown::show('Network', ['name'  => "networks_id",
                                      'value' => $this->fields["networks_id"]]);
      echo "</td>";

      if ($PluginAddressingConfig->fields["reserved_ip"]) {
         echo "<td>".__('Reserved IP', 'addressing')."</td><td>";
         Dropdown::showYesNo('reserved_ip', $this->fields["reserved_ip"]);
         echo "</td>";
      } else {
         echo "<td>";
         echo Html::hidden('reserved_ip', ['value' => 0]);
         echo "</td><td></td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Detected subnet list', 'addressing')."</td>";
      echo "<td>";
      $this->dropdownSubnet($ID>0 ? $this->fields["entities_id"] : $_SESSION["glpiactive_entity"]);
      echo "</td>";

      if ($PluginAddressingConfig->fields["use_ping"]) {
         echo "<td>".__('Ping free Ip', 'addressing')."</td><td>";
         Dropdown::showYesNo('use_ping', $this->fields["use_ping"]);
         echo "</td>";
      } else {
         echo "<td>";
         echo Html::hidden('use_ping', ['value' => 0]);
         echo "</td><td></td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('First IP', 'addressing')."</td>"; // Subnet
      echo "<td>";
      echo "<input type='text' id='plugaddr_ipdeb0' value='' name='_ipdeb0' size='3' ".
           "onChange='plugaddr_ChangeNumber(\"".__('Invalid data !!', 'addressing')."\");'>.";
      echo "<input type='text' id='plugaddr_ipdeb1' value='' name='_ipdeb1' size='3' ".
           "onChange='plugaddr_ChangeNumber(\"".__('Invalid data !!', 'addressing')."\");'>.";
      echo "<input type='text' id='plugaddr_ipdeb2' value='' name='_ipdeb2' size='3' ".
           "onChange='plugaddr_ChangeNumber(\"".__('Invalid data !!', 'addressing')."\");'>.";
      echo "<input type='text' id='plugaddr_ipdeb3' value='' name='_ipdeb3' size='3' ".
           "onChange='plugaddr_ChangeNumber(\"".__('Invalid data !!', 'addressing')."\");'>";
      echo "</td>";
      echo "<td></td>";
      echo "<td></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Last IP', 'addressing')."</td>"; // Mask
      echo "<td>";
      echo "<input type='text' id='plugaddr_ipfin0' value='' name='_ipfin0' size='3' ".
           "onChange='plugaddr_ChangeNumber(\"".__('Invalid data !!', 'addressing')."\");'>.";
      echo "<input type='text' id='plugaddr_ipfin1' value='' name='_ipfin1' size='3' ".
           "onChange='plugaddr_ChangeNumber(\"".__('Invalid data !!', 'addressing')."\");'>.";
      echo "<input type='text' id='plugaddr_ipfin2' value='' name='_ipfin2' size='3' ".
           "onChange='plugaddr_ChangeNumber(\"".__('Invalid data !!', 'addressing')."\");'>.";
      echo "<input type='text' id='plugaddr_ipfin3' value='' name='_ipfin3' size='3' ".
           "onChange='plugaddr_ChangeNumber(\"".__('Invalid data !!', 'addressing')."\");'>";
      echo "</td>";
      echo "<td></td>";
      echo "<td></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Report for the IP Range', 'addressing')."</td>"; // Mask
      echo "<td>";
      echo "<input type='hidden' id='plugaddr_ipdeb' value='".$this->fields["begin_ip"]."' name='begin_ip'>";
      echo "<input type='hidden' id='plugaddr_ipfin' value='".$this->fields["end_ip"]."' name='end_ip'>";
      echo "<div id='plugaddr_range'>-</div>";
      if ($ID > 0) {
         $js = "plugaddr_Init(\"".__('Invalid data !!', 'addressing')."\");";
         echo Html::scriptBlock('$(document).ready(function() {'.$js.'});');
      }
      echo "</td>";
      echo "<td></td>";
      echo "<td></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Comments')."</td>";
      echo "<td class='center' colspan='3'>".
           "<textarea cols='125' rows='3' name='comment'>".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   /*
   function linkToExport($ID) {

      echo "<div class='center'>";
      echo "<a href='./report.form.php?id=".$ID."&export=true'>".__('Export')."</a>";
      echo "</div>";
   }*/


   /**
    * @param       $start
    * @param array $params
    *
    * @return array
    * @throws \GlpitestSQLError
    */
   function compute($start, $params = []) {
      global $DB;

      foreach ($params as $key => $val) {
         if (isset($params[$key])) {
            $$key = $params[$key];
         }
      }

      if (isset($_GET["export"])) {
         if (isset($start)) {
            $ipdeb += $start;
         }
         if ($ipdeb > $ipfin) {
            $ipdeb = $ipfin;
         }
         if ($ipdeb+$_SESSION["glpilist_limit"] <= $ipfin) {
            $ipfin = $ipdeb+$_SESSION["glpilist_limit"]-1;
         }
      }

      $result = [];
      for ($ip=$ipdeb; $ip<=$ipfin; $ip++) {
         $result["IP".$ip] = [];
      }

      $sql = "SELECT `port`.`id`,
                     'NetworkEquipment' AS itemtype,
                     `dev`.`id` AS on_device,
                     `dev`.`name` AS dname,
                     '' AS pname,
                     `glpi_ipaddresses`.`name` as ip,
                     `port`.`mac`,
                     `dev`.`users_id`,
                     INET_ATON(`glpi_ipaddresses`.`name`) AS ipnum
               FROM `glpi_networkports` port
               LEFT JOIN `glpi_networkequipments` dev ON (`port`.`items_id` = `dev`.`id`
                     AND `port`.`itemtype` = 'NetworkEquipment')
               LEFT JOIN `glpi_networknames` ON (`port`.`id` =  `glpi_networknames`.`items_id`)
               LEFT JOIN `glpi_ipaddresses` ON (`glpi_ipaddresses`.`items_id` = `glpi_networknames`.`id`)
               WHERE INET_ATON(`glpi_ipaddresses`.`name`) >= '$ipdeb'
                     AND INET_ATON(`glpi_ipaddresses`.`name`) <= '$ipfin'
                     AND `dev`.`is_deleted` = 0
                     AND `dev`.`is_template` = 0 ";
      $dbu = new DbUtils();
      if (isset($entities)) {
         $sql .= $dbu->getSonsAndAncestorsOf('glpi_entities', $entities);
      } else {
         $sql .= $dbu->getEntitiesRestrictRequest(" AND ", "dev");
      }
      if (isset($type_filter)) {
         $sql .= " AND `glpi_ipaddresses`.`mainitemtype` = '" . $type_filter . "'";
      }

      if ($this->fields["networks_id"]) {
         $sql .= " AND `dev`.`networks_id` = ".$this->fields["networks_id"];
      }

      //$ntypes = $CFG_GLPI["networkport_types"];
      //foreach ($ntypes as $k => $v) {
      //   if ($v == 'PluginFusioninventoryUnknownDevice') {
      //      unset($ntypes[$k]);
      //   }
      //}
      if (isset($type_filter)) {
         $types = [$type_filter];
      } else {
         $types = self::getTypes();
      }
      $dbu = new DbUtils();
      foreach ($types as $type) {
         $itemtable = $dbu->getTableForItemType($type);
         if (!($item = $dbu->getItemForItemtype($type))) {
            continue;
         }
            $sql .= " UNION SELECT `port`.`id`,
                                    '" . $type . "' AS `itemtype`,
                                    `port`.`items_id`,
                                   `dev`.`name` AS dname,
                                   `port`.`name` AS pname,
                                   `glpi_ipaddresses`.`name` as ip,
                                   `port`.`mac`";

         if ($type == 'PluginFusioninventoryUnknownDevice') {
            $sql .= " ,0 AS `users_id` ";
         } else {
            $sql .= " ,`dev`.`users_id` ";
         }
            $sql .= " , INET_ATON(`glpi_ipaddresses`.`name`) AS ipnum ";
            $sql .= " FROM `glpi_networkports` port
                           LEFT JOIN `" . $itemtable . "` dev ON (`port`.`items_id` = `dev`.`id`
                                 AND `port`.`itemtype` = '" . $type . "')
                           LEFT JOIN `glpi_networknames` ON (`port`.`id` =  `glpi_networknames`.`items_id`)
                           LEFT JOIN `glpi_ipaddresses` ON (`glpi_ipaddresses`.`items_id` = `glpi_networknames`.`id`)
                           WHERE INET_ATON(`glpi_ipaddresses`.`name`) >= '$ipdeb'
                                 AND INET_ATON(`glpi_ipaddresses`.`name`) <= '$ipfin'";
         $dbu = new DbUtils();
         if (isset($entities)) {
            $sql .= $dbu->getSonsAndAncestorsOf('glpi_entities', $entities);
         } else {
            $sql .= $dbu->getEntitiesRestrictRequest(" AND ", "dev");
         }
         if (isset($type_filter)) {
            $sql .= " AND `glpi_ipaddresses`.`mainitemtype` = '" . $type_filter . "'";
         }

         if ($item->maybeDeleted()) {
            $sql.=" AND `dev`.`is_deleted` = '0'";
         }

         if ($item->maybeTemplate()) {
            $sql.=" AND `dev`.`is_template` = '0'";
         }

         if ($this->fields["networks_id"]
                  && $DB->fieldExists($type::getTable(), 'networks_id')) {
            $sql .= " AND `dev`.`networks_id`= ".$this->fields["networks_id"];
         }
      }
      $res = $DB->query($sql);
      if ($res) {
         while ($row=$DB->fetch_assoc($res)) {
            $result["IP".$row["ipnum"]][]=$row;
         }
      }
      foreach ($result as $key => $data) {
         if (count($data) > 1) {
            foreach ($data as $keyip => $ip) {
               if (empty($ip['pname'])) {
                  unset($result[$key][$keyip]);
               }

            }
         }
      }
      if (isset($type_filter)) {
         foreach ($result as $key => $data) {
            if (empty($data)) {
               unset($result[$key]);
            }
         }
      }
      return $result;
   }

   /**
    * @param $params
    */
   function showReport($params) {
      global $CFG_GLPI;

      $PluginAddressingReport = new PluginAddressingReport();

      // Default values of parameters
      $default_values["start"]  = $start  = 0;
      $default_values["id"]     = $id     = 0;
      $default_values["export"] = $export = false;
      $default_values['filter'] = $filter = 0;

      foreach ($default_values as $key => $val) {
         if (isset($params[$key])) {
            $$key=$params[$key];
         }
      }

      if ($this->getFromDB($id)) {
         $addressingFilter = new PluginAddressingFilter();
         if ($filter > 0) {
            if ($addressingFilter->getFromDB($filter)) {
               $ipdeb  = sprintf("%u", ip2long($addressingFilter->fields['begin_ip']));
               $ipfin  = sprintf("%u", ip2long($addressingFilter->fields['end_ip']));
               $result = $this->compute($start, ['ipdeb'       => $ipdeb,
                                                 'ipfin'       => $ipfin,
                                                 'entities_id' => $addressingFilter->fields['entities_id'],
                                                 'type_filter' => $addressingFilter->fields['type']]);
            }
         } else {
            $ipdeb = sprintf("%u", ip2long($this->fields["begin_ip"]));
            $ipfin = sprintf("%u", ip2long($this->fields["end_ip"]));
            $result = $this->compute($start, ['ipdeb' => $ipdeb,
                                              'ipfin' => $ipfin]);
         }

         $nbipf = 0; // ip libres
         $nbipr = 0; // ip reservees
         $nbipt = 0; // ip trouvees
         $nbipd = 0; // doublons

         foreach ($result as $ip => $lines) {
            if (count($lines)) {
               if (count($lines) > 1) {
                  $nbipd++;
                  if (!$this->fields['double_ip']) {
                     unset($result[$ip]);
                  }
               }
               if ((isset($lines[0]['pname']) && strstr($lines[0]['pname'], "reserv"))) {
                  $nbipr++;
                  if (!$this->fields['alloted_ip']) {
                     unset($result[$ip]);
                  }
               }
               $nbipt++;
               if (!$this->fields['alloted_ip']) {
                  unset($result[$ip]);
               }
            } else {
               $nbipf++;
               if (!$this->fields['free_ip']) {
                  unset($result[$ip]);
               }
            }

         }

         ////title
         echo "<div class='spaced'>";
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2 left'>";
         echo "<td>";
         if ($this->fields['free_ip']) {
            echo __('Number of free ip', 'addressing')." ".$nbipf."<br>";
         }
         if ($this->fields['reserved_ip']) {
            echo __('Number of reserved ip', 'addressing')." ".$nbipr."<br>";
         }
         if ($this->fields['alloted_ip']) {
            echo __('Number of assigned ip (no doubles)', 'addressing')." ".$nbipt."<br>";
         }
         if ($this->fields['double_ip']) {
            echo __('Doubles', 'addressing')." ".$nbipd."<br>";
         }
         echo "</td>";
         echo "<td>";
         if ($this->fields['double_ip']) {
            echo "<span class='plugin_addressing_ip_double'>".
               __('Red row', 'addressing')."</span> - ".__('Same Ip', 'addressing')."<br>";
         }
         if (isset($this->fields['use_ping']) && $this->fields['use_ping']) {
            echo __('Ping free Ip', 'addressing')."<br>";
            echo "<span class='plugin_addressing_ping_off'>".
               __('Ping: got a response - used Ip', 'addressing').
                 "</span><br>";
            echo "<span class='plugin_addressing_ping_on'>".
               __('Ping: no response - free Ip', 'addressing').
                 "</span><br>";
         } else {
            echo "<span class='plugin_addressing_ip_free'>".
               __('Blue row', 'addressing')."</span> - ".__('Free Ip', 'addressing')."<br>";
         }
         if ($this->fields['reserved_ip']) {
            echo "<span class='plugin_addressing_ip_reserved'>".
               __('Green row', 'addressing')."</span> - ".__('Reserved Ip', 'addressing')."<br>";
         }

         echo "</td></tr>";

         echo "</table>";
         echo "</div>";

         ////////////////////////// research ////////////////////////////////////////////////////////////
          echo "<form method='post' name='filtering_form' id='filtering_form' action='".Toolbox::getItemTypeFormURL("PluginAddressingAddressing")."?id=$id'>";
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2 center'>";
         echo "<input type='hidden' name='id' value='$id'>";
         echo "<tr class='tab_bg_2 center'>";
         echo "<th colspan='2'>";
         echo __('Search');
         echo "</th></tr>";
         echo "<tr class='tab_bg_1 center'><td>";
         PluginAddressingFilter::dropdownFilters($params['id'], $filter);
         echo "</td>";
         echo "<td>";
         echo "<input type='submit' name='search' value=\""._sx('button', 'Search')."\"
                            class='submit'></td>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();

         $numrows = count($result);
         //         $numrows = 1 + ip2long($this->fields['end_ip']) - ip2long($this->fields['begin_ip']);
         $result = array_slice($result, $start, $_SESSION["glpilist_limit"]);
         Html::printPager($start, $numrows, self::getFormURL(), "start=$start&amp;id=$id&amp;filter=$filter",
                             'PluginAddressingReport');

         //////////////////////////liste ips////////////////////////////////////////////////////////////

         $ping_response = $PluginAddressingReport->displayReport($result, $this);

         if ($this->fields['use_ping']) {
            $total_realfreeip=$nbipf-$ping_response;
            echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2 center'>";
            echo "<td>";
            echo __('Real free Ip (Ping=KO)', 'addressing')." ".$total_realfreeip;
            echo "</td></tr>";
            echo "</table>";
         }
         echo "</div>";

      } else {
         echo "<div class='center'>".
               "<i class='fas fa-exclamation-triangle fa-4x' style='color:orange'></i><br><br><b>".
                 __('Problem detected with the IP Range', 'addressing')."</b></div>";
      }
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == __CLASS__) {
         if ($tabnum == 0) {
            $item->showReport($_GET);
         }
      }
      return true;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      return [Report::getTypeName(1)];
   }

   //Massive Action
   function getSpecificMassiveActions($checkitem = null) {
      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if (Session::haveRight('transfer', READ)
            && Session::isMultiEntitiesMode()
            && $isadmin) {
         $actions['PluginAddressingAddressing'.MassiveAction::CLASS_ACTION_SEPARATOR.'transfer'] = __('Transfer');
      }
      return $actions;
   }


   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case "transfer" :
            Dropdown::show('Entity');
            echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
            return true;
            break;
      }
      return parent::showMassiveActionsSubForm($ma);
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case "transfer" :
            $input = $ma->getInput();

            if ($item->getType() == 'PluginAddressingAddressing') {
               foreach ($ids as $key) {
                  $values["id"] = $key;
                  $values["entities_id"] = $input['entities_id'];

                  if ($item->update($values)) {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                  } else {
                      $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                  }
               }
            }
            break;
      }
   }

   /**
    * Type than could be linked to a Rack
    *
    * @param $all boolean, all type, or only allowed ones
    *
    * @return array of types
    **/
   static function getTypes($all = false) {

      if ($all) {
         return self::$types;
      }

      // Only allowed types
      $types = self::$types;

      foreach ($types as $key => $type) {
         if (!class_exists($type)) {
            continue;
         }

         $item = new $type();
         if (!$item->canView()) {
            unset($types[$key]);
         }
      }
      return $types;
   }
}

