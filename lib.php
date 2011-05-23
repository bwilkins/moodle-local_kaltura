<?php
/**
 * Kaltura Local Plugin for Moodle 2
 * Copyright (C) 2011 Catalyst IT (http://www.catalyst.net.nz)
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    local
 * @subpackage kaltura
 * @author     Brett Wilkins <brett@catalyst.net.nz>
 * @license    http://www.gnu.org/licenses/agpl.html GNU Affero GPL v3 or later
 */

require_once("$CFG->libdir/resourcelib.php");
require_once($CFG->dirroot."/local/kaltura/client/KalturaClient.php");


function kalturaClientSession($admin=false) {
    global $DB, $USER;
    $partnerId = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'partner_id'));
    $serviceUrl = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'server_uri'));
    $config = new KalturaConfiguration($partnerId);
    $config->serviceUrl = $serviceUrl;
    $client = new KalturaClient($config);

    if ($admin) {
        $secret = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'admin_secret'));
        $ks = $client->session->start($secret, $USER->id, KalturaSessionType::ADMIN);
        $client->setKs($ks);
    }
    else {
        $secret = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'secret'));
        $ks = $client->session->start($secret, $USER->id, KalturaSessionType::USER);
        $client->setKs($ks);
    }
    return $client;
}

function kalturaCWSession_setup($admin=false) {
    global $DB, $USER;
    $client = kalturaClientSession($admin);

    $uploader_type = 'regular';

    $config = $client->getConfig();
    $serviceUrl = $config->serviceUrl;
    $uiId = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'uploader_'.$uploader_type));
    $url = $serviceUrl."/kcw/ui_conf_id/".$uiId;

    return array('url'=>$url, 'params'=>array('sessionId'=>$client->getKs(),'uiConfId'=>$uiId,'partnerId'=>$config->partnerId, 'userId'=>$USER->id));
}

function kalturaGlobals_js($config) {
    if(empty($config) || !is_array($config)) {
        return false;
    }
    $ret = '<script type="text/javascript">'."\n";
    $ret .= 'if (window.kaltura == undefined) {
                window.kaltura = {};
            }'."\n";
    foreach ($config as $key => $value) {
        $ret .= "window.kaltura.$key = '$value';\n";
    }
    $ret .= '</script>';
    return $ret;
}

function kalturaPlayerUrlBase() {
    global $DB;
    $config = get_config('kalturavideo');
    $baseurl = $DB->get_field('config_plugins','value',array('plugin' => 'local_kaltura', 'name'=>'server_uri'));
    $partnerid = $DB->get_field('config_plugins','value',array('plugin' => 'local_kaltura', 'name'=>'partner_id'));

    $player_type = 'regular';

    $playerid = $DB->get_field('config_plugins','value',array('plugin' => 'local_kaltura', 'name'=>'player_'.$player_type.'_'.$config->player_theme));

    $swfurl = $baseurl;
    $swfurl .= '/kwidget/wid/_'.$partnerid;
    $swfurl .= '/ui_conf_id/'.$playerid.'/entry_id/';

    return $swfurl;
}


