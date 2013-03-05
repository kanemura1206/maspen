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

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->libdir . "/filelib.php");

class local_exfunctions_external extends external_api {

   public static function view_assignment_parameters() {
      return new external_function_parameters(
               array(
                        'cmid'   => new external_value(PARAM_INT, 'course module id'),
                        'userid' => new external_value(PARAM_INT, 'userid'),
               )
      );
   }

   public static function view_assignment($cmid, $userid) {
      global $CFG, $USER, $DB;

      require_once("$CFG->dirroot/config.php");
      require_once("$CFG->dirroot/mod/assign/locallib.php");
      require_once("$CFG->libdir/datalib.php");
      require_once("$CFG->libdir/dml/moodle_database.php");

      self::validate_parameters(self::view_assignment_parameters(), array('cmid'=>$cmid, 'userid'=>$userid));

      $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);

      $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

      $context = context_module::instance($cm->id);

      require_capability('mod/assign:view', $context);

      $assign = new assign($context,$cm,$course);

      // Mark as viewed
      $completion=new completion_info($course);
      $completion->set_module_viewed($cm);

      $instance = $assign->get_instance();
      $data = $DB->get_record('assign_submission', array('assignment'=>$instance->id, 'userid'=>$userid), 'timemodified');

      $list = array();
      $list['duedate']      = $instance->duedate;
      $list['timemodified'] = $data->timemodified;
      return $list;
   }

   public static function view_assignment_returns() {
      return new external_single_structure(
               array(
                        'duedate' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                        'timemodified' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
               )
      );
   }

   //--------------------------------------------------------------------------------------

   public static function submit_assignment_parameters() {
      return new external_function_parameters(
               array(
                        'cmid'   => new external_value(PARAM_INT, 'course module id'),
                        'userid' => new external_value(PARAM_INT, 'userid'),
                        'text'   => new external_value(PARAM_RAW, 'text'),
                        'output' => new external_value(PARAM_RAW, 'output'),
               )
      );
   }

   public static function submit_assignment($cmid, $userid, $text, $output) {
      global $CFG, $DB;
      /** config.php */
      require_once("$CFG->dirroot/config.php");
      /** Include library */
      require_once("$CFG->dirroot/mod/assign/locallib.php");
      require_once("$CFG->dirroot/mod/assign/lib.php");

      self::validate_parameters(self::submit_assignment_parameters(), array('cmid'=>$cmid, 'userid'=>$userid, 'text'=>$text, 'output'=>$output));

      //	$url = new moodle_url('/mod/assign/view.php', array('cmid' => $cmid)); // Base URL

      // get the request parameters
      $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);

      $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

      // Auth
      //	$PAGE->set_url($url);

      $context = context_module::instance($cm->id);

      require_capability('mod/assign:view', $context);

      $assign = new assign($context,$cm,$course);

      // Mark as viewed
      $completion=new completion_info($course);
      $completion->set_module_viewed($cm);

      // Get the assign to render the page
      $assign->submit_assign($cmid, $userid, $text);

      $data = $DB->get_record('lti', array('course'=>$cm->course, 'name'=>$cm->name),'intro');
      $intro = $data->intro;
      $intro = strip_tags($intro);
      $intro = str_replace("\r", "", $intro);
      $list = explode("\n", $intro);
      
      $answer = array();
      $i = 0;
      $key = 0;
      $answer = array();

      while($i < count($list)){
         if($key == 1){
            $answer[] = $list[$i];
         }
         if($list[$i] == "#Output"){
            $key = 1;
         }
         $i++;
      }
      $output = json_decode($output);

      $correct  = 0;
      if(count($answer) == count($output)){
         $i = 0;
         $correct = 1; 
         while($i < count($answer)){
            if($answer[$i] != $output[$i]){
               $correct = 0;
               break;
            }
            $i++;
         }
      }
      
      $data = new stdClass();
      $data->userid  = $userid;
      $data->cmid    = $cmid;
      $data->time    = time();
      $data->correct = $correct;
      $data->course  = (int)$cm->course;
      $data->text    = $text;

      $DB->insert_record('aspen_submit', $data);
   }

   public static function submit_assignment_returns() {
   }

   //--------------------------------------------------------------------------------------------

   public static function get_runking_parameters() {
      return new external_function_parameters(
               array(
                        'cmid' => new external_value(PARAM_INT, 'course module id'),
               )
      );
   }

   public static function get_runking($cmid) {
      global $CFG, $DB;

      self::validate_parameters(self::get_runking_parameters(), array('cmid'=>$cmid));

      $list = array();
      $i = 0;
      
      $data = $DB->get_record('course_modules', array('id'=>$cmid), 'course');
      $data = $DB->get_records('enrol', array('courseid' => $data->course), '', 'id');
      foreach ($data as $obj){
         $enrolid = (int)$obj->id;
         $users = $DB->get_records('user_enrolments', array('enrolid' => $enrolid));
         foreach ($users as $user){
            $userid = $user->userid;
            if($userid != 1 && $userid != 2){
               $username = $DB->get_record('user', array('id'=>$userid),'username')->username;
               $list[$i]['username'] = $username;
               $list[$i]['userid'] = $userid;
               $list[$i]['code'] = 0;
               $list[$i]['error'] = 0;
               $list[$i]['time'] = 0;
               $list[$i]['correct'] = 0;
               $run = $DB->get_records('aspen_run', array('cmid'=>$cmid, 'userid'=>$userid));
               if($run != NULL){
                  $list[$i]['code'] = end($run)->code;
                  $list[$i]['error'] = end($run)->error;
                  $submit = $DB->get_records('aspen_submit', array('cmid'=>$cmid, 'userid'=>$userid));
                  if($submit != NULL){
                     $list[$i]['time'] = end($submit)->time - reset($run)->time;
                     $list[$i]['correct'] = end($submit)->correct;
                  }
               }
            
               $i++;
            }
         }
      }

      return $list;
   }

   public static function get_runking_returns() {
      return new external_multiple_structure(
               new external_single_structure(
                        array(
                                 'username'  => new external_value(PARAM_TEXT, 'username'),
                                 'userid'=> new external_value(PARAM_INT, 'userid'),
                                 'code'  => new external_value(PARAM_INT, 'code', VALUE_OPTIONAL),
                                 'error' => new external_value(PARAM_INT, 'error', VALUE_OPTIONAL),
                                 'time' => new external_value(PARAM_INT, 'time', VALUE_OPTIONAL),
                                 'correct'  => new external_value(PARAM_INT, 'correct', VALUE_OPTIONAL),
                        )
               )
      );
   }

   //--------------------------------------------------------------------------------------------

   public static function get_run_status_parameters() {
      return new external_function_parameters(
               array(
                        'userid' => new external_value(PARAM_INT, 'userid'),
                        'cmid' => new external_value(PARAM_INT, 'course module id'),
               )
      );
   }

   public static function get_run_status($userid, $cmid) {
      global $CFG, $DB;

      self::validate_parameters(self::get_run_status_parameters(), array('userid'=>$userid, 'cmid'=>$cmid));

      $data = $DB->get_records('aspen_run', array('userid'=>$userid, 'cmid'=>$cmid));
      $list = array();
      $i = 0;
      foreach ($data as $datum){
         $list[$i]['time'] = $datum->time;
         $list[$i]['code'] = $datum->code;
         $list[$i]['error'] = $datum->error;
         $i++;
      }
      return $list;
   }

   public static function get_run_status_returns() {
      return new external_multiple_structure(
               new external_single_structure(
                        array(
                                 'time'  => new external_value(PARAM_INT, 'time'),
                                 'code'  => new external_value(PARAM_INT, 'code'),
                                 'error' => new external_value(PARAM_INT, 'error'),
                        )
               )
      );
   }
   
   //--------------------------------------------------------------------------------------------
   
   public static function get_total_runking_parameters() {
      return new external_function_parameters(
               array(
                        'cmid' => new external_value(PARAM_INT, 'course module id'),
               )
      );
   }
   
   public static function get_total_runking($cmid) {
      global $CFG, $DB;
   
      self::validate_parameters(self::get_total_runking_parameters(), array('cmid'=>$cmid));
   
      $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
   
      $course = $cm->course;
   
      $list = array();
      $i = 0;
   
      $data = $DB->get_records('enrol', array('courseid' => $course), '', 'id');
      foreach ($data as $obj){
         $users = $DB->get_records('user_enrolments', array('enrolid' => $obj->id), '', 'userid');
         foreach ($users as $user){
            $userid = (int)$user->userid;
            $username = $DB->get_record('user', array('id'=>$userid),'username')->username;
            $list[$i]['username'] = $username;
            $list[$i]['userid'] = $userid;
            $list[$i]['submission'] = 0;
            $list[$i]['num_of_correct'] = 0;
            $list[$i]['per_of_correct'] = 0;
   
            $cms = $DB->get_records('course_modules', array('course'=>$course, 'module'=>1));
            foreach ($cms as $cm){
               $submit = $DB->get_records('aspen_submit', array('cmid'=>$cm->id, 'userid'=>$userid));
               if($submit != NULL){
                  $list[$i]['submission'] += 1;
                  $list[$i]['num_of_correct'] += end($submit)->correct;
               }
            }
            if($list[$i]['submission'] != 0){
               $list[$i]['per_of_correct'] = (int)round($list[$i]['num_of_correct'] / $list[$i]['submission'] * 100);
            }
            $i++;
         }
      }

      return $list;
   }
   
   public static function get_total_runking_returns() {
      return new external_multiple_structure(
               new external_single_structure(
                        array(
                                 'username'  => new external_value(PARAM_TEXT, 'username'),
                                 'userid'=> new external_value(PARAM_INT, 'userid'),
                                 'submission'  => new external_value(PARAM_INT, 'code'),
                                 'num_of_correct' => new external_value(PARAM_INT, 'num_of_correct'),
                                 'per_of_correct' => new external_value(PARAM_INT, 'per_of_correct'),
                        )
               )
      );
   }

   //--------------------------------------------------------------------------------------------

   public static function set_run_status_parameters() {
      return new external_function_parameters(
               array(
                        'userid'   => new external_value(PARAM_INT, 'userid'),
                        'cmid' => new external_value(PARAM_INT, 'course module id'),
                        'code'   => new external_value(PARAM_INT, 'code'),
                        'codetext'   => new external_value(PARAM_RAW, 'codetext'),
                        'error' => new external_value(PARAM_INT, 'error'),
                        'errortext'   => new external_value(PARAM_RAW, 'errortext')
               )
      );
   }

   public static function set_run_status($userid, $cmid, $code, $codetext, $error, $errortext) {
      global $CFG, $DB;

      self::validate_parameters(self::set_run_status_parameters(), array('userid'=>$userid, 'cmid'=>$cmid, 'code'=>$code, 'codetext'=>$codetext, 'error'=>$error, 'errortext'=>$errortext));

      $data = new stdClass();
      $data->userid   = $userid;
      $data->cmid = $cmid;
      $data->time   = time();
      $data->code   = $code;
      $data->codetext = $codetext;
      $data->error  = $error;
      $data->errortext = $errortext;

      $aspen = $DB->insert_record('aspen_run', $data);
   }

   public static function set_run_status_returns() {
   }

   //--------------------------------------------------------------------------------------------

   public static function get_head_text_parameters() {
      return new external_function_parameters(
               array(
                        'userid' => new external_value(PARAM_INT, 'userid'),
                        'cmid' => new external_value(PARAM_INT, 'course module id'),
               )
      );
   }

   public static function get_head_text($userid, $cmid) {
      global $CFG, $DB;

      self::validate_parameters(self::get_head_text_parameters(), array('userid'=>$userid, 'cmid'=>$cmid));

      $data = $DB->get_records('aspen_run', array('userid'=>$userid, 'cmid'=>$cmid));

      return end($data)->codetext;
   }

   public static function get_head_text_returns() {
      return new external_value(PARAM_RAW, 'text');
   }

   //--------------------------------------------------------------------------------------------

   public static function init_aspen_parameters() {
      return new external_function_parameters(
               array(
                        'userid'   => new external_value(PARAM_INT, 'userid'),
                        'cmid' => new external_value(PARAM_INT, 'course module id')
               )
      );
   }

   public static function init_aspen($userid, $cmid) {
      global $CFG, $DB;

      self::validate_parameters(self::init_aspen_parameters(), array('userid'=>$userid, 'cmid'=>$cmid));

      $obj = $DB->get_record('aspen_run', array('userid'=>$userid, 'cmid'=>$cmid), 'id');
      if($obj == NULL){
         $data = new stdClass();
         $data->userid   = $userid;
         $data->cmid = $cmid;
         $data->time   = time();
         $data->code   = 0;
         $data->codetext   = ' ';
         $data->error  = 0;
         $data->errortext   = ' ';
         $aspen = $DB->insert_record('aspen_run', $data);
      }
   }

   public static function init_aspen_returns() {
   }
}

