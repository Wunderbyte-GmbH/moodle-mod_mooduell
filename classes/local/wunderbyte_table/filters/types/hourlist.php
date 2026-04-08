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
 * The Wunderbyte table class is an extension of the tablelib table_sql class.
 *
 * @package mod_mooduell
 * @copyright 2023 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell\local\wunderbyte_table\filters\types;

use core_date;
use mod_mooduell\local\wunderbyte_table\filters\base;
use mod_mooduell\local\wunderbyte_table\wunderbyte_table;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class hourlist extends base {
    /**
     * Set the column which should be filtered and possibly localize it.
     * @param string $columnidentifier
     * @param string $localizedstring
     * @param string $secondcolumnidentifier
     * @param string $secondcolumnlocalized
     * @return void
     */
    public function __construct(
        string $columnidentifier,
        string $localizedstring = '',
        string $secondcolumnidentifier = '',
        string $secondcolumnlocalized = ''
    ) {
        $this->options = self::get_possible_timed_options();

        $this->columnidentifier = $columnidentifier;
        $this->localizedstring = empty($localizedstring) ? $columnidentifier : $localizedstring;
        $this->secondcolumnidentifier = $secondcolumnidentifier;
        $this->secondcolumnlocalized = empty($secondcolumnlocalized) ? $secondcolumnidentifier : $secondcolumnlocalized;
    }

    /**
     * Add the filter to the array.
     * @return array
     */
    public static function get_possible_timed_options() {
        return [
            0  => get_string('from0to1', 'mod_mooduell'),
            1  => get_string('from1to2', 'mod_mooduell'),
            2  => get_string('from2to3', 'mod_mooduell'),
            3  => get_string('from3to4', 'mod_mooduell'),
            4  => get_string('from4to5', 'mod_mooduell'),
            5  => get_string('from5to6', 'mod_mooduell'),
            6  => get_string('from6to7', 'mod_mooduell'),
            7  => get_string('from7to8', 'mod_mooduell'),
            8  => get_string('from8to9', 'mod_mooduell'),
            9  => get_string('from9to10', 'mod_mooduell'),
            10 => get_string('from10to11', 'mod_mooduell'),
            11 => get_string('from11to12', 'mod_mooduell'),
            12 => get_string('from12to13', 'mod_mooduell'),
            13 => get_string('from13to14', 'mod_mooduell'),
            14 => get_string('from14to15', 'mod_mooduell'),
            15 => get_string('from15to16', 'mod_mooduell'),
            16 => get_string('from16to17', 'mod_mooduell'),
            17 => get_string('from17to18', 'mod_mooduell'),
            18 => get_string('from18to19', 'mod_mooduell'),
            19 => get_string('from19to20', 'mod_mooduell'),
            20 => get_string('from20to21', 'mod_mooduell'),
            21 => get_string('from21to22', 'mod_mooduell'),
            22 => get_string('from22to23', 'mod_mooduell'),
            23 => get_string('from23to24', 'mod_mooduell'),
        ];
    }

    /**
     * Add the filter to the array.
     * @param array $filter
     * @param bool $invisible
     * @return void
     * @throws \moodle_exception
     */
    public function add_filter(array &$filter, bool $invisible = false) {

        $options = $this->options;

        $options['localizedname'] = $this->localizedstring;
        $options['wbfilterclass'] = get_called_class();
        $options[get_class($this)] = true;
        $options[$this->columnidentifier . '_wb_checked'] = $invisible ? 0 : 1;

        // We always need to make sure that id column is present.
        if (!isset($filter['id'])) {
            $filter['id'] = [
                'localizedname' => get_string('id', 'mod_mooduell'),
                'id_wb_checked' => 1,
            ];
        }

        if (!isset($filter[$this->columnidentifier])) {
            $filter[$this->columnidentifier] = $options;
        } else {
            throw new \moodle_exception(
                'filteridentifierconflict',
                'mod_mooduell',
                '',
                $this->columnidentifier,
                'Every column can have only one filter applied'
            );
        }
    }

    /**
     * This function takes a key value pair of options.
     * Only if there are actual results in the table, these options will be displayed.
     * The keys are the results, the values are the localized strings.
     * For the standard filter, it's not necessary to provide these options...
     * They will be gathered automatically.
     *
     * @param array $options
     * @return void
     */
    public function add_options(array $options = []) {
        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
        }
    }

    /**
     * Get filter options for hours.
     * @param wunderbyte_table $table
     * @param string $key
     * @return array
     */
    public static function get_data_for_filter_options(wunderbyte_table $table, string $key) {

        $array = self::get_db_filter_column_hours($table, $key);
        $returnarray = [];

        foreach ($array as $hour => $value) {
            $value->$key = "$hour";
            $returnarray[$hour] = $value;
        }
        return $returnarray ?? [];
    }

    /**
     * The expected value.
     * @param \MoodleQuickForm $mform
     * @param array $data
     * @param string $filterspecificvalue
     */
    public static function render_mandatory_fields(&$mform, $data = [], $filterspecificvalue = '') {
        $mform->addElement('html', '<p id="no-pairs-message" class="alert alert-info">No further seetings needed</p>');
    }

    /**
     * The expected value.
     * @param object $data
     * @param string $filtercolumn
     * @return array
     */
    public static function get_filterspecific_values($data, $filtercolumn) {
        $filterenablelabel = $filtercolumn . '_wb_checked';
        $filterspecificvalues = [
            'localizedname' => $data->localizedname ?? '',
            $data->wbfilterclass => true,
            $filterenablelabel => $data->$filterenablelabel ?? '0',
            'wbfilterclass' => $data->wbfilterclass ?? '',
        ];
        $filterspecificvalues = array_merge($filterspecificvalues, self::get_possible_timed_options());
        return [$filterspecificvalues, ''];
    }

    /**
     * Makes sql requests.
     * @param wunderbyte_table $table
     * @param string $key
     * @return array
     */
    protected static function get_db_filter_column_hours(wunderbyte_table $table, string $key) {

        global $DB, $USER;

        $databasetype = $DB->get_dbfamily();
        $tz = core_date::get_user_timezone($USER); // We must apply user's timezone there.

        // The $key param is the name of the table in the column, so we can safely use it directly without fear of injection.
        switch ($databasetype) {
            case 'postgres':
                $sql = "SELECT hours, COUNT(hours)
                        FROM (
                            SELECT EXTRACT(
                                HOUR FROM (TIMESTAMP 'epoch' + $key * interval '1 second') AT TIME ZONE 'UTC' AT TIME ZONE '$tz'
                            ) AS hours
                            FROM {$table->sql->from}
                            WHERE {$table->sql->where} AND $key IS NOT NULL
                        ) as hourss1
                        GROUP BY hours ";
                break;
            case 'mysql':
                $sql = "SELECT hours, COUNT(*) as count
                        FROM (
                            SELECT EXTRACT(
                                HOUR FROM CONVERT_TZ(FROM_UNIXTIME($key), 'UTC', '$tz')
                            ) AS hours
                            FROM {$table->sql->from}
                            WHERE {$table->sql->where} AND $key IS NOT NULL
                        ) as hourss1
                        GROUP BY hours";
                break;
            default:
                $sql = '';
                break;
        }

        if (empty($sql)) {
            return [];
        }

        $records = $DB->get_records_sql($sql, $table->sql->params);

        return $records;
    }

    /**
     * Apply the filter of hourlist class.
     *
     * @param string $filter
     * @param string $columnname
     * @param mixed $categoryvalue
     * @param wunderbyte_table $table
     *
     * @return void
     *
     */
    public function apply_filter(
        string &$filter,
        string $columnname,
        $categoryvalue,
        wunderbyte_table &$table
    ): void {
        global $DB, $USER;

        $databasetype = $DB->get_dbfamily();
        $tz = core_date::get_user_timezone($USER); // We must apply user's timezone there.
        $filtercounter = 1;
        $filter .= " ( ";
        foreach ($categoryvalue as $key => $value) {
            $filter .= $filtercounter == 1 ? "" : " OR ";
            $paramsvaluekey = $table->set_params((string) ($value), false);
            // The $key param is the name of the table in the column, so we can safely use it directly without fear of injection.
            switch ($databasetype) {
                case 'postgres':
                    $filter .= " EXTRACT(
                     HOUR FROM (TIMESTAMP 'epoch' + $columnname * interval '1 second') AT TIME ZONE 'UTC' AT TIME ZONE '$tz'
                     ) = :$paramsvaluekey
                     AND $columnname IS NOT NULL";
                    break;
                default:
                    $filter .= " EXTRACT(
                     HOUR FROM CONVERT_TZ(FROM_UNIXTIME($columnname), 'UTC', '$tz')
                     ) = :$paramsvaluekey
                     AND $columnname IS NOT NULL";
            }
            $filtercounter++;
        }
        $filter .= " ) ";
    }

    /**
     * The expected value.
     * @param object $data
     * @param string $filtercolumn
     * @return array
     */
    public static function get_new_filter_values($data, $filtercolumn) {
        return [];
    }
}
