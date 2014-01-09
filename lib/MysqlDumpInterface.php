<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * MysqlDump interface
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

/**
 * MysqlDump interface
 *
 * @category  Databases
 * @package   MysqlDumpFactory
 * @author    Yani Iliev <yani@iliev.me>
 * @copyright 2014 Yani Iliev
 * @license   https://raw.github.com/yani-/mysqldump-factory/master/LICENSE The MIT License (MIT)
 * @version   GIT: 1.0.0
 * @link      https://github.com/yani-/mysqldump-factory/
 */
interface MysqlDumpInterface
{
    const MAXLINESIZE = 1000000;

    /**
     * Constructor of Mysqldump. Note that in the case of an SQLite database connection, the filename must be in the $db parameter.
     *
     * @param string $db        Database name
     * @param string $user      SQL account username
     * @param string $pass      SQL account password
     * @param string $host      SQL server to connect to
     * @return null
     */
    public function __construct($db = '', $user = '', $pass = '', $host = 'localhost');

    /**
     * jquery style extend, merges arrays (without errors if the passed
     * values are not arrays)
     *
     * @param array $args       default settings
     * @param array $extended   user settings
     *
     * @return array $extended  merged user settings with default settings
     */
    public function extend();


    /**
     * Set new settings
     *
     * @return void
     */
    public function set($settings);

    /**
     * Main call
     *
     * @param string $filename  Name of file to write sql dump to
     * @param array  $clauses   Query parameters
     * @return bool
     */
    public function start($filename = '', $clauses = array());

    /**
     * Get current file name
     *
     * @return string
     */
    public function getFileName();

    /**
     * Truncate database
     *
     * @return void
     */
    public function truncateDatabase();

    /**
     * Import database from file
     *
     * @return void
     */
    public function importFromFile($file);

    /**
     * Returns list of tables
     *
     * @return array
     */
    public function listTables();
}
