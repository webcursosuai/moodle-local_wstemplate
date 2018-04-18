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
    public static function webservice_intranetmobile_parameters() {
        return new external_function_parameters(
                array(
                	'idnumber' => new external_value(PARAM_INT, 'the initial date from where you want to get the attendance', VALUE_DEFAULT, 0),
                )
        );
    }

    /**
     * Returns presence of paperattendance
     * @return json presence of paperattendance 
     */
    public static function webservice_intranetmobile($courseidnumber = 0) {
        global $DB;
        
        //Parameter validation
        $params = self::validate_parameters(self::webservice_intranetmobile_parameters(),
            array('idnumber' => $courseidnumber));
        
        $return = $DB->get_record('course',array("idnumber" => $courseidnumber));
        
        echo json_encode($return);
        //return $return;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function webservice_intranetmobile_returns() {
        return new external_value(PARAM_TEXT, 'json encoded array that returns, courses and its surveys with the last time the survey was changed');
    }



}
