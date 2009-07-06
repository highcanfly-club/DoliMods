<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2009      Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id: phpsanetop.php,v 1.1 2009/07/06 13:22:46 eldy Exp $
 * $Source: /cvsroot/dolibarr/dolibarrmod/htdocs/phpsane/Attic/phpsanetop.php,v $
 */

/**
		\file 		htdocs/phpsane/phpsanetop.php
        \ingroup    phpsane
		\brief      Frame du haut Dolibarr pour l'affichage de PHPSane
		\author	    Laurent Destailleur
		\version    $Revision: 1.1 $
*/

$res=@include("../main.inc.php");
if (! $res) $res=@include("../../main.inc.php");	// If pre.inc.php is called by jawstats
if (! $res) $res=@include("../../../dolibarr/htdocs/main.inc.php");		// Used on dev env only
if (! $res) $res=@include("../../../../dolibarr/htdocs/main.inc.php");	// Used on dev env only

top_menu("","","_top");

?>










