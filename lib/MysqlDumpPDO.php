<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * MysqlDumpPDO class
 *
 * PHP version 5
 *
 * LICENSE: Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @category  Databases
 * @package   MysqlDumpFactory
 * @author    Yani Iliev <yani@iliev.me>
 * @copyright 2014 Yani Iliev
 * @license   https://raw.github.com/yani-/mysqldump-factory/master/LICENSE The MIT License (MIT)
 * @version   GIT: 1.0.0
 * @link      https://github.com/yani-/mysqldump-factory/
 */

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MysqlDumpInterface.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TypeAdapter.php';

/**
 * MysqlDumpPDO class
 *
 * @category  Databases
 * @package   MysqlDumpFactory
 * @author    Yani Iliev <yani@iliev.me>
 * @copyright 2014 Yani Iliev
 * @license   https://raw.github.com/yani-/mysqldump-factory/master/LICENSE The MIT License (MIT)
 * @version   GIT: 1.0.0
 * @link      https://github.com/yani-/mysqldump-factory/
 */
class MysqlDumpPDO implements MysqlDumpInterface
{
    // This can be set both on constructor or manually
    public $host;
    public $user;
    public $pass;
    public $db;
    public $fileName = 'dump.sql';

    // Internal stuff
    private $settings = array();
    private $tables = array();
    private $views = array();
    private $dbHandler;
    private $defaultSettings = array(
        'include-tables'     => array(),
        'exclude-tables'     => array(),
        'compress'           => 0,
        'no-data'            => false,
        'add-drop-table'     => false,
        'single-transaction' => true,
        'lock-tables'        => false,
        'add-locks'          => true,
        'extended-insert'    => true
    );
    private $compressManager;

    /**
     * Constructor of Mysqldump. Note that in the case of an SQLite database connection, the filename must be in the $db parameter.
     *
     * @param string $db        Database name
     * @param string $user      SQL account username
     * @param string $pass      SQL account password
     * @param string $host      SQL server to connect to
     * @return null
     */
    public function __construct($db = '', $user = '', $pass = '', $host = 'localhost', $type="mysql", $settings = null, $pdo_options = array(PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION))
    {
        $this->db = $db;
        $this->user = $user;
        $this->pass = $pass;
        $this->host = $host;
        $this->type = strtolower($type);
        $this->pdo_options = $pdo_options;
        $this->set($settings);
    }

    /**
     * jquery style extend, merges arrays (without errors if the passed
     * values are not arrays)
     *
     * @param array $args       default settings
     * @param array $extended   user settings
     *
     * @return array $extended  merged user settings with default settings
     */
    public function extend()
    {
        $args = func_get_args();
        $extended = array();
        if (is_array($args) && count($args) > 0) {
            foreach ($args as $array) {
                if (is_array($array)) {
                    $extended = array_merge($extended, $array);
                }
            }
        }

        return $extended;
    }


    /**
     * Set new settings
     *
     * @return void
     */
    public function set($settings)
    {
        $this->settings = $this->extend($this->defaultSettings, $settings);
    }

    /**
     * Connect with PDO
     *
     * @return bool
     */
    private function connect()
    {
        // Connecting with PDO
        try {
            $this->dbHandler = new PDO(
                $this->type . ":host=" . $this->host.";dbname=" . $this->db,
                $this->user,
                $this->pass,
                $this->pdo_options
            );
            // Fix for always-unicode output
            $this->dbHandler->exec("SET NAMES utf8");
        } catch (PDOException $e) {
            throw new \Exception(
                'Connection to ' . $this->type . ' failed with message: ' .
                $e->getMessage(), 3
            );
        }

        $this->dbHandler->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL);
        $this->adapter = new TypeAdapter($this->type);
    }

    /**
     * Main call
     *
     * @param string $filename  Name of file to write sql dump to
     * @param array  $clauses   Query parameters
     * @return bool
     */
    public function start($filename = '', $clauses = array())
    {
        // Output file can be redefined here
        if ( !empty($filename) ) {
            $this->fileName = $filename;
        }

        // We must set a name to continue
        if ( empty($this->fileName) ) {
            throw new \Exception("Output file name is not set", 1);
        }

        // Connect to database
        $this->connect();

        // Create a new compressManager to manage compressed output
        $this->compressManager = CompressManagerFactory::create($this->settings['compress']);

        if (! $this->compressManager->open($this->fileName)) {
            throw new \Exception("Output file is not writable", 2);
        }

        // Formating dump file
        $this->compressManager->write($this->getHeader());

        // Listing all tables from database
        $this->tables = array();
        foreach ($this->dbHandler->query($this->adapter->show_tables($this->db)) as $row) {
            if (empty($this->settings['include-tables']) || (! empty($this->settings['include-tables']) && in_array(current($row), $this->settings['include-tables'], true))) {
                array_push($this->tables, current($row));
            }
        }

        // Exporting tables one by one
        foreach ($this->tables as $table) {
            if (in_array($table, $this->settings['exclude-tables'], true)) {
                continue;
            }

            $is_table = $this->getTableStructure($table);
            if (true === $is_table) {
                if (false === $this->settings['no-data']) {
                    $this->listValues($table, $clauses);
                } else if (isset($clauses[$table])) {
                    $this->listValues($table, $clauses);
                }
            }
        }

        // Exporting views one by one
        foreach ($this->views as $view) {
            $this->compressManager->write($view);
        }

        //$this->compressManager->close();
    }

    /**
     * Get current file name
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Truncate database
     *
     * @return void
     */
    public function truncateDatabase()
    {
        // Connect to database
        $this->connect();

        foreach ($this->dbHandler->query($this->adapter->show_tables($this->db)) as $row) {
            // Drop table
            $this->dbHandler->query($this->adapter->drop_table($row['tbl_name']));
        }
    }

    /**
     * Import database from file
     *
     * @return void
     */
    public function importFromFile($file)
    {
        if (!is_resource($file)) {
            $file = fopen($file, 'r');
        }

        // Read database file
        $sql = stream_get_contents($file);

        return $this->dbHandler->query($sql);
    }

    /**
     * Returns list of tables
     *
     * @return array
     */
    public function listTables()
    {
        // Connect to database
        $this->connect();

        $result = array();
        foreach ($this->dbHandler->query($this->adapter->show_tables($this->db)) as $row) {
            $result[] = $row['tbl_name'];
        }

        return $result;
    }

    /**
     * Returns header for dump file
     *
     * @return null
     */
    private function getHeader()
    {
        // Some info about software, source and time
        $header = "-- All In One WP Migration SQL Dump\n" .
                "-- http://servmask.com/\n" .
                "--\n" .
                "-- Host: {$this->host}\n" .
                "-- Generation Time: " . date('r') . "\n\n" .
                "--\n" .
                "-- Database: `{$this->db}`\n" .
                "--\n\n";

        return $header;
    }

    /**
     * Table structure extractor
     *
     * @param string $tablename  Name of table to export
     * @return null
     */
    private function getTableStructure($tablename)
    {
        $stmt = $this->adapter->show_create_table($tablename);
        foreach ($this->dbHandler->query($stmt) as $r) {
            if (isset($r['Create Table'])) {
                $this->compressManager->write("-- " .
                    "--------------------------------------------------------" .
                    "\n\n" .
                    "--\n" .
                    "-- Table structure for table `$tablename`\n--\n\n");

                if ($this->settings['add-drop-table']) {
                    $this->compressManager->write("DROP TABLE IF EXISTS `$tablename`;\n\n");
                }

                $this->compressManager->write($r['Create Table'] . ";\n\n");

                return true;
            }
            if ( isset($r['Create View']) ) {
                $view  = "-- " .
                        "--------------------------------------------------------" .
                        "\n\n";
                $view .= "--\n-- Table structure for view `$tablename`\n--\n\n";
                $view .= $r['Create View'] . ";\n\n";
                $this->views[] = $view;

                return false;
            }
        }
    }

    /**
     * Table rows extractor
     *
     * @param string $tablename  Name of table to export
     * @param array  $clauses    Query parameters
     * @return null
     */
    private function listValues($tablename, $clauses = array())
    {
        $this->compressManager->write(
            "--\n" .
            "-- Dumping data for table `$tablename`\n" .
            "--\n\n"
        );

        if ($this->settings['single-transaction']) {
            $this->dbHandler->exec($this->adapter->start_transaction());
        }

        if ($this->settings['lock-tables']) {
            $lockstmt = $this->adapter->lock_table($tablename);
            if(strlen($lockstmt)){
                $this->dbHandler->exec($lockstmt);
            }
        }

        if ( $this->settings['add-locks'] ) {
            $this->compressManager->write($this->adapter->start_add_lock_table($tablename));
        }

        $onlyOnce = true; $lineSize = 0;
        $stmt = "SELECT * FROM `$tablename` ";

        // Add query parameters
        if (isset($clauses[$tablename]) && ($clause_query = $clauses[$tablename])) {
            $stmt .= $clause_query;
        }

        foreach ($this->dbHandler->query($stmt, PDO::FETCH_NUM) as $r) {
            $vals = array();
            foreach ($r as $val) {
                $vals[] = is_null($val) ? "NULL" :
                $this->dbHandler->quote($val);
            }
            if ($onlyOnce || !$this->settings['extended-insert'] ) {
                $lineSize += $this->compressManager->write("INSERT INTO `$tablename` VALUES (" . implode(",", $vals) . ")");
                $onlyOnce = false;
            } else {
                $lineSize += $this->compressManager->write(",(" . implode(",", $vals) . ")");
            }
            if ( ($lineSize > Mysqldump::MAXLINESIZE) ||
                    !$this->settings['extended-insert'] ) {
                $onlyOnce = true;
                $lineSize = $this->compressManager->write(";\n");
            }
        }

        if (! $onlyOnce) {
            $this->compressManager->write(";\n");
        }

        if ($this->settings['add-locks']) {
            $this->compressManager->write($this->adapter->end_add_lock_table($tablename));
        }

        if ($this->settings['single-transaction']) {
            $this->dbHandler->exec($this->adapter->commit_transaction());
        }

        if ($this->settings['lock-tables']) {
            $lockstmt = $this->adapter->unlock_table($tablename);
            if(strlen($lockstmt)){
                $this->dbHandler->exec($lockstmt);
            }
        }
    }
}
