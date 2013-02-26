<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file       htdocs/cabinetmed/stats/index.php
 *  \ingroup    cabinetmed
 *  \brief      Page of patient outcomes statistics
 */


$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../dolibarr/htdocs/main.inc.php");     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (! $res && file_exists("../../../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (! $res) die("Include of main fails");
require_once(DOL_DOCUMENT_ROOT."/core/class/dolgraph.class.php");
dol_include_once("/cabinetmed/lib/cabinetmed.lib.php");
dol_include_once("/cabinetmed/class/cabinetmedcons.class.php");
dol_include_once("/cabinetmed/class/cabinetmedstats.class.php");

$WIDTH=500;
$HEIGHT=200;

$userid=GETPOST('userid','int'); if ($userid < 0) $userid=0;
$socid=GETPOST('socid','int'); if ($socid < 0) $socid=0;
// Security check
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}

$year = strftime("%Y", time());
$startyear=$year-2;
$endyear=$year;

$mode=GETPOST("mode")?GETPOST("mode"):'customer';
$codageccam=GETPOST('codageccam');

if (empty($conf->cabinetmed->enabled)) accessforbidden();


/*
 * View
 */

$langs->load("cabinetmed@cabinetmed");

$form=new Form($db);

llxHeader();

$title=$langs->trans("Statistics");
$dir=$conf->cabinetmed->dir_temp;

print_fiche_titre($title, $mesg);

dol_mkdir($dir);

$stats = new CabinetMedStats($db, $socid, $mode, ($userid>0?$userid:0), ($codageccam?" AND codageccam LIKE '".$db->escape(preg_replace('/\*/','%',$codageccam))."'":''));


// Build graphic number of object
// $data = array(array('Lib',val1,val2,val3),...)
$data = $stats->getNbByMonthWithPrevYear($endyear,$startyear);
//var_dump($data);

$filenamenb = $dir."/outcomesnbinyear-".$year.".png";
$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=cabinetmed_temp&amp;file=outcomesnbinyear-'.$year.'.png';

$px1 = new DolGraph();
$mesg = $px1->isGraphKo();
if (! $mesg)
{
	$px1->SetData($data);
	$px1->SetPrecisionY(0);
	$i=$startyear;
	while ($i <= $endyear)
	{
		$legend[]=$i;
		$i++;
	}
	$px1->SetLegend($legend);
	$px1->SetMaxValue($px1->GetCeilMaxValue());
	$px1->SetWidth($WIDTH);
	$px1->SetHeight($HEIGHT);
	$px1->SetYLabel($langs->trans("NumberConsult"));
	$px1->SetShading(3);
	$px1->SetHorizTickIncrement(1);
	$px1->SetPrecisionY(0);
	$px1->mode='depth';
	$px1->SetTitle($langs->trans("NumberConsultByMonth"));

	$px1->draw($filenamenb,$fileurlnb);
}

// Build graphic amount of object
$data = $stats->getAmountByMonthWithPrevYear($endyear,$startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)

$filenameamount = $dir."/outcomesamountinyear-".$year.".png";
$fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=cabinetmed_temp&amp;file=outcomesamountinyear-'.$year.'.png';

$px2 = new DolGraph();
$mesg = $px2->isGraphKo();
if (! $mesg)
{
	$px2->SetData($data);
	$i=$startyear;
	while ($i <= $endyear)
	{
		$legend[]=$i;
		$i++;
	}
	$px2->SetLegend($legend);
	$px2->SetMaxValue($px2->GetCeilMaxValue());
	$px2->SetMinValue(min(0,$px2->GetFloorMinValue()));
	$px2->SetWidth($WIDTH);
	$px2->SetHeight($HEIGHT);
	$px2->SetYLabel($langs->trans("Amount"));
	$px2->SetShading(3);
	$px2->SetHorizTickIncrement(1);
	$px2->SetPrecisionY(0);
	$px2->mode='depth';
	$px2->SetTitle($langs->trans("AmountByMonth"));

	$px2->draw($filenameamount,$fileurlamount);
}



$head = patient_stats_prepare_head(null);

dol_fiche_head($head, 'statsconsultations', $langs->trans("Consultations"), 0, 'generic');


print '<table class="notopnoleftnopadd" width="100%"><tr>';
print '<td align="center" valign="top">';

// Show filter box
print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<table class="border" width="100%">';
print '<tr><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
/*print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
if ($mode == 'customer') $filter='s.client in (1,2,3)';
if ($mode == 'supplier') $filter='s.fournisseur = 1';
print $form->select_company($socid,'socid',$filter,1);
print '</td></tr>';
*/
print '<tr><td>'.$langs->trans("User").'</td><td>';
print $form->select_users($userid,'userid',1);
print '</td></tr>';
print '<tr><td>'.$langs->trans("CodageCCAM").'</td><td>';
print '<input type="text" id="codageccam" name="codageccam" value="'.$codageccam.'" size="30"> (* = joker)';
print '</td></tr>';
print '<tr><td align="center" colspan="2"><input type="submit" name="submit" class="button" value="'.$langs->trans("Refresh").'"></td></tr>';
print '</table>';
print '</form>';

print '<br><br>';

// Show array
$data = $stats->getAllByYear();

print '<table class="border" width="100%">';
print '<tr height="24">';
print '<td align="center">'.$langs->trans("Year").'</td>';
print '<td align="center">'.$langs->trans("Number").'</td>';
print '<td align="center">'.$langs->trans("AmountTotal").'</td>';
print '<td align="center">'.$langs->trans("AmountAverage").'</td>';
print '</tr>';

$oldyear=0;
foreach ($data as $val)
{
	$year = $val['year'];
	while ($year && $oldyear > $year+1)
	{	// If we have empty year
		$oldyear--;
		print '<tr height="24">';
		print '<td align="center"><a href="month.php?year='.$oldyear.'&amp;mode='.$mode.'">'.$oldyear.'</a></td>';
		print '<td align="right">0</td>';
		print '<td align="right">0</td>';
		print '<td align="right">0</td>';
		print '</tr>';
	}
	print '<tr height="24">';
	print '<td align="center">';
	//print '<a href="month.php?year='.$year.'&amp;mode='.$mode.'">';
	print $year;
	//print '</a>';
	print '</td>';
	print '<td align="right">'.$val['nb'].'</td>';
	print '<td align="right">'.price(price2num($val['total'],'MT'),1).'</td>';
	print '<td align="right">'.price(price2num($val['avg'],'MT'),1).'</td>';
	print '</tr>';
	$oldyear=$year;
}

print '</table>';


print '</td>';
print '<td align="center" valign="top">';

// Show graphs
print '<table class="border" width="100%"><tr valign="top"><td align="center">';
if ($mesg) { print $mesg; }
else {
	print $px1->show();
	print "<br>\n";
	print $px2->show();
}
print '</td></tr></table>';

print '</td></tr></table>';

dol_fiche_end();


llxFooter();

$db->close();
?>
