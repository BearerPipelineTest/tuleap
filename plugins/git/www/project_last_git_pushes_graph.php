<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require 'pre.php';
require_once 'common/chart/Chart.class.php';
require_once dirname(__FILE__).'/../include/GitDao.class.php';

$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $groupId = $request->get('group_id');
    $project = ProjectManager::instance()->getProject($groupId);
} else {
    header('Location: '.get_server_url());
}
$vDuration = new Valid_UInt();
$vGroupId->required();
if ($request->valid($vDuration)) {
    $nb_weeks = $request->get('duration');
} else {
    header('Location: '.get_server_url());
}

$dao      = new GitDao();
$repoList = $dao->getProjectRepositoryList($groupId);

//$nb_weeks        = 4 * 3;
$duration        = 7 * $nb_weeks;
$day             = 24 * 3600;
$week            = 7 * $day;
$today           = $_SERVER['REQUEST_TIME'];
$start_of_period = strtotime("-$nb_weeks weeks");

$dates = array();
$year = array();
$weekNum = array();
for ($i = $start_of_period ; $i <= $today ; $i += $week) {
    $dates[] = date('M d', $i);
    $weekNum[] = intval(date('W', $i));
    $year[] = intval(date('Y',$i));
}
$nb_repo = count($repoList);
$graph = new Chart(500, 300+16*$nb_repo);
$graph->SetScale('textlin');

$graph->img->SetMargin(40,20,20,80+16*$nb_repo);
$graph->SetMarginColor('white');
$graph->title->Set('Project last git pushes');
$graph->title->SetFont(FF_FONT2, FS_BOLD);

$graph->xaxis->SetLabelMargin(25);
$graph->xaxis->SetLabelAlign('right', 'center');
$graph->xaxis->SetTickLabels($dates);

$graph->yaxis->SetPos('min');
$graph->yaxis->SetTitle("Pushes", 'center');

$graph->yaxis->title->SetFont(FF_FONT2, FS_BOLD);
$graph->yaxis->title->SetAngle(90);
$graph->yaxis->title->Align('center', 'top');
$graph->yaxis->SetTitleMargin(30);

$graph->yaxis->SetLabelAlign('center', 'top');
$graph->legend->Pos(0.1,0.98,'right', 'bottom');

$nb_repo = count($repoList);
$colors = array_reverse(array_slice($GLOBALS['HTML']->getChartColors(), 0, $nb_repo));
$nb_colors = count($colors);
$i = 0;
$bplot = array();
foreach ($repoList as $repository) {
    $pushes = array();
    $gitLogDao = new Git_LogDao();
    foreach($weekNum as $key => $w) {
        $res = $gitLogDao->getRepositoryPushesByWeek($repository['repository_id'], $w, $year[$key]);
        if ($res && !$res->isError()) {
            if ($res->valid()) {
                $row = $res->current();
                $pushes[$key] = intval($row['pushes']);
                $res->next();
            }
        }
    $pushes = array_pad($pushes, $nb_weeks, 0);
    }
    $b2plot = new BarPlot($pushes);
    $color = $colors[$i++ % $nb_colors];   
    $b2plot->SetFillgradient($color, $color.':0.6', GRAD_VER);
    $b2plot->SetLegend($repository['repository_name']);
    $bplot[] = $b2plot;
}

// Create the accumulated bar plot
$abplot = new AccBarPlot($bplot);
$abplot->SetShadow();
$abplot->SetAbsWidth(15);

$graph->Add($abplot);
$graph->Stroke();

?>
