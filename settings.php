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

if ($hassiteconfig) { // needs this condition or there is error on login page

    $settings = new admin_settingpage('local_kaltura', get_string('pluginname', 'local_kaltura'));
    $ADMIN->add('localplugins', $settings);


    $settings->add(new admin_setting_heading('servicesettings', get_string('servicesettings', 'local_kaltura') , ''));

    $settings->add(new admin_setting_configtext('local_kaltura/server_uri',
        get_string('serveruri', 'local_kaltura'), get_string('serveruri-explanation', 'local_kaltura'), 'http://www.kaltura.com', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('local_kaltura/partner_id',
        get_string('partnerid', 'local_kaltura'), null, null, PARAM_TEXT, 8));

    $settings->add(new admin_setting_configpasswordunmask('local_kaltura/secret',
        get_string('secret', 'local_kaltura'), null, null, PARAM_TEXT, 8));

    $settings->add(new admin_setting_configpasswordunmask('local_kaltura/admin_secret',
        get_string('adminsecret', 'local_kaltura'), null, null, PARAM_TEXT, 8));

    $settings->add(new admin_setting_heading('pluginsettings', get_string('pluginsettings', 'local_kaltura') , ''));

    $settings->add(new admin_setting_configtext('local_kaltura/player_regular_dark',
        get_string('playerregulardark', 'local_kaltura'), null, '1466342', PARAM_TEXT, 8));

    $settings->add(new admin_setting_configtext('local_kaltura/player_regular_light',
        get_string('playerregularlight', 'local_kaltura'), null, '1466432', PARAM_TEXT, 8));

    $settings->add(new admin_setting_configtext('local_kaltura/kupload_video',
        get_string('kuploadvideo', 'local_kaltura'), null, '4436601', PARAM_TEXT, 8));

    $settings->add(new admin_setting_configtext('local_kaltura/kupload_audio',
        get_string('kuploadaudio', 'local_kaltura'), null, '4971641', PARAM_TEXT, 8));

    $settings->add(new admin_setting_configtext('local_kaltura/student_upload_category',
        get_string('studentcategory', 'local_kaltura'), get_string('studentcategory-explain', 'local_kaltura'), '', PARAM_TEXT));

    $settings->add(new admin_setting_configselect('local_kaltura/identifier',
        get_string('identifier', 'local_kaltura'), get_string('identifier-explanation', 'local_kaltura'),
            'username', array('username'=>'username', 'email'=>'email', 'id'=>'id', 'idnumber'=>'idnumber')));
}
