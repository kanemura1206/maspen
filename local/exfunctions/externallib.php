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
						'name'   => new external_value(PARAM_RAW, 'assign name', VALUE_DEFAULT, ""),
						'cmid'     => new external_value(PARAM_INT, 'course module id', VALUE_DEFAULT, 0),
						'userid' => new external_value(PARAM_INT, 'userid'),
						'text'   => new external_value(PARAM_RAW, 'text')
				)
		);
	}

	public static function submit_assignment($name="", $cmid=0, $userid, $text) {
		global $CFG, $DB;
		/** config.php */
		require_once("$CFG->dirroot/config.php");
		/** Include library */
		require_once("$CFG->dirroot/mod/assign/locallib.php");
		require_once("$CFG->dirroot/mod/assign/lib.php");
		
		//self::validate_parameters(self::submit_assignment_parameters(), array('name'=>$name, 'cmid'=>$cmid, 'text'=>$text));
		if($name!="" && $cmid==0){
			$cmid = (int)self::get_course_module_id_from_assign_name($name);
		}
		elseif ($name=="" && $cmid!=0){
			
		}
		else{
			throw new moodle_exception('invalid_parameter_exception');
		}

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

                $data = new stdClass();
                $data->userid   = $userid;
                $data->cmid = $cmid;
                $data->time   = time();
                $data->correct   = 0;
                $data->text  = $text;


		$data->course = (int)$cm->course;
		var_dump($data);
		$DB->insert_record('aspen_submit_t', $data);
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
	
		$data = $DB->get_records('aspen_head', array('cmid'=>$cmid));
		$list = array();
		$i = 0;
		foreach ($data as $datum){
			$user = $DB->get_record('user', array('id'=>$datum->userid),'username');
			$list[$i]['username'] = $user->username;
			$list[$i]['userid'] = $datum->userid;
			$list[$i]['code'] = $datum->code;
			$list[$i]['error'] = $datum->error;
			$list[$i]['timemodified'] = NULL;
			$i++;
		}
	
		$data = $DB->get_record('course_modules', array('id'=>$cmid, 'module'=>1), 'instance');
		$assignment = $data->instance;
		$data = $DB->get_records('assign_submission', array('assignment'=>$assignment));
		foreach ($data as $datum){
			$hit = 0;
			for($j = 0; $j < $i; $j++){
				if($list[$j]['userid'] == $datum->userid){
					$list[$j]['timemodified'] = $datum->timemodified;
					$hit = 1;
					break;
				}
			}
			if(!$hit){
				$obj = $DB->get_record('user', array('id'=>$datum->userid),'*');
				$list[$i]['username'] = $obj->username;
				$list[$i]['userid'] = $datum->userid;
				$list[$i]['code'] = 0;
				$list[$i]['error'] = 0;
				$list[$i]['timemodified'] = $datum->timemodified;
				$i++;
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
								'timemodified' => new external_value(PARAM_INT, 'timemodified', VALUE_OPTIONAL),
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

		$data = $DB->get_records('aspen', array('userid'=>$userid, 'cmid'=>$cmid));
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
	
	public static function set_run_status_parameters() {
		return new external_function_parameters(
				array(
						'userid'   => new external_value(PARAM_INT, 'userid'),
						'cmid' => new external_value(PARAM_INT, 'course module id'),
						'code'   => new external_value(PARAM_INT, 'code'),
						'errors' => new external_value(PARAM_RAW, 'errors'),
						'text'   => new external_value(PARAM_RAW, 'text')
				)
		);
	}
	
	public static function set_run_status($userid, $cmid, $code, $errors, $text) {
		global $CFG, $DB;
		
		self::validate_parameters(self::set_run_status_parameters(), array('userid'=>$userid, 'cmid'=>$cmid, 'code'=>$code, 'errors'=>$errors, 'text'=>$text));

		$obj = json_decode($errors);
		$error = count($obj->error) + count($obj->warning);
		$time = time();
		
		$data = new stdClass();
		$data->userid   = $userid;
		$data->cmid = $cmid;
		$data->time   = time();
		$data->code   = $code;
		$data->error  = $error;
		$aspen = $DB->insert_record('aspen', $data);
		
		$data->aspen = $aspen;
		$obj = $DB->get_record('aspen_head', array('userid'=>$userid, 'cmid'=>$cmid), 'id');
		if($obj == NULL){
			$DB->insert_record('aspen_head', $data);
		}
		else{
			$data->id = $obj->id;
			$DB->update_record('aspen_head', $data);
		}

		$data = new stdClass();
		$data->aspen = $aspen;
		$data->text = $text;
		$DB->insert_record('aspen_text', $data);
		
		$data = new stdClass();
		$data->aspen = $aspen;
		$data->errors = $errors;
		$DB->insert_record('aspen_error', $data);
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
	
		$data = $DB->get_record('aspen_head', array('userid'=>$userid, 'cmid'=>$cmid), 'aspen');
		$data = $DB->get_record('aspen_text', array('aspen'=>$data->aspen), 'text');
		return $data->text;
	}
	
	public static function get_head_text_returns() {
		return new external_value(PARAM_RAW, 'text');
	}

	//--------------------------------------------------------------------------------------------
	
	public static function get_submit_text_parameters() {
		return new external_function_parameters(
				array(
						'username' => new external_value(PARAM_TEXT, 'username'),
						'cmid' => new external_value(PARAM_INT, 'course module id'),
				)
		);
	}
	
	public static function get_submit_text($username, $cmid) {
		global $CFG, $DB;
		
		self::validate_parameters(self::get_submit_text_parameters(), array('username'=>$username, 'cmid'=>$cmid));
	
		$data = $DB->get_record('user', array('username'=>$username), 'id');
		$userid = $data->id;
		$data = $DB->get_record('course_modules', array('id'=>$cmid), 'instance');
		$data = $DB->get_record('assign_submission', array('assignment'=>$data->instance, 'userid'=>$userid), 'id');
		$data = $DB->get_record('assignsubmission_onlinetext', array('submission'=>$data->id), 'onlinetext');
		return $data->onlinetext;
	}
	
	public static function get_submit_text_returns() {
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

		$obj = $DB->get_record('aspen_head', array('userid'=>$userid, 'cmid'=>$cmid), 'id');
		if($obj == NULL){
			$data = new stdClass();
			$data->userid   = $userid;
			$data->cmid = $cmid;
			$data->time   = time();
			$aspen = $DB->insert_record('aspen_start', $data);

			$data->code   = 0;
			$data->error  = 0;
			$aspen = $DB->insert_record('aspen', $data);
		
			$data->aspen = $aspen;
			$DB->insert_record('aspen_head', $data);
		}
	}
	
	public static function init_aspen_returns() {
	}
	
}

