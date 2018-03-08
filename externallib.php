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
    public static function webservice_surveycheck_parameters() {
        return new external_function_parameters(
                array(
                	'courseid' => new external_value(PARAM_INT, 'the initial date from where you want to get the attendance', VALUE_DEFAULT, 0),
                	'feedbackid' => new external_value(PARAM_INT, 'the last day from where you want to get the attendance', VALUE_DEFAULT, 0)
                )
        );
    }

    /**
     * Returns presence of paperattendance
     * @return json presence of paperattendance 
     */
    public static function webservice_surveycheck($courseid = 0, $feedbackid = 0) {
        global $DB;

        //Parameter validation
        $params = self::validate_parameters(self::webservice_surveycheck_parameters(),
            array('courseid' => $courseid, 'feedbackid' => $feedbackid));

        switch(true)
        {
            case($courseid == 0 && $feedbackid == 0):
                $return = $DB->get_records_sql('SELECT c.id FROM {course} AS c
                                                INNER JOIN {course_modules} AS cm ON (c.id = cm.course)
                                                INNER JOIN {modules} AS m ON (cm.module = m.id AND m.name = ?)
                                                INNER JOIN {feedback} AS f ON (c.id = f.course)
                                                GROUP BY c.id', array("feedback"));
                break;
            case($courseid > 0 && $feedbackid == 0):
                $return = $DB->get_records_sql('SELECT c.id, f.id, FROM_UNIXTIME(max(fc.timemodified)) FROM {course} AS c
                                                INNER JOIN {course_modules} AS cm ON (c.id = cm.course AND c.id = ?)
                                                INNER JOIN {modules} AS m ON (cm.module = m.id AND m.name = ?)
                                                INNER JOIN {feedback} AS f ON (c.id = f.course)
                                                LEFT JOIN {feedback_completed} AS fc ON (f.id = fc.feedback)
                                                GROUP BY f.id', array($courseid,"feedback"));
                break;
            case($courseid > 0 && $feedbackid >0):
                $return = $DB->get_records_sql('SELECT c.id, f.id, f.name, fi.id, fi.name, fi.presentation, fi.typ, fv.id, fv.value FROM {course} AS c
                                                INNER JOIN {course_modules} AS cm ON (c.id = cm.course AND c.id = ?)
                                                INNER JOIN {modules} AS m ON (cm.module = m.id AND m.name = ?)
                                                INNER JOIN {feedback} AS f ON (c.id = f.course AND f.id = ?)
                                                INNER JOIN {feedback_completed} AS fc ON (f.id = fc.feedback)
                                                INNER JOIN {feedback_item} AS fi ON (f.id = fi.feedback)
                                                INNER JOIN {feedback_value} AS fv ON (fi.id = fv.item)
                                                GROUP BY fv.id', array($courseid,"feedback",$feedbackid));
                break;
            case($courseid == 0 && $feedbackid > 0):
                $return = array("ERROR: Please enter a valid course id (1-∞)");
                break;
            case($courseid < 0 || $feedbackid < 0):
                $return = array("ERROR: Please enter positive values (1-∞)");
                break;          
        }
        echo json_encode($return);
        //return $return;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function webservice_surveycheck_returns() {
        return new external_value(PARAM_TEXT, 'json encoded array that returns, courses and its surveys with the last time the survey was changed');
    }

    
    public static function webservice_intranet_parameters() {
        return new external_function_parameters(
            array(
                'idnumber' => new external_value(PARAM_INT, 'the initial date from where you want to get the attendance', VALUE_DEFAULT, 0)
            )
            );
    }
    /**
     * Returns presence of paperattendance
     * @return json presence of paperattendance
     */
    public static function webservice_intranet($idnumber=0) {
        global $DB;
        //Parameter validation
        $params = self::validate_parameters(self::webservice_intranet_parameters(),
            array('idnumber' => $idnumber));
        $return = $DB->get_record('course', array('idnumber' => $idnumber));
        echo json_encode($return);
    }
    public static function webservice_intranet_returns() {
        return new external_value(PARAM_TEXT, 'json encoded array that returns, courses and its surveys with the last time the survey was changed');
    }
    
        
}
