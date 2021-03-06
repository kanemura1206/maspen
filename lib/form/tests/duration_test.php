<?php
// This file is part of Moodle - http://moodle.org/
//
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
 * Unit tests for forms lib.
 *
 * This file contains all unit test related to forms library.
 *
 * @package    core_form
 * @category   phpunit
 * @copyright  2009 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/form/duration.php');

/**
 * Unit tests for MoodleQuickForm_duration
 *
 * Contains test cases for testing MoodleQuickForm_duration
 *
 * @package    core_form
 * @category   unittest
 * @copyright  2009 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class duration_form_element_testcase extends basic_testcase {
    /** @var MoodleQuickForm_duration Keeps reference of MoodleQuickForm_duration object */
    private $element;

    /**
     * Initalize test wide variable, it is called in start of the testcase
     */
    function setUp() {
        $this->element = new MoodleQuickForm_duration();
    }

    /**
     * Clears the data set in the setUp() method call.
     * @see duration_form_element_test::setUp()
     */
    function tearDown() {
        $this->element = null;
    }

    /**
     * Testcase for testing contructor.
     * @expectedException coding_exception
     * @retrun void
     */
    function test_constructor() {
        // Test trying to create with an invalid unit.
        $this->element = new MoodleQuickForm_duration('testel', null, array('defaultunit' => 123));
    }

    /**
     * Testcase for testing units (seconds, minutes, hours and days)
     */
    function test_get_units() {
        $units = $this->element->get_units();
        ksort($units);
        $this->assertEquals($units, array(1 => get_string('seconds'), 60 => get_string('minutes'),
            3600 => get_string('hours'), 86400 => get_string('days'), 604800 => get_string('weeks')));
    }

    /**
     * Testcase for testing conversion of seconds to the best possible unit
     */
    function test_seconds_to_unit() {
        $this->assertEquals($this->element->seconds_to_unit(0), array(0, 60)); // Zero minutes, for a nice default unit.
        $this->assertEquals($this->element->seconds_to_unit(1), array(1, 1));
        $this->assertEquals($this->element->seconds_to_unit(3601), array(3601, 1));
        $this->assertEquals($this->element->seconds_to_unit(60), array(1, 60));
        $this->assertEquals($this->element->seconds_to_unit(180), array(3, 60));
        $this->assertEquals($this->element->seconds_to_unit(3600), array(1, 3600));
        $this->assertEquals($this->element->seconds_to_unit(7200), array(2, 3600));
        $this->assertEquals($this->element->seconds_to_unit(86400), array(1, 86400));
        $this->assertEquals($this->element->seconds_to_unit(90000), array(25, 3600));

        $this->element = new MoodleQuickForm_duration('testel', null, array('defaultunit' => 86400));
        $this->assertEquals($this->element->seconds_to_unit(0), array(0, 86400)); // Zero minutes, for a nice default unit.
    }

    /**
     * Testcase to check generated timestamp
     */
    function test_exportValue() {
        $el = new MoodleQuickForm_duration('testel');
        $el->_createElements();
        $values = array('testel' => array('number' => 10, 'timeunit' => 1));
        $this->assertEquals($el->exportValue($values), array('testel' => 10));
        $values = array('testel' => array('number' => 3, 'timeunit' => 60));
        $this->assertEquals($el->exportValue($values), array('testel' => 180));
        $values = array('testel' => array('number' => 1.5, 'timeunit' => 60));
        $this->assertEquals($el->exportValue($values), array('testel' => 90));
        $values = array('testel' => array('number' => 2, 'timeunit' => 3600));
        $this->assertEquals($el->exportValue($values), array('testel' => 7200));
        $values = array('testel' => array('number' => 1, 'timeunit' => 86400));
        $this->assertEquals($el->exportValue($values), array('testel' => 86400));
        $values = array('testel' => array('number' => 0, 'timeunit' => 3600));
        $this->assertEquals($el->exportValue($values), array('testel' => 0));

        $el = new MoodleQuickForm_duration('testel', null, array('optional' => true));
        $el->_createElements();
        $values = array('testel' => array('number' => 10, 'timeunit' => 1));
        $this->assertEquals($el->exportValue($values), array('testel' => 0));
        $values = array('testel' => array('number' => 20, 'timeunit' => 1, 'enabled' => 1));
        $this->assertEquals($el->exportValue($values), array('testel' => 20));
    }
}
