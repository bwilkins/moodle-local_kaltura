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

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once("lib.php");
require_once('client/KalturaClient.php');
require_once('interface_strings.php');

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


header('Content-Type: application/json');

echo json_encode($returndata);
?>
