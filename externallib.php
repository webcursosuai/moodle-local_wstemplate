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
                	'feedbackid' => new external_value(PARAM_INT, 'the last day from where you want to get the attendance', VALUE_DEFAULT, 0),
                'excel' => new external_value(PARAM_INT, 'changes the format of the json', VALUE_DEFAULT , 0)
                )
        );
    }

    /**
     * Returns presence of paperattendance
     * @return json presence of paperattendance 
     */
    public static function webservice_surveycheck($courseid = 0, $feedbackid = 0, $excel = 0) {
        global $DB;

        //Parameter validation
        $params = self::validate_parameters(self::webservice_surveycheck_parameters(),
            array('courseid' => $courseid, 'feedbackid' => $feedbackid, 'excel' => $excel));

        switch(true)
        {
            case($courseid == 0 && $feedbackid == 0):
                $return = $DB->get_records_sql('SELECT c.id FROM {course} AS c
                                                INNER JOIN {course_modules} AS cm ON (c.id = cm.course)
                                                INNER JOIN {modules} AS m ON (cm.module = m.id AND m.name = ?)
                                                INNER JOIN {questionnaire} AS q ON (c.id = q.course)
                                                INNER JOIN {questionnaire_response} AS qr ON (q.id = qr.survey_id)
                                                WHERE q.intro like "<ul>%" AND c.category != 39
                                                GROUP BY c.id', array("questionnaire"));
                if(count($return) == 0){
                    $return = array("ERROR: No questionnaires have been made");
                }else{
                    $return = array_values($return);
                }
                break;
            case($courseid > 0 && $feedbackid == 0):
                $return = $DB->get_records_sql('SELECT q.id, from_unixtime(MAX(qr.submitted),"%Y-%m-%d") as fecha FROM {questionnaire} AS q
                                                INNER JOIN {course} AS c ON (q.course = c.id AND c.id = ?)
                                                INNER JOIN {questionnaire_response} AS qr ON (q.id = qr.survey_id)
                                                WHERE q.intro like "<ul>%" AND c.category != 39
                                                GROUP BY q.id', array($courseid));
                if(count($return) == 0){
                    $return = array("ERROR: No questionnaires in this course");
                }else{
                    $return = array_values($return);
                }
                break;
            case($courseid > 0 && $feedbackid > 0):
                if($excel == 0){
                    $result = $DB->get_record_sql('SELECT id,course,name,intro FROM {questionnaire} WHERE id = ?', array("id"=>$feedbackid));
                    $explode = explode("</li>",$result->intro);
                    foreach($explode as $key => $exploded){
                        $info = explode(":",$exploded);
                        $explode[$key] = strip_tags($info[1]);    
                    }
                    $result->programa = $explode[0];
                    $result->cliente = $explode[1];
                    $result->actividad = $explode[2];
                    $result->profesor1 = $explode[3];
                    $result->profesor2 = $explode[4];
                    $result->fecha = $explode[5];
                    $result->grupo = $explode[6];
                    $result->coordinadora = $explode[7];
                    unset($result->intro);
                    
                    $questions = $DB->get_records_sql('SELECT id, name,content, type_id, length, position
                                                        FROM {questionnaire_question}
                                                        WHERE survey_id = ? AND deleted = "n" order by position',array($feedbackid));
                    $count = 0;
                    foreach($questions as $question){
                        if($question->name === "EVALUACIÓN DEL PROFESOR"){
                            $count++;
                        }
                        if($question->type_id == 2  || $question->type_id == 10){
                            $responses = $DB->get_records_sql('SELECT id, response FROM {questionnaire_response_text} WHERE question_id = ?', array($question->id));
                            $input = new stdClass();
                            $input->programa = $result->programa;
                            $input->cliente =  $result->cliente;
                            $input->actividad =  $result->actividad;
                            if($count == 2 || $count == 1){
                                $input->profesor =  $result->profesor1;
                            }
                            if($count == 4 || $count == 3){
                                $input->profesor = $result->profesor2;
                            }
                            $input->length = "0";
                            $input->fecha = $result->fecha;
                            $input->grupo = $result->grupo;
                            $input->coordinadora = $result->coordinadora;
                            $input->category = $question->name;
                            $input->position = $question->position;
                            $input->question = strip_tags($question->content);
                            $input->responses = array_values($responses);
                            $return[] = $input;
                            
                        }
                        elseif($question->type_id == 8){
                            $rankquestions = $DB->get_records_sql('SELECT id, content FROM {questionnaire_quest_choice} WHERE question_id = ?', array($question->id));
                            
                            foreach($rankquestions as $rank){
                                if(preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $rank->content)){
                                    $explode = explode(")", $rank->content);
                                    $rank->content = ltrim($explode[1]);
                                }
                                $responses = $DB->get_records_sql('SELECT id, rank+1 as value FROM {questionnaire_response_rank} WHERE choice_id = ?', array($rank->id));
                                $input = new stdClass();
                                $input->programa = $result->programa;
                                $input->cliente =  $result->cliente;
                                $input->actividad =  $result->actividad;
                                if($count == 2 || $count == 1){
                                    $input->profesor =  $result->profesor1;
                                }
                                if($count == 4 || $count == 3){
                                    $input->profesor = $result->profesor2;
                                }
                                $input->length = $question->length;
                                $input->fecha = $result->fecha;
                                $input->grupo = $result->grupo;
                                $input->coordinadora = $result->coordinadora;
                                if($question->name === "EVALUACIÓN GENERAL"){
                                    $count++;
                                    if($count==1){
                                        $input->category = "AUTOEVALUACIÓN";
                                    }elseif($count == 2){
                                        $input->category = "EVALUACIÓN ACADÉMICA";
                                    }else{
                                        $input->category = "EVALUACIÓN DEL PROFESOR";
                                    }
                                }else{
                                    $input->category = $question->name;
                                }
                                $input->position = $question->position;
                                $input->question = $rank->content;
                                $input->responses = array_values($responses);
                                $return[] = $input;
                            }
                        }
                    }
                }
               else{
                   
                    $return=array();
                    $textresponses = $DB->get_records_sql('SELECT qrt.id as id, cc.name as category, c.fullname as coursename, q.name as questionnaire, qqt.response_table, qq.length, qq.position, q.intro as info, qq.name as sectioncategory, qq.content as question, qrt.response as response FROM {questionnaire} AS q 
                                                            INNER JOIN {course} AS c ON (c.id = q.course AND c.id = ? AND q.id = ?)
                                                            INNER JOIN {course_categories} AS cc ON (cc.id = c.category)
                                                            INNER JOIN {questionnaire_question} AS qq ON (qq.survey_id = q.id)
                                                            INNER JOIN {questionnaire_response_text} AS qrt ON (qrt.question_id = qq.id)
                                                            INNER JOIN {questionnaire_question_type} AS qqt ON (qqt.typeid = qq.type_id)
                                                            WHERE q.intro like "<ul>%" AND cc.id != 39', array($courseid,$feedbackid));
                    $rankresponses = $DB->get_records_sql('SELECT qrr.id as id, cc.name as category, c.fullname as coursename, q.name as questionnaire, qqt.response_table, qq.length, qq.position,q.intro as info, qq.name as sectioncategory, qqc.content as question, qrr.rank+1 as response FROM {questionnaire} AS q
                                                            INNER JOIN {course} AS c ON (c.id = q.course AND c.id = ? AND q.id = ?)
                                                            INNER JOIN {course_categories} AS cc ON (cc.id = c.category)
                                                            INNER JOIN {questionnaire_question} AS qq ON (qq.survey_id = q.id)
                                                            INNER JOIN {questionnaire_quest_choice} AS qqc ON (qqc.question_id = qq.id)
                                                            INNER JOIN {questionnaire_response_rank} AS qrr ON (qrr.choice_id = qqc.id)
                                                            INNER JOIN {questionnaire_question_type} AS qqt ON (qqt.typeid = qq.type_id)
                                                            WHERE q.intro like "<ul>%" AND cc.id != 39', array($courseid,$feedbackid));
                    $dateresponses = $DB->get_records_sql('SELECT qrd.id as id, cc.name as category, c.fullname as coursename, q.name as questionnaire, qqt.response_table, qq.length, qq.position,q.intro as info, qq.name as sectioncategory, qq.content as question, qrd.response as response FROM {questionnaire} AS q
                                                            INNER JOIN {course} AS c ON (c.id = q.course AND c.id = ? AND q.id = ?)
                                                            INNER JOIN {course_categories} AS cc ON (cc.id = c.category)
                                                            INNER JOIN {questionnaire_question} AS qq ON (qq.survey_id = q.id)
                                                            INNER JOIN {questionnaire_response_date} AS qrd ON (qrd.question_id = qq.id)
                                                            INNER JOIN {questionnaire_question_type} AS qqt ON (qqt.typeid = qq.type_id)
                                                            WHERE q.intro like "<ul>%" AND cc.id != 39', array($courseid,$feedbackid));
                    $boolresponses = $DB->get_records_sql('SELECT qrd.id as id, cc.name as category, c.fullname as coursename, q.name as questionnaire, qqt.response_table, qq.length, qq.position,q.intro as info, qq.name as sectioncategory, qq.content as question, qrd.choice_id as response FROM {questionnaire} AS q
                                                            INNER JOIN {course} AS c ON (c.id = q.course AND c.id = ? AND q.id = ?)
                                                            INNER JOIN {course_categories} AS cc ON (cc.id = c.category)
                                                            INNER JOIN {questionnaire_question} AS qq ON (qq.survey_id = q.id)
                                                            INNER JOIN {questionnaire_response_bool} AS qrd ON (qrd.question_id = qq.id)
                                                            INNER JOIN {questionnaire_question_type} AS qqt ON (qqt.typeid = qq.type_id)
                                                            WHERE q.intro like "<ul>%" AND cc.id != 39', array($courseid,$feedbackid));
                    $singleresponses = $DB->get_records_sql('SELECT qrs.id as id, cc.name as category, c.fullname as coursename, q.name as questionnaire, qqt.response_table, qq.length, qq.position,q.intro as info, qq.name as sectioncategory, qq.content as question, qqc.content as response FROM {questionnaire} AS q
                                                            INNER JOIN {course} AS c ON (c.id = q.course AND c.id = ? AND q.id = ?)
                                                            INNER JOIN {course_categories} AS cc ON (cc.id = c.category)
                                                            INNER JOIN {questionnaire_question} AS qq ON (qq.survey_id = q.id)
                                                            INNER JOIN {questionnaire_quest_choice} AS qqc ON (qqc.question_id = qq.id)
                                                            INNER JOIN {questionnaire_resp_single} AS qrs ON (qrs.choice_id = qqc.id)
                                                            INNER JOIN {questionnaire_question_type} AS qqt ON (qqt.typeid = qq.type_id)
                                                            WHERE q.intro like "<ul>%" AND cc.id != 39', array($courseid,$feedbackid));
                    $multiresponses = $DB->get_records_sql('SELECT qrm.id as id, cc.name as category, c.fullname as coursename, q.name as questionnaire, qqt.response_table, qq.length, qq.position,q.intro as info, qq.name as sectioncategory, qq.content as question, qqc.content as response FROM {questionnaire} AS q
                                                            INNER JOIN {course} AS c ON (c.id = q.course AND c.id = ? AND q.id = ?)
                                                            INNER JOIN {course_categories} AS cc ON (cc.id = c.category)
                                                            INNER JOIN {questionnaire_question} AS qq ON (qq.survey_id = q.id)
                                                            INNER JOIN {questionnaire_quest_choice} AS qqc ON (qqc.question_id = qq.id)
                                                            INNER JOIN {questionnaire_resp_multiple} AS qrm ON (qrm.choice_id = qqc.id)
                                                            INNER JOIN {questionnaire_question_type} AS qqt ON (qqt.typeid = qq.type_id)
                                                            WHERE q.intro like "<ul>%" AND cc.id != 39', array($courseid,$feedbackid));
                    $result = array_merge($textresponses,$rankresponses,$dateresponses,$boolresponses,$singleresponses,$multiresponses);
                    foreach($result as $position => $response){
                        $result[$position]->question = strip_tags($response->question);
                        if($response->response_table === 'response_rank' && $response->sectioncategory !== 'EVALUACIÓN GENERAL'){
                            $explode = explode(")", $response->question);
                            $response->question = ltrim($explode[1]);
                        }
                        $explode = explode("</li>",$response->info);
                        foreach($explode as $key => $item){ 
                            $explode[$key] = strip_tags($item);
                        }
                        foreach($explode as $key => $exploded){
                            $info = explode(":",$exploded);
                            $explode[$key] = $info[1];
                            
                        }
                        $obj = new stdClass();
                        $obj->category = $response->category;
                        $obj->coursename = $response->coursename;
                        $obj->questionnaire = $response->questionnaire;
                        $obj->response_table = $response->response_table;
                        $obj->length = $response->length;
                        $obj->position = $response->position;
                        $obj->sectioncategory = $response->sectioncategory;
                        $obj->question = $response->question;
                        $obj->response = $response->response;
                        $obj->programa = $explode[0];
                        $obj->cliente = $explode[1];
                        $obj->actividad = $explode[2];
                        if(strlen($explode[4]) > 5){
                            if($response->position == 6 || $response->position == 7 || $response->question === "El Profesor/Facilitador 2"){
                                $obj->profesor = $explode[4];
                            }else{
                                $obj->profesor = $explode[3];
                            }
                        }else{
                            $obj->profesor = $explode[3];
                        }
                        $obj->fecha = $explode[5];
                        $obj->grupo = $explode[6];
                        $obj->coordinadora = $explode[7];
                        unset($obj->info);
                        $return[] = $obj;
                    }
               }
                if(count($return) == 0){
                    $return = array("ERROR: This questionnaires in not in this course");
                }
                break;
            case($courseid == 0 && $feedbackid > 0):
                $return = array("ERROR: Please enter a valid course id (1-∞)");
                break;
            case($courseid < 0 || $feedbackid < 0):
                $return = array("ERROR: Please enter positive values (1-∞)");
                break;          
        }
        echo json_encode($return);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function webservice_surveycheck_returns() {
        return new external_value(PARAM_TEXT, 'json encoded array that returns, courses and its surveys with the last time the survey was changed');
    }



}
