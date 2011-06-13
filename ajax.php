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

$id         = optional_param('id', 0, PARAM_INT);
$action     = optional_param('action', '', PARAM_TAGLIST);
$entryid    = optional_param('entryid', '', PARAM_CLEAN);

require_login();


$returndata = array();

$admin = false;
if (!empty($id)) {
    $cm = get_coursemodule_from_id('', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    if (has_capability('moodle/course:manageactivities', $context)) {
        $admin = true;
    }
}

switch ($action) {
    case 'playerurl':
        $entry = null;
        if (!empty($id)) {
            $cm = get_coursemodule_from_id('kalturavideo', $id, 0, false, MUST_EXIST);
            $entry = $DB->get_record('kalturavideo', array('id'=>$cm->instance), '*', MUST_EXIST);
        }
        else if (!empty($entryid)) {
            $entry = new stdClass;
            $entry->kalturavideo = $entryid;
        }

        $url = kalturaPlayerUrlBase();
        $returndata = array('url' => $url.$entry->kalturavideo, 'params' => array());
        break;

    case 'cwurl':
        $returndata = kalturaCWSession_setup();
        break;

    default:
        break;
}

header('Content-Type: application/json');

echo json_encode($returndata);
?>
