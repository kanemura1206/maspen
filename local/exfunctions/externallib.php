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
						'name' => new external_value(PARAM_TEXT, 'assign name', VALUE_DEFAULT, ""),
						'id'   => new external_value(PARAM_INT, 'id', VALUE_DEFAULT, 0),
						'userid' => new external_value(PARAM_INT, 'userid'),
				)
		);
	}

	public static function view_assignment($name="", $id=0, $userid) {
		global $CFG, $USER, $DB;

 		require_once("$CFG->dirroot/config.php");
		require_once("$CFG->dirroot/mod/assign/locallib.php");
		require_once("$CFG->libdir/datalib.php");
		require_once("$CFG->libdir/dml/moodle_database.php");

	    //self::validate_parameters(self::view_assignment_parameters(), array('name'=>$name, 'id'=>$, 'userid'=>$userid));

 		if($name!="" && $id==0){
			$id = self::get_course_module_id_from_assign_name($name);
		}
		elseif ($name=="" && $id!=0){
			
		}
		else{
			throw new moodle_exception('invalid params');
		}

		$cm = get_coursemodule_from_id('assign', $id, 0, false, MUST_EXIST);

		$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

		$context = context_module::instance($cm->id);

		require_capability('mod/assign:view', $context);
		
		$assign = new assign($context,$cm,$course);
		
		// Mark as viewed
		$completion=new completion_info($course);
		$completion->set_module_viewed($cm);
		
		$instance = $assign->get_instance();
 		$data = $DB->get_record('assign_submission', array('assignment'=>$instance->id, 'userid'=>$userid), '*');
		$text = $DB->get_record('assignsubmission_onlinetext', array('assignment'=>$instance->id, 'submission'=>$data->id), 'onlinetext');

		$list = array();
		$list['name']         = $instance->name;
		$list['intro']        = $instance->intro;
		$list['status']       = $data->status;
		$list['duedate']      = $instance->duedate;
		$list['timemodified'] = $data->timemodified;
		$list['text']         = $text->onlinetext;
		return $list;
	}

	public static function view_assignment_returns() {
			return new external_single_structure(
				array(
						'name'    => new external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
						'intro'   => new external_value(PARAM_RAW, '', VALUE_OPTIONAL),
						'status'  => new external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
						'duedate' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
						'timemodified' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),	
						'text'    => new external_value(PARAM_RAW, '', VALUE_OPTIONAL)
				)
		);
	}

	//--------------------------------------------------------------------------------------

	public static function submit_assignment_parameters() {
		return new external_function_parameters(
				array(
						'name'   => new external_value(PARAM_RAW, 'assign name', VALUE_DEFAULT, ""),
						'id'     => new external_value(PARAM_INT, 'id', VALUE_DEFAULT, 0),
						'userid' => new external_value(PARAM_INT, 'userid'),
						'text'   => new external_value(PARAM_RAW, 'text')
				)
		);
	}

	public static function submit_assignment($name="", $id=0, $userid, $text) {
		global $CFG, $DB;
		/** config.php */
		require_once("$CFG->dirroot/config.php");
		/** Include library */
		require_once("$CFG->dirroot/mod/assign/locallib.php");
		require_once("$CFG->dirroot/mod/assign/lib.php");
		
		//self::validate_parameters(self::submit_assignment_parameters(), array('name'=>$name, 'id'=>$id, 'text'=>$text));
		if($name!="" && $id==0){
			$id = (int)self::get_course_module_id_from_assign_name($name);
		}
		elseif ($name=="" && $id!=0){
			
		}
		else{
			throw new moodle_exception('invalid_parameter_exception');
		}

		//	$url = new moodle_url('/mod/assign/view.php', array('id' => $id)); // Base URL

		// get the request parameters
		$cm = get_coursemodule_from_id('assign', $id, 0, false, MUST_EXIST);

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
		$assign->submit_assign($id, $userid, $text);
		return self::view_assignment('', $id, $userid);
	}

	public static function submit_assignment_returns() {
		return new external_single_structure(
				array(
						'name'    => new external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
						'intro'   => new external_value(PARAM_RAW, '', VALUE_OPTIONAL),
						'status'  => new external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
						'duedate' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
						'timemodified' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),	
						'text'    => new external_value(PARAM_RAW, '', VALUE_OPTIONAL)
				)
		);
	}

	//--------------------------------------------------------------------------------------------
	
	public static function get_run_runking_parameters() {
		return new external_function_parameters(
				array(
						'id' => new external_value(PARAM_INT, 'id'),
				)
		);
	}
	
	public static function get_run_runking($id) {
		global $CFG, $DB;
	
		self::validate_parameters(self::get_run_runking_parameters(), array('id'=>$id));
	
		$data = $DB->get_records_sql("SELECT * FROM mdl_aspen_head WHERE module=$id ORDER BY score DESC");
		$list = array();
		$i = 0;
		foreach ($data as $datum){
			$user =  $DB->get_record_sql("SELECT username FROM mdl_user WHERE id=$datum->user");
			$list[$i]['username']  = $user->username;
			$list[$i]['userid']= $datum->user;
			$list[$i]['time']  = $datum->time;
			$list[$i]['code']  = $datum->code;
			$list[$i]['error'] = $datum->error;
			$list[$i]['score'] = $datum->score;
			$i++;
		}
		
		return $list;
	}
	
	public static function get_run_runking_returns() {
		return new external_multiple_structure(
				new external_single_structure(
						array(
								'username'  => new external_value(PARAM_TEXT, 'username'),
								'userid'=> new external_value(PARAM_INT, 'userid'),
								'time'  => new external_value(PARAM_INT, 'time'),
								'code'  => new external_value(PARAM_INT, 'code'),
								'error' => new external_value(PARAM_INT, 'error'),
								'score' => new external_value(PARAM_INT, 'score'),
						)
				)
		);
	}
	
	//--------------------------------------------------------------------------------------------
	
	public static function get_submit_runking_parameters() {
		return new external_function_parameters(
				array(
						'id' => new external_value(PARAM_INT, 'id'),
						'userid' => new external_value(PARAM_INT, 'userid', VALUE_DEFAULT, 0),
				)
		);
	}
	
	public static function get_submit_runking($id, $userid=0) {
		global $CFG, $DB;
	
		self::validate_parameters(self::get_submit_runking_parameters(), array('id'=>$id, 'userid'=>$userid));
		
		$data = $DB->get_record_sql("SELECT instance FROM mdl_course_modules WHERE id='$id' AND module=1");
		$assignment = $data->instance;
		$data = $DB->get_records_sql("SELECT * FROM mdl_assign_submission WHERE assignment=$assignment ORDER BY timemodified LIMIT 3");
		$list = array();
		$i = 0;
		foreach ($data as $datum){
			$user = $DB->get_record_sql("SELECT username FROM mdl_user WHERE id=$datum->userid");
			$list[$i]['username'] = $user->username;
			$list[$i]['timemodified'] = $datum->timemodified;
			$i++;
		}
		
		while ($i < 3){
			$list[$i]['username']  = NULL;
			$list[$i]['timemodified'] = NULL;
			$i++;
		}
		
		
		if($userid != 0){
			$data = $DB->get_record_sql("SELECT * FROM mdl_assign_submission WHERE assignment=$assignment AND userid=$userid");
			$user = $DB->get_record_sql("SELECT username FROM mdl_user WHERE id=$userid");
			$list[3]['username'] = $user->username;
			$list[3]['timemodified'] = $data->timemodified;
		}
		
		return $list;
	}
	
	public static function get_submit_runking_returns() {
		return new external_multiple_structure(
				new external_single_structure(
					array(
						'username' => new external_value(PARAM_TEXT, 'user name'),
						'timemodified' => new external_value(PARAM_INT, 'time modified'),
					)
				)
		);
	}
	
	//--------------------------------------------------------------------------------------------
	
	public static function get_run_status_parameters() {
		return new external_function_parameters(
				array(
						'userid' => new external_value(PARAM_INT, 'userid'),
						'id' => new external_value(PARAM_INT, 'id'),
				)
		);
	}
	
	public static function get_run_status($userid, $id) {
		global $CFG, $DB;

		self::validate_parameters(self::get_run_status_parameters(), array('userid'=>$userid, 'id'=>$id));

		$data = $DB->get_records_sql("SELECT * FROM `mdl_aspen` WHERE user='$userid' AND module='$id'");
		$list = array();
		$i = 0;
		foreach ($data as $datum){
			$list[$i]['time'] = $datum->time;
			$list[$i]['code'] = $datum->code;
			$list[$i]['error'] = $datum->error;
			$list[$i]['score'] = $datum->score;
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
								'score' => new external_value(PARAM_INT, 'score'),
						)
				)
		);
	}
	
	//--------------------------------------------------------------------------------------------
	
	public static function set_run_status_parameters() {
		return new external_function_parameters(
				array(
						'user'   => new external_value(PARAM_INT, 'user'),
						'module' => new external_value(PARAM_INT, 'module'),
						'code'   => new external_value(PARAM_INT, 'code'),
						'error'  => new external_value(PARAM_INT, 'error'),
						'text'   => new external_value(PARAM_RAW, 'text')
				)
		);
	}
	
	public static function set_run_status($user, $module, $code, $error, $text) {
		global $CFG, $DB;
		
		self::validate_parameters(self::set_run_status_parameters(), array('user'=>$user, 'module'=>$module, 'code'=>$code, 'error'=>$error, 'text'=>$text));

		$time = time();
		$score = $code - $error;
		$DB->execute("INSERT INTO `mdl_aspen` (`id`, `user`, `module`, `time`, `code`, `error`, `score`) VALUES (NULL, '$user', '$module', '$time', '$code', '$error', '$score')");
		$obj = $DB->get_record_sql("SELECT id FROM `mdl_aspen_head` WHERE user='$user' AND module='$module'");
		if($obj == NULL){
			$DB->execute("INSERT INTO `mdl_aspen_head` (`id`, `user`, `module`, `time`, `code`, `error`, `score`) VALUES (NULL, '$user', '$module', '$time', '$code', '$error', '$score')");
		}
		else{
			$DB->execute("UPDATE `mdl_aspen_head` SET `time`='$time',`code`='$code',`error`='$error', `score`='$score' WHERE `user`=$user AND `module`=$module");
		}
		
		$obj = $DB->get_record_sql("SELECT id FROM `mdl_aspen` WHERE user='$user' AND module='$module' AND time='$time'");
		$id = $obj->id;
		echo $DB->execute("INSERT INTO `mdl_aspen_text` (`id`, `text`) VALUES ('?', '?')", array($id, $text));
		
		/* 	$data = new stdClass();
		$data->user   = $user;
		$data->module = $module;
		$data->time   = time();
		$data->code   = $code;
		$data->error  = $error;
		$data->score  = $code - $error;var_dump($data);
		$id = $DB->insert_record('aspen', $data);echo "id = $id\n";
		
		$obj = $DB->get_record_sql("SELECT id FROM `mdl_aspen_head` WHERE user='$user' AND module='$module'");
		if($obj == NULL){
		$DB->insert_record('aspen_head', $data);
		}
		else{
		$data->id = $obj->id;
		$DB->update_record('aspen_head', $data);
		} */
	}
	
	public static function set_run_status_returns() {
	}
	
	//--------------------------------------------------------------------------------------------

	function get_course_module_id_from_assign_name($name) {
		global $CFG, $DB;
		require_once("$CFG->libdir/dml/moodle_database.php");
		$assign = $DB->get_record_sql("SELECT * FROM mdl_assign WHERE name='$name'");
		$instance = $assign->id;
		$course_modules = $DB->get_record_sql("SELECT * FROM mdl_course_modules WHERE module=1 AND instance=$instance");
		$id = $course_modules->id;
		return $id;
	}
	
	//--------------------------------------------------------------------------------------------
	
	function decode_timestamp($timestamp) {
		$list = array();
		$time = getdate($timestamp);
		$list['year']  = $time['year'];
		$list['month'] = sprintf('%02d', $time['mon']);
		$list['day']   = sprintf('%02d', $time['mday']);
		$list['hours'] = sprintf('%02d', $time['hours']);
		$list['minutes'] = sprintf('%02d', $time['minutes']);
		$list['seconds'] = sprintf('%02d', $time['seconds']);
		$list['weekday'] = $time['wday'];

		return $list;
	}
}

