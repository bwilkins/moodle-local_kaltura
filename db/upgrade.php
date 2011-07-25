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

defined('MOODLE_INTERNAL') || die;

function xmldb_local_kaltura_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    if ($oldversion < 2011071800) {
        require_once(dirname(dirname(__FILE__)).'/lib.php');

        // If have kaltura details
        $partnerId  = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'partner_id'));
        $serviceUrl = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'server_uri'));
        $secret     = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'admin_secret'));
        $identifier = $DB->get_field('config_plugins','value',array('plugin'=>'local_kaltura', 'name'=>'identifier'));

        if (empty($identifier)) {
            //Not sure if identifier is set by this time, so assume username if not.
            $identifier = 'username';
        }

        if (!empty($partnerId) && !empty($secret)) {
            $client = kalturaClientSession(true);

            // Get list of media
            $media  = $client->media->listAction();

            // userids = [x->userid for x in media]
            $userids = array();
            foreach ($media->objects as $m) {
                if (is_numeric($m->userId)) {
                    $userids[$m->id] = $m->userId;
                }
            }

            // Get list of userid,username pairs for each userid
            $userids_to_fetch = array_unique(array_values($userids));
            if (!empty($userids_to_fetch)) {
                list($sql, $params) = $DB->get_in_or_equal($userids_to_fetch);
                $records = $DB->get_records_select('user','id '.$sql, $params, '', 'id, '.$identifier);

                // update each entry with userid as username of pair
                foreach ($media->objects as $m) {
                    if (array_key_exists($m->userId, $records)) {
                        $item = new KalturaMediaEntry();
                        $user = $records[$m->userId];
                        $item->userId = $user->{$identifier};
                        print $OUTPUT->box("Updating '$m->name' ($m->id): userid: $m->userId -> $item->userId");
                        $client->media->update($m->id, $item);
                    }
                }
            }
        }

        upgrade_plugin_savepoint(true, 2011071800, 'local', 'kaltura');
    }

    return true;
}

