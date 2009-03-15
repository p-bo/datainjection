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

// ----------------------------------------------------------------------
// Original Author of file: CAILLAUD Xavier
// Purpose of file:
// ----------------------------------------------------------------------

global $LANG;
	
if (!defined('GLPI_ROOT')) {
	define('GLPI_ROOT', '../../..');
}

$NEEDED_ITEMS=array("data_injection","profile");

include (GLPI_ROOT . "/inc/includes.php");

checkRight("config","w");

commonHeader($DATAINJECTIONLANG["config"][1], $_SERVER["PHP_SELF"],"plugins","data_injection");

if (substr(phpversion(),0,1) < "5") {
	echo "<strong>".$DATAINJECTIONLANG["setup"][10]."</strong>";

} else {
	cleanCache("GLPI_HEADER_".$_SESSION["glpiID"]);
	
	if (!TableExists("glpi_plugin_data_injection_models")) {
		// First Install
		plugin_data_injection_Install();
		plugin_data_injection_createfirstaccess($_SESSION['glpiactiveprofile']['ID']);

	} else if (!FieldExists("glpi_plugin_data_injection_models","recursive")) {
		// Update
		plugin_data_injection_update131_14();	
	}elseif (!FieldExists("glpi_plugin_data_injection_models","port_unicity"))
	{
		plugin_data_injection_update14_15();
	}
	
	plugin_data_injection_initSession();
	glpi_header($_SERVER['HTTP_REFERER']);
}

commonFooter();
?>