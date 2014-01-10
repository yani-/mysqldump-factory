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
 * @author    Bobby Angelov <bobby@servmask.com>
 * @copyright 2014 Yani Iliev, Bobby Angelov
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
 * @author    Bobby Angelov <bobby@servmask.com>
 * @copyright 2014 Yani Iliev, Bobby Angelov
 * @license   https://raw.github.com/yani-/mysqldump-factory/master/LICENSE The MIT License (MIT)
 * @version   GIT: 1.0.0
 * @link      https://github.com/yani-/mysqldump-factory/
 */
interface MysqlDumpInterface
{
    const MAXLINESIZE = 1000000;

    /**
     * Define MySQL credentials for the current connection
     *
     * @param  string $hostname MySQL Hostname
     * @param  string $username MySQL Username
     * @param  string $password MySQL Password
     * @param  string $database MySQL Database
     * @return void
     */
    public function __construct($hostname = 'localhost', $username = '', $password = '', $database = '');

    /**
     * Set new settings
     *
     * @param  string Name of the parameter
     * @param  mixed  Value of the parameter
     * @return void
     */
    public function set($key, $value);

    /**
     * Dump database into a file
     *
     * @param  array $clauses Additional query parameters
     * @return void
     */
    public function dump($clauses = array());

    /**
     * Set output file name
     *
     * @return string
     */
    public function setFileName($fileName);

    /**
     * Get output file name
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
     * @param  string $fileName Name of file
     * @return mixed
     */
    public function import($fileName);

    /**
     * Get list of tables
     *
     * @return array
     */
    public function listTables();
}
