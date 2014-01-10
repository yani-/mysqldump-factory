<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * MysqlDumpSQL class
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
 * @author    Bobby Angelov <bobby@servmask.com>
 * @copyright 2014 Yani Iliev, Bobby Angelov
 * @license   https://raw.github.com/yani-/mysqldump-factory/master/LICENSE The MIT License (MIT)
 * @version   GIT: 1.0.0
 * @link      https://github.com/yani-/mysqldump-factory/
 */

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MysqlDumpInterface.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MysqlQueryAdapter.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MysqlFileAdapter.php';

/**
 * MysqlDumpSQL class
 *
 * @category  Databases
 * @package   MysqlDumpFactory
 * @author    Yani Iliev <yani@iliev.me>
 * @author    Bobby Angelov <bobby@servmask.com>
 * @copyright 2014 Yani Iliev, Bobby Angelov
 * @license   https://raw.github.com/yani-/mysqldump-factory/master/LICENSE The MIT License (MIT)
 * @version   GIT: 1.0.0
 * @link      https://github.com/yani-/mysqldump-factory/
 */
class MysqlDumpSQL implements MysqlDumpInterface
{
    protected $hostname         = null;

    protected $username         = null;

    protected $password         = null;

    protected $database         = null;

    protected $fileName         = 'dump.sql';

    protected $tables           = array();

    protected $views            = array();

    protected $fileAdapter      = null;

    protected $queryAdapter     = null;

    protected $connection       = null;

    protected $settings  = array(
        'include-tables'     => array(),
        'exclude-tables'     => array(),
        'no-data'            => false,
        'add-drop-table'     => false,
        'extended-insert'    => true
    );

    /**
     * Define MySQL credentials for the current connection
     *
     * @param  string $hostname MySQL Hostname
     * @param  string $username MySQL Username
     * @param  string $password MySQL Password
     * @param  string $database MySQL Database
     * @return void
     */
    public function __construct($hostname = 'localhost', $username = '', $password = '', $database = '')
    {
        // Set MySQL credentials
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;

        // Set Query Adapter
        $this->queryAdapter = new MysqlQueryAdapter('mysql');
    }

    /**
     * Create MySQL connection (lazy loading)
     *
     * @return mixed
     */
    public function getConnection()
    {
        if ($this->connection === null) {
            // Make connection
            $this->connection = mysql_pconnect($this->hostname, $this->username, $this->password);

            // Select database and set default encoding
            if ($this->connection) {
                if (mysql_select_db($this->database, $this->connection)) {
                    $query = $this->queryAdapter->set_names( 'utf8' );
                    mysql_query($query, $this->connection);
                } else {
                    throw new Exception('Could not select MySQL database: ' . mysql_error($this->connection));
                }
            } else {
                throw new Exception('Unable to connect to MySQL database server: ' . mysql_error($this->connection));
            }
        }

        return $this->connection;
    }

    /**
     * Set new settings
     *
     * @param  string Name of the parameter
     * @param  mixed  Value of the parameter
     * @return void
     */
    public function set($key, $value)
    {
        $this->settings[$key] = $value;

        return $this;
    }

    /**
     * Dump database into a file
     *
     * @param  array $clauses Additional query parameters
     * @return void
     */
    public function dump($clauses = array())
    {
        // Set File Adapter
        $this->fileAdapter = new MysqlFileAdapter();

        // Set output file
        $this->fileAdapter->open($this->getFileName());

        // Write Headers Formating dump file
        $this->fileAdapter->write($this->getHeader());

        // Listing all tables from database
        $this->tables = array();
        foreach ($this->listTables() as $table) {
            if (empty($this->settings['include-tables']) || in_array($table, $this->settings['include-tables'])) {
                $this->tables[] = $table;
            }
        }

        // Export Tables
        foreach ($this->tables as $table) {
            if (in_array($table, $this->settings['exclude-tables'])) {
                continue;
            }

            $isTable = $this->getTableStructure($table);
            if (true === $isTable) {
                if (false === $this->settings['no-data']) {
                    $this->listValues($table, $clauses);
                } else if (isset($clauses[$table])) {
                    $this->listValues($table, $clauses);
                }
            }
        }

        // Export Views
        foreach ($this->views as $view) {
            $this->fileAdapter->write($view);
        }
    }

    /**
     * Set output file name
     *
     * @return string
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get output file name
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
        $query = $this->queryAdapter->show_tables($this->database);
        $result = mysql_query($query, $this->getConnection());
        while ($row = mysql_fetch_assoc($result)) {
            // Drop table
            $delete = $this->queryAdapter->drop_table($row['tbl_name']);
            mysql_query($delete, $this->getConnection());
        }
    }

    /**
     * Import database from file
     *
     * @param  string $fileName Name of file
     * @return mixed
     */
    public function import($fileName)
    {
        // Read database file
        $sql = file_get_contents($fileName);

        // Run SQL queries
        return mysql_query($sql, $this->getConnection());
    }

    /**
     * Get list of tables
     *
     * @return array
     */
    public function listTables()
    {
        $tables = array();

        $query = $this->queryAdapter->show_tables($this->database);
        $result = mysql_query($query, $this->getConnection());
        while ($row = mysql_fetch_assoc($result)) {
            $tables[] = $row['tbl_name'];
        }

        return $tables;
    }

    /**
     * Returns header for dump file
     *
     * @return string
     */
    protected function getHeader()
    {
        // Some info about software, source and time
        $header = "-- All In One WP Migration SQL Dump\n" .
                "-- http://servmask.com/\n" .
                "--\n" .
                "-- Host: {$this->hostname}\n" .
                "-- Generation Time: " . date('r') . "\n\n" .
                "--\n" .
                "-- Database: `{$this->database}`\n" .
                "--\n\n";

        return $header;
    }

    /**
     * Table structure extractor
     *
     * @param  string $tableName Name of table to export
     * @return bool
     */
    protected function getTableStructure($tableName)
    {
        $query = $this->queryAdapter->show_create_table($tableName);
        $result = mysql_query($query, $this->getConnection());
        while ($row = mysql_fetch_assoc($result)) {
            if (isset($row['Create Table'])) {
                $this->fileAdapter->write("-- " .
                    "--------------------------------------------------------" .
                    "\n\n" .
                    "--\n" .
                    "-- Table structure for table `$tableName`\n--\n\n");

                if ($this->settings['add-drop-table']) {
                    $this->fileAdapter->write("DROP TABLE IF EXISTS `$tableName`;\n\n");
                }

                $this->fileAdapter->write($row['Create Table'] . ";\n\n");

                return true;
            }
            if (isset($row['Create View'])) {
                $view  = "-- " .
                        "--------------------------------------------------------" .
                        "\n\n";
                $view .= "--\n-- Table structure for view `$tableName`\n--\n\n";
                $view .= $row['Create View'] . ";\n\n";
                $this->views[] = $view;

                return false;
            }
        }
    }

    /**
     * Table rows extractor
     *
     * @param  string $tableName Name of table to export
     * @param  array  $clauses   Query parameters
     * @return void
     */
    private function listValues($tableName, $clauses = array())
    {
        $this->fileAdapter->write(
            "--\n" .
            "-- Dumping data for table `$tableName`\n" .
            "--\n\n"
        );

        $insertFirst = true;
        $lineSize = 0;
        $query = "SELECT * FROM `$tableName` ";

        // Add query parameters
        if (isset($clauses[$tableName]) && ($clause_query = $clauses[$tableName])) {
            $query .= $clause_query;
        }

        $result = mysql_query($query, $this->getConnection());
        while ($row = mysql_fetch_row($result)) {
            $items = array();
            foreach ($row as $value) {
                $items[] = is_null($value) ? 'NULL' : "'" . mysql_real_escape_string($value) . "'";
            }

            if ($insertFirst || !$this->settings['extended-insert'] ) {
                $lineSize += $this->fileAdapter->write("INSERT INTO `$tableName` VALUES (" . implode(',', $items) . ")");
                $insertFirst = false;
            } else {
                $lineSize += $this->fileAdapter->write(",(" . implode(",", $items) . ")");
            }

            if (($lineSize > MysqlDumpInterface::MAXLINESIZE) || !$this->settings['extended-insert'] ) {
                $insertFirst = true;
                $lineSize = $this->fileAdapter->write(";\n");
            }
        }

        if (!$insertFirst) {
            $this->fileAdapter->write(";\n");
        }
    }
}
