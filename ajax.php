<?php
/**
 * Kaltura Local Plugin for Moodle 2
 * Copyright (C) 2009 Petr Skoda  (http://skodak.org)
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

require('../../config.php');
require_once("lib.php");
require_once($CFG->dirroot.'/local/kaltura/client/KalturaClient.php');
require_once($CFG->dirroot.'/local/kaltura/interface_strings.php');

$actions       = optional_param('actions', '', PARAM_CLEAN);
$params        = optional_param('params', null, PARAM_CLEAN);

require_login();

$returndata = array();
foreach ($actions as $index => $action) {
    if (isset($params[$index])) {
        $p = $params[$index];
    }
    else {
        $p = array();
    }
    $returndata[$index] = handleAction($action, $p);
}

function handleAction($action, $params=array()) {
    global $USER, $CFG, $DB, $_SESSION;
    switch ($action) {
        case 'playerurl':
            $partnerId  = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'partner_id'));
            $serviceUrl = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'server_uri'));
            $entry = null;
            if (!empty($params['id'])) {
                $cm = get_coursemodule_from_id('kalturavideo', $params['id'], 0, false, MUST_EXIST);
                $entry = $DB->get_record('kalturavideo', array('id'=>$cm->instance), '*', MUST_EXIST);
            }
            else if (!empty($params['entryid'])) {
                $entry = new stdClass;
                $entry->kalturavideo = $params['entryid'];
            }

            $url = kalturaPlayerUrlBase();

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

            return array('url' => $base.'audio.swf', 'base' => $base, 'params' => array('ks' => $client->getKs(), 'host' => $host, 'uid' => $USER->id, 'pid' => $config->partnerId, 'subpid' => $config->partnerId*100, 'kshowId' => -1, 'autopreview' => true, 'themeUrl' => $CFG->wwwroot.'/local/kaltura/objects/skin.swf', 'entryName' => 'New Entry', 'entryTags' => 'audio', 'thumbOffset' => 1, 'useCamera' => 'false'));
            break;

        case 'videourl':
            $client = kalturaClientSession();
            $config = $client->getConfig();
            $base   = $CFG->wwwroot.'/local/kaltura/objects/';
            $host   = str_replace(array('http://', 'https://'), '', $config->serviceUrl);

            return array('url' => $base.'video.swf', 'base' => $base, 'params' => array('ks' => $client->getKs(), 'host' => $host, 'uid' => $USER->id, 'pid' => $config->partnerId, 'subpid' => $config->partnerId*100, 'kshowId' => -1, 'autopreview' => true, 'themeUrl' => $CFG->wwwroot.'/local/kaltura/objects/skin.swf', 'entryName' => 'New Entry', 'entryTags' => 'audio', 'thumbOffset' => 1));
            break;

        case 'listpublic':
            list($client, $filter, $pager) = buildListFilter($params);

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

        case 'listprivate':
            list($client, $filter, $pager) = buildListFilter($params);
            $identifier = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'identifier'));

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
            $select->videolistprivate   = handleAction('listprivate', array('mediatype' => 'video'));
            $select->audiolistprivate   = handleAction('listprivate', array('mediatype' => 'audio'));
            if ($enable_shared) {
                $select->videolistpublic    = handleAction('listpublic', array('mediatype' => 'video'));
                $select->audiolistpublic    = handleAction('listpublic', array('mediatype' => 'audio'));
            }

            $edit->categorylist         = handleAction('getcategorylist');
            return construct_interface($select, $edit, $enable_shared);
            break;

        case 'videouploadurl':
            $identifier = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'identifier'));
            $ui_conf_id = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'kupload_video'));
            $client = kalturaClientSession();
            $config = $client->getConfig();
            $base   = $CFG->wwwroot.'/local/kaltura/objects/';

            return array('url' => $config->serviceUrl.'/kupload/ui_conf_id/'.$ui_conf_id, 'base' => $base, 'params' => array('ks' => $client->getKs(), 'uid' => $USER->{$identifier}, 'partnerId' => $config->partnerId, 'subPId' => $config->partnerId*100, 'uiConfId' => $ui_conf_id), 'wmode' => 'transparent');
            break;

        case 'audiouploadurl':
            $identifier = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'identifier'));
            $ui_conf_id = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'kupload_audio'));
            $client = kalturaClientSession();
            $config = $client->getConfig();
            $base   = $CFG->wwwroot.'/local/kaltura/objects/';

            return array('url' => $config->serviceUrl.'/kupload/ui_conf_id/'.$ui_conf_id, 'base' => $base, 'params' => array('ks' => $client->getKs(), 'uid' => $USER->{$identifier}, 'partnerId' => $config->partnerId, 'subPId' => $config->partnerId*100, 'uiConfId' => $ui_conf_id), 'wmode' => 'transparent');
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
                if($category_name = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'student_upload_category'))) {
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
                $entry->mediaType = KalturaMediaType::VIDEO;
            }
            return array(
                'entry' => $client->media->addFromUploadedFile($entry, $token)
            );

            break;

        case 'updateentry':
            $client  = kalturaClientSession();
            $config  = $client->getConfig();

            $entryid = $params['token'];
            $entrydata = json_decode($params['entrydata']);

            $entry                = new KalturaMediaEntry();
            $entry->name          = $entrydata->title;
            $entry->description   = $entrydata->description;
            $entry->tags          = $entrydata->tags;
            if ($entrydata->categories) {
                $entry->categoriesIds = $entrydata->categories;
            }

            if (!$_SESSION['kaltura_use_shared']) { //This means they're a student...
                if($category_name = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'student_upload_category'))) {
                    $res = handleAction('getcategorydetails', array('category' => $category_name));
                    if (!empty($entry->categoriesIds)) {
                        $entry->categoriesIds .= ',' . $res['category']->id;
                    }
                    else {
                        $entry->categoriesIds = $res['category']->id;
                    }
                }
            }

            if (empty($entry->description)) {
                return array(
                    'entry' => $client->media->update($entryid, $entry),
                );
            }
            else {
                return array(
                    'entry' => $client->media->addFromEntry($entryid, $entry),
                );
            }

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

        default:
            break;
    }
    return array();
}

function buildListFilter($params) {
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
    if (isset($params['mediatype']) && $params['mediatype'] == 'video') {
        $filter->mediaTypeEqual = KalturaMediaType::VIDEO;
    }
    else if (isset($params['mediatype']) && $params['mediatype'] == 'audio') {
        $filter->mediaTypeEqual = KalturaMediaType::AUDIO;
    }

    return array($client, $filter, $pager);
}

header('Content-Type: application/json');

echo json_encode($returndata);
?>
