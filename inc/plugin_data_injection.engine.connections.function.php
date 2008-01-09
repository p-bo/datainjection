<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org/
 ----------------------------------------------------------------------

 LICENSE

	This file is part of GLPI.

    GLPI is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    GLPI is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with GLPI; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 ------------------------------------------------------------------------
*/

// Original Author of file: Walid Nouh (walid.nouh@atosorigin.com)
// Purpose of file:
// ----------------------------------------------------------------------

/**
 * Add a Network Card (for computer, printer, ... but not for network device)
 * 
 * @param common_fields the common_fields
 * @param canadd indicates if user has right to add values
 */
function addNetworkCard(&$common_fields, $canadd, $canconnect)
{
	global $DATAINJECTIONLANG;
	
	$input=array();
	
	//Unset fields is mac or ip is empty
	if ((isset($common_fields["ifmac"]) && $common_fields["ifmac"] == EMPTY_VALUE))
		unset($common_fields["ifmac"]);
		
	if ((isset($common_fields["ifaddr"]) && $common_fields["ifaddr"] == EMPTY_VALUE))
		unset($common_fields["ifaddr"]);

	if ((isset($common_fields["port"]) && $common_fields["port"] == EMPTY_VALUE))
		unset($common_fields["port"]);

	//Must add port ONLY if ip or mac or name of port is provided
	if (isset($common_fields["ifmac"]) || isset($common_fields["ifaddr"]) || isset($common_fields["port"]))
	{	
		$add_card_informations=true;
		if (isset($common_fields["ifaddr"])) {
			$input["ifaddr"]=$common_fields["ifaddr"];			
		}
		if (isset($common_fields["ifmac"])) {
			$input["ifmac"]=$common_fields["ifmac"];			
		}	
		if (isset($common_fields["port"])) {
			$input["name"]=$common_fields["port"];			
		} else {
			$input["name"]=$DATAINJECTIONLANG["mappings"][2];			
		}
		$input["logical_number"]=0;
			
		addFullPort($common_fields,$input);

		//Only try to create network plug and perform network connection is the option was set in the injection model
		if ($canconnect) {
			updatePort($common_fields, $canadd);
		}
	}
}

/*
 * Add port, check if it exists
 */
function addFullPort (&$common_fields, $input)
{
	global $DB;
	$ID=0;
	$netport = new Netport;

	//First, detect if port is already present
	$sql = "SELECT ID FROM glpi_networking_ports WHERE on_device=".$common_fields["device_id"]." AND name='".$input["name"]."'";
	$result = $DB->query($sql);
	if ($DB->numrows($result) > 0) {
		$ID = $DB->result($result,0,"ID");
	}

	if ($ID) {
		$input["ID"]=$ID;
		$common_fields["network_port_id"] = $ID;

		$netport->update($input);		
	} else {
		$input["on_device"]=$common_fields["device_id"];
		$input["device_type"]=$common_fields["device_type"];

		$common_fields["network_port_id"] = $netport->add($input);
	}	
}

/**
 * Add a Network Ports (for network device only)
 * 
 * @param common_fields the common_fields
 */
function addNetworkPorts($common_fields)
{
	
	if (isset($common_fields["nb_ports"])) {
		for ($i=1 ; $i <= $common_fields["nb_ports"] ; $i++)
		{
			$input = array();
			$netport = new Netport;
	
			$add="";
			if ($i<10) $add="0";
			$input["logical_number"]=$i;
			$input["name"]=$add.$i;
			$input["on_device"]=$common_fields["device_id"];
			$input["device_type"]=$common_fields["device_type"];
			$netport->add($input);
		}	
	}
}

function addNetworkPlug($common_fields,$canadd)
{
	if (isset($common_fields["network_port_id"]) && isset($common_fields["plug"]))
		return getDropdownValue(array(), array("table"=>"glpi_dropdown_netpoint"),$common_fields["plug"],$common_fields["FK_entities"],$canadd,(isset($common_fields["location"])?$common_fields["location"]:0));	  
	else
		return 0;
}

function addVlan($common_fields,$canadd)
{
	if (isset($common_fields["network_port_id"]) && isset($common_fields["vlan"]))
	{
		$vlan_id = getDropdownValue(array(), array("table"=>"glpi_dropdown_vlan"),$common_fields["vlan"],$common_fields["FK_entities"],$canadd);
		assignVlan($common_fields["network_port_id"],$vlan_id);	  
	}
	else
		return 0;
}

function updatePort(&$common_fields, $canadd)
{
	global $DB;
		
	$netport = new Netport;
	$input=array();
	
	if (isset($common_fields["network_port_id"]) && $common_fields["network_port_id"] != EMPTY_VALUE)
	{
		$common_fields["netpoint"]=addNetworkPlug($common_fields,$canadd);
		$input["netpoint"]=$common_fields["netpoint"];
		$input["ID"]=$common_fields["network_port_id"];
		$netport->update($input);
		addVlan($common_fields,$canadd);
		connectWire($common_fields);
	}
}

/* 
 * For all type device, except network
 * TODO : need to be improved (search by network name+port)
 */
function connectWire($common_fields)
{
	global $DB;
	
	if (isset($common_fields["netpoint"]) && $common_fields["netpoint"]>0 &&
		isset($common_fields["network_port_id"]) && $common_fields["network_port_id"]>0)
	{
		$sql = "SELECT ID FROM glpi_networking_ports WHERE ID!=".$common_fields["network_port_id"]." AND netpoint=".$common_fields["netpoint"]." AND device_type=".NETWORKING_TYPE;
		$result=$DB->query($sql);
		if($DB->numrows($result)>0)
			$DB->query("INSERT INTO glpi_networking_wire (end1,end2) VALUES (".$common_fields["network_port_id"].",".$DB->result($result,0,"ID").")");
	}
}

function addContract($common_fields)
{
	if (isset($common_fields["contract"]))
		addEnterpriseContract($common_fields["contract"],$common_fields['device_id']);
}

function addContact($common_fields)
{
	if (isset($common_fields["contact"]))
		addContactEnterprise($common_fields['device_id'],$common_fields['contact']);
}

function addEntity($fields)
{
	$entity = new Entity;
	
}

function getEntityParentId($parent_name)
{
	global $DB;
	$sql = "SELECT ID, level FROM glpi_entities WHERE name='".$parent_name."' OR completename='".$parent_name."'";
	$result = $DD->query($sql);
	if ($DB->numrows($result) > 0)
		return array("ID"=>$DB->result($result,0,"ID"),"level"=>$DB->result($result,0,"level"));
	else
		return array("ID"=>0);	
}

function updateWithTemplate($common_fields)
{
	if (isset($common_fields["template"]))
	{
		$template_id = $common_fields["template"];
		$tpl = getInstance($common_fields["device_type"]);
		$tpl->getFromDB($template_id);
	
		$item = getInstance($common_fields["device_type"]);
		$item->getFromDB($common_fields["device_id"]);
	
		//Unset fields from template
		unset($tpl->fields["ID"]);
		unset($tpl->fields["date_mod"]);
		unset($tpl->fields["is_template"]);
		unset($tpl->fields["FK_entities"]);			
		
		foreach ($tpl->fields as $key=>$value)
		{
			if ($value != EMPTY_VALUE && ( !isset($item->fields[$key]) || $item->fields[$key] == EMPTY_VALUE || $item->fields[$key] == DROPDOWN_DEFAULT_VALUE))
				$item->fields[$key]=$value;
		}
		
		$item->update($item->fields);
	}	
}
?>