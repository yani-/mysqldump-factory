<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Provides unit tests for MysqlDumpFactory
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
 * @category  Tests
 * @package   MysqlDumpFactory
 * @author    Yani Iliev <yani@iliev.me>
 * @author    Bobby Angelov <bobby@servmask.com>
 * @copyright 2014 Yani Iliev, Bobby Angelov
 * @license   https://raw.github.com/yani-/mysqldump-factory/master/LICENSE The MIT License (MIT)
 * @version   GIT: 1.2.0
 * @link      https://github.com/yani-/mysqldump-factory/
 */

/**
 * Unit test class
 *
 * @category  Tests
 * @package   MysqlDumpFactory
 * @author    Yani Iliev <yani@iliev.me>
 * @author    Bobby Angelov <bobby@servmask.com>
 * @copyright 2014 Yani Iliev, Bobby Angelov
 * @license   https://raw.github.com/yani-/mysqldump-factory/master/LICENSE The MIT License (MIT)
 * @version   GIT: 1.2.0
 * @link      https://github.com/yani-/mysqldump-factory/
 */
class MysqlDumpFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * [testMakeMysqlDumpCreatePDO description]
     * @return [type] [description]
     */
    public function testMakeMysqlDumpCreatePDO()
    {
        $this->assertTrue(
            MysqlDumpFactory::makeMysqlDump('', '', '', '', true) instanceof MysqlDumpPDO
        );
    }

    /**
     * [testMakeMysqlDumpCreateSQL description]
     * @return [type] [description]
     */
    public function testMakeMysqlDumpCreateSQL()
    {
        $this->assertTrue(
            MysqlDumpFactory::makeMysqlDump() instanceof MysqlDumpSQL
        );
    }

    /**
     * [testCreateTablePrefixPDO description]
     * @return [type] [description]
     */
    public function testCreateTablePrefixPDO()
    {
    }

    /**
     * [testCreateTablePrefixSQL description]
     * @return [type] [description]
     */
    public function testCreateTablePrefixSQL()
    {
        $adapter = MysqlDumpFactory::makeMysqlDump();
        $adapter->setOldTablePrefix('blog_');
        $adapter->setNewTablePrefix('SERVMASK_PREFIX_');

        $sql = 'CREATE TABLE `blog_test` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `post_id` bigint(20) unsigned NOT NULL,
                    `ddd` varchar(20) NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `post_id` (`post_id`),
                    CONSTRAINT `wp_test_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `wp_posts` (`ID`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1';

        $fp = fopen("php://memory", 'r+');
        fputs($fp, $sql);
        rewind($fp);

        $class = new ReflectionClass($adapter);
        $method = $class->getMethod('replaceCreateTablePrefix');
        $method->setAccessible(true);

        $result = null;
        while ($line = fgets($fp)) {
            $result .= $method->invokeArgs($adapter, array($line));
        }

        $sql = 'CREATE TABLE `SERVMASK_PREFIX_test` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `post_id` bigint(20) unsigned NOT NULL,
                    `ddd` varchar(20) NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `post_id` (`post_id`),
                    CONSTRAINT `wp_test_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `wp_posts` (`ID`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1';

        $this->assertEquals($sql, $result);
    }
}

