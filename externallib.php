<?php

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External Web Service
 *
 * @package    local
 * @copyright  2017 Mihail Pozarski <mihailpozarski@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");

class local_webservice_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function paperattendance_presence_parameters() {
        return new external_function_parameters(
                array(
                	'initialdate' => new external_value(PARAM_INT, 'the initial date from where you want to get the attendance', VALUE_DEFAULT, 0),
                	'enddate' => new external_value(PARAM_INT, 'the last day from where you want to get the attendance', VALUE_DEFAULT, 0)
                )
        );
    }

    /**
     * Returns presence of paperattendance
     * @return json presence of paperattendance 
     */
    public static function paperattendance_presence($initialdate = 0, $enddate = 0) {
        global $DB;

        //Parameter validation
        $params = self::validate_parameters(self::paperattendance_presence_parameters(),
        		array('initialdate' => $initialdate, 'enddate' => $enddate));

      $return = $DB->get_records_sql('SELECT pp.id as presenceid,
										u.username as uaiemail,
										c.shortname as courseshortname,
										pp.status as presencestatus,
										pp.omegaid as omegaid 
										FROM {paperattendance_presence} AS pp 
										INNER JOIN {paperattendance_session} AS ps ON (pp.sessionid = ps.id) 
										INNER JOIN {course} AS c ON (c.id = ps.courseid) 
										INNER JOIN {user} AS u ON (u.id = pp.userid) where pp.lastmodified > ? AND pp.lastmodified < ?', array($initialdate,$enddate));
        echo json_encode($return);
        //return $return;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function paperttendance_presence_returns() {
        return new external_value(PARAM_TEXT, 'json encoded array with id,username,course shortname, presence status and omegaid');
    }



}
