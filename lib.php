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
    $partnerId  = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'partner_id'));
    $serviceUrl = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'server_uri'));
    $identifier = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'identifier'));

    $config = new KalturaConfiguration($partnerId);
    $config->serviceUrl = $serviceUrl;
    $client = new KalturaClient($config);
    $id = $USER->{$identifier};

    if ($admin) {
        $secret = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'admin_secret'));
        $ks = $client->session->start($secret, $id, KalturaSessionType::ADMIN);
        $client->setKs($ks);
    }
    else {
        $secret = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'secret'));
        $ks = $client->session->start($secret, $id, KalturaSessionType::USER);
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

    return array('url'=>$url, 'params'=>array('sessionId'=>$client->getKs(),'uiConfId'=>$uiId,'partnerId'=>$config->partnerId, 'userId'=>$USER->email));
}

function kalturaGlobals_js($config) {
    $strs = array();
    $strs['loading'] = get_string('loading', 'local_kaltura');
    $strs['connectionissue'] = get_string('connectionissue', 'local_kaltura');
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
    $ret .= "window.kaltura.strs = {};\n";
    foreach ($strs as $key => $value) {
        $value = htmlentities($value);
       $ret .= "window.kaltura.strs.$key = '$value';\n";
    }
    $ret .= '</script>';
    return $ret;
}

function kalturaPlayerUrlBase($playerid=0) {
    $config = get_config('local_kaltura');
    $baseurl = $config->server_uri;
    $partnerid = $config->partner_id;

    $player_type = 'regular';


    $ids = $config->player_selections;
    if (strpos($ids, ',') !== false) {
        $playerids = explode(',', $ids);
    }

    if (empty($playerid)) {
        if (!empty($playerids)) {
            $playerid = $playerids[0];
        }
        else {
            $playerid = $ids;
        }
    }
    else {
        if (!empty($playerids)) {
            if (!in_array($playerid, $playerids)) {
                $playerid = $playerids[0];
            }
        }
    }

    $swfurl = $baseurl;
    $swfurl .= '/kwidget/wid/_'.$partnerid;
    $swfurl .= '/ui_conf_id/'.$playerid.'/entry_id/';

    return $swfurl;
}

function handleAction($action, $params=array()) {
    global $USER, $CFG, $DB, $_SESSION;
    $localconfig = get_config('local_kaltura');
    switch ($action) {
        case 'playerurl':
            $partnerId  = $localconfig->partner_id;
            $serviceUrl = $localconfig->server_uri;
            $entry = null;
            if (!empty($params['id'])) {
                $cm    = get_coursemodule_from_id('kalturavideo', $params['id'], 0, false, MUST_EXIST);
                $entry = $DB->get_record('kalturavideo', array('id'=>$cm->instance), '*', MUST_EXIST);
            }
            else if (!empty($params['entryid'])) {
                $entry = new stdClass;
                $entry->kalturavideo   = $params['entryid'];
                $entry->kaltura_player = 0;
            }

            if (!empty($params['playerid'])) {
                $entry->kaltura_player = $params['playerid'];
            }

            $url = kalturaPlayerUrlBase($entry->kaltura_player);

            $ui_conf_id = 5209112;
            $scriptUrl = $serviceUrl . '/p/' . $partnerId . '/sp/' . $partnerId * 100 . '/embedIframeJs/ui_conf_id/' . $ui_conf_id . '/partner_id/' . $partnerId;

            return array('url' => $url . $entry->kalturavideo, 'html5url' =>$scriptUrl, 'params' => array());
            break;

        case 'cwurl':
            return kalturaCWSession_setup();
            break;

        case 'audiourl':
            $client = kalturaClientSession();
            $config = $client->getConfig();
            $base   = $CFG->wwwroot.'/local/kaltura/objects/';
            $host   = str_replace(array('http://', 'https://'), '', $config->serviceUrl);

            return array('url' => $base.'audio.swf', 'base' => $base, 'params' => array('ks' => $client->getKs(), 'host' => $host, 'uid' => $USER->id, 'pid' => $config->partnerId, 'subpid' => $config->partnerId*100, 'kshowId' => -1, 'autopreview' => true, 'themeUrl' => $CFG->wwwroot.'/local/kaltura/objects/skin.swf', 'entryName' => 'New Entry', 'thumbOffset' => 1, 'useCamera' => 'false'));
            break;

        case 'videourl':
            $client = kalturaClientSession();
            $config = $client->getConfig();
            $base   = $CFG->wwwroot.'/local/kaltura/objects/';
            $host   = str_replace(array('http://', 'https://'), '', $config->serviceUrl);

            return array('url' => $base.'video.swf', 'base' => $base, 'params' => array('ks' => $client->getKs(), 'host' => $host, 'uid' => $USER->id, 'pid' => $config->partnerId, 'subpid' => $config->partnerId*100, 'kshowId' => -1, 'autopreview' => true, 'themeUrl' => $CFG->wwwroot.'/local/kaltura/objects/skin.swf', 'entryName' => 'New Entry', 'thumbOffset' => 1));
            break;

        case 'videolistpublic':
            list($client, $filter, $pager) = buildVideoListFilter($params);

            $results = $client->media->listAction($filter, $pager);
            $count   = $client->media->count($filter);
            $pagecount = ceil($count/$pager->pageSize);

            if ($pager->pageIndex > $pagecount) {
                return array();
                break;
            }

            return array(
                'page' => array(
                    'count' => $pagecount,
                    'current' => (int) $pager->pageIndex,
                ),
                'count' => $count,
                'objects' => $results->objects,
            );
            break;

        case 'videolistprivate':
            list($client, $filter, $pager) = buildVideoListFilter($params);
            $identifier = $localconfig->identifier;

            $filter->userIdEqual = $USER->{$identifier};

            $results = $client->media->listAction($filter, $pager);
            $count   = $client->media->count($filter);
            $pagecount = ceil($count/$pager->pageSize);

            if ($pager->pageIndex > $pagecount) {
                return array();
                break;
            }

            return array(
                'page' => array(
                    'count' => $pagecount,
                    'current' => (int) $pager->pageIndex,
                ),
                'count' => $count,
                'objects' => $results->objects,
            );
            break;

        case 'audiolistprivate':
            list($client, $filter) = buildAudioListFilter($params);
            $identifier = $localconfig->identifier;

            $filter->userIdEqual = $USER->{$identifier};

            $results = $client->media->listAction($filter);
            $count   = $client->media->count($filter);

            return array(
                'count' => $count,
                'objects' => $results->objects,
            );
            break;

        case 'audiolistpublic':
            list($client, $filter) = buildAudioListFilter($params);

            $results = $client->media->listAction($filter);
            $count   = $client->media->count($filter);

            return array(
                'count' => $count,
                'objects' => $results->objects,
            );
            break;

        case 'getdomnodes':
            $select = new stdClass;
            $edit   = new stdClass;

            $enable_shared=false;
            if (is_numeric($params['id'])) {
                $id = (int) $params['id'];
                if (!empty($id)) {
                    $context = get_context_instance(CONTEXT_MODULE, $id);
                    if (has_capability('local/kaltura:viewshared', $context)) {
                        $enable_shared = true;
                    }
                }
                else {//This will happen when you're creating a new resource (i.e. probably most of the time)
                    //Assume this is from hitting the new kaltura resource as admin/teacher
                    $enable_shared = true;
                }
            }

            $_SESSION['kaltura_use_shared'] = $enable_shared;

            $select->videouploadurl     = handleAction('videouploadurl');
            $select->audiouploadurl     = handleAction('audiouploadurl');
            $select->videourl           = handleAction('videourl');
            $select->audiourl           = handleAction('audiourl');
            $select->videolistprivate   = handleAction('videolistprivate');
            $select->audiolistprivate   = handleAction('audiolistprivate');
            if ($enable_shared) {
                $select->videolistpublic    = handleAction('videolistpublic');
                $select->audiolistpublic    = handleAction('audiolistpublic');
            }

            $edit->categorylist         = handleAction('getcategorylist');
            return construct_interface($select, $edit, $enable_shared);
            break;

        case 'videouploadurl':
            $identifier = $localconfig->identifier;
            $ui_conf_id = $localconfig->kupload_video;
            $vidmaxsize = $localconfig->upload_video_maxsize;
            $client = kalturaClientSession();
            $config = $client->getConfig();
            $base   = $CFG->wwwroot.'/local/kaltura/objects/';

            return array('url' => $config->serviceUrl.'/kupload/ui_conf_id/'.$ui_conf_id, 'base' => $base, 'params' => array('ks' => $client->getKs(), 'uid' => $USER->{$identifier}, 'partnerId' => $config->partnerId, 'subPId' => $config->partnerId*100, 'uiConfId' => $ui_conf_id, 'maxFileSize' => $vidmaxsize, 'maxUploads' => 1), 'wmode' => 'transparent');
            break;

        case 'audiouploadurl':
            $identifier = $localconfig->identifier;
            $ui_conf_id = $localconfig->kupload_audio;
            $audmaxsize = $localconfig->upload_audio_maxsize;
            $client = kalturaClientSession();
            $config = $client->getConfig();
            $base   = $CFG->wwwroot.'/local/kaltura/objects/';

            return array('url' => $config->serviceUrl.'/kupload/ui_conf_id/'.$ui_conf_id, 'base' => $base, 'params' => array('ks' => $client->getKs(), 'uid' => $USER->{$identifier}, 'partnerId' => $config->partnerId, 'subPId' => $config->partnerId*100, 'uiConfId' => $ui_conf_id, 'maxFileSize' => $audmaxsize, 'maxUploads' => 1), 'wmode' => 'transparent');
            break;

        case 'geteditdata':
            $client = kalturaClientSession();

            if ($entryid = $params['entryid']) {
                return array('entry' => $client->media->get($entryid));
            }
            else {
                return array('error' => 'no such entryid');
            }
            break;

        case 'getcategorylist':
            $client = kalturaClientSession();

            return array('categories' => $client->category->listAction());
            break;

        case 'addentry':
            $token = $params['token'];
            $client = kalturaClientSession();
            $config = $client->getConfig();

            $entrydata = json_decode($params['entrydata']);

            $entry                 = new KalturaMediaEntry();
            $entry->name           = $entrydata->title;
            $entry->description    = $entrydata->description;
            $entry->tags           = $entrydata->tags;
            if ($entrydata->categories) {
                $entry->categoriesIds = $entrydata->categories;
            }

            if (!$_SESSION['kaltura_use_shared']) { //This means they're a student...
                if($category_name = $localconfig->student_upload_category) {
                    $res = handleAction('getcategorydetails', array('category' => $category_name));
                    if (!empty($entry->categoriesIds)) {
                        $entry->categoriesIds .= ',' . $res['category']->id;
                    }
                    else {
                        $entry->categoriesIds = $res['category']->id;
                    }
                }
            }

            if ($entrydata->mediatype == 'video') {
                $entry->mediaType = KalturaMediaType::VIDEO;
            }
            else {//Assume audio for now
                $entry->mediaType = KalturaMediaType::AUDIO;
            }
            return array(
                'entry' => $client->media->addFromUploadedFile($entry, $token)
            );

            break;

        case 'updateentry':
            $client  = kalturaClientSession(true);
            $config  = $client->getConfig();

            $entryid = $params['token'];
            $entrydata = (object) json_decode($params['entrydata']);

            $entry                = new KalturaMediaEntry();
            $entry->name          = $entrydata->title;
            $entry->description   = (!empty($entrydata->description)) ? $entrydata->description : ' '; //Urgh. if description is empty, we get an "Invalid KS" error
            $entry->tags          = (!empty($entrydata->tags)) ? $entrydata->tags : null;
            if ($entrydata->categories) {
                $entry->categoriesIds = $entrydata->categories;
            }

            if (!$_SESSION['kaltura_use_shared']) { //This means they're a student...
                if($category_name = $localconfig->student_upload_category) {
                    $res = handleAction('getcategorydetails', array('category' => $category_name));
                    if (!empty($entry->categoriesIds)) {
                        $entry->categoriesIds .= ',' . $res['category']->id;
                    }
                    else {
                        $entry->categoriesIds = $res['category']->id;
                    }
                }
            }

            return array(
                'entry' => $client->media->update($entryid, $entry),
            );

            break;

        case 'getcategorydetails':
            $client = kalturaClientSession(true);

            $categoryname = html_entity_decode($params['category']);

            $filter = new KalturaCategoryFilter();
            $filter->fullNameEqual = $categoryname;

            $result = $client->category->listAction($filter);

            //Assume only one category was returned - that's all we want!
            if ($result->totalCount > 0) {
                $category = $result->objects[0];
                return array(
                    'category' => $category,
                );
            }
            else {
                $category = new KalturaCategory();

                if (strpos($categoryname, '>') > 0) {
                    $parts = explode('>', $categoryname);
                    $name = array_pop($parts);
                    $parentname = implode('>', $parts);

                    $parent = handleAction('getcategorydetails', array('category' => $parentname));

                    $category->parentId = $parent['category']->id;
                    $category->name = $name;
                }
                else {
                    $category->name = $categoryname;
                }

                return array(
                    'category' => $client->category->add($category)
                );
            }
            break;

        case 'getUiConfDetails':
            $client = kalturaClientSession(true);

            $ids = $params['ids'];

            $filter = new KalturaUiConfFilter();
            $filter->idIn = $ids;
            $filter->objTypeEqual = KalturaUiConfObjType::PLAYER;

            $results = $client->uiConf->listAction($filter);
            return $results;
            break;

        default:
            break;
    }
    return array();
}

function buildVideoListFilter($params) {
    $client = kalturaClientSession(true);
    $config = $client->getConfig();

    $pager = new KalturaFilterPager();
    $pager->pageSize  = 9;
    $pager->pageIndex = 1;
    if (!empty($params['page'])) {
        $pager->pageIndex=$params['page'];
    }

    $filter = new KalturaMediaEntryFilter();
    $filter->patnerIdEqual = $config->partnerId;
    $filter->statusEqual   = KalturaEntryStatus::READY;
    $filter->mediaTypeEqual = KalturaMediaType::VIDEO;

    return array($client, $filter, $pager);
}

function buildAudioListFilter($params) {
    $client = kalturaClientSession(true);
    $config = $client->getConfig();

    $filter = new KalturaMediaEntryFilter();
    $filter->patnerIdEqual = $config->partnerId;
    $filter->statusEqual   = KalturaEntryStatus::READY;
    $filter->mediaTypeEqual = KalturaMediaType::AUDIO;

    return array($client, $filter);
}


//Adding this cron function here as it does not seem to be being run from mod/kalturavideo
function local_kaltura_cron() {
    global $DB;
    mtrace('kaltura: local_kaltura_cron() started at '. date('H:i:s'));

    $dbman = $DB->get_manager();
    if ($dbman->table_exists('kalturaplayers')) {

        $ids = get_config('local_kaltura', 'player_selections');
        $records = $DB->get_records('kalturaplayers');
        $alreadyhave = array_keys($records);

        $compare = array_diff(explode(',', $ids), $alreadyhave);
        $compare = implode(',', $compare);

        if (!empty($compare)) {
            $data = handleAction('getUiConfDetails', array('ids' => $compare));
            mtrace('data: '. print_r($data, true));
            foreach ($data->objects as $ob) {
                $player       = new stdClass;
                $player->id   = $ob->id;
                $player->name = $ob->name;

                mtrace('Adding player ' . $player->id . ': "' . $player->name . '"');
                $DB->import_record('kalturaplayers', $player); //Using import here, as insert discards id
            }
        }
    }
    mtrace('kaltura: local_kaltura_cron() finished at '. date('H:i:s'));
}
