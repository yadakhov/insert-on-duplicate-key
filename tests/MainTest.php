<?php

class MainTest extends BootstrapTest
{
    /**
     * @var UserTest
     */
    private $user;

    public function setUp()
    {
        parent::setUp();

        $this->user = new UserTest();
    }

    public function getDataForInsert()
    {
        return [
            ['id' => 1, 'email' => 'user1@email.com', 'name' => 'User One'],
            ['id' => 2, 'email' => 'user2@email.com', 'name' => 'User Two'],
            ['id' => 3, 'email' => 'user3@email.com', 'name' => 'User Three'],
        ];
    }

    public function testGetTableName()
    {
        $this->assertEquals('test_user_table', UserTest::getTableName());
    }

    public function testGetPrimaryKey()
    {
        $this->assertEquals('uuid', UserTest::getPrimaryKey());
    }

    // test private functions

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetColumnListEmptyDataException()
    {
        $data = [];

        $this->invokeMethod($this->user, 'getColumnList', [$data]);
    }

    public function testGetColumnList()
    {
        $data = $this->getDataForInsert();

        $expected = '`id`,`email`,`name`';

        $result = $this->invokeMethod($this->user, 'getColumnList', [$data[0]]);

        $this->assertEquals($expected, $result);
    }

    public function testBuildValuesList()
    {
        $data = $this->getDataForInsert();

        $expected = '`id` = VALUES(`id`), `email` = VALUES(`email`), `name` = VALUES(`name`)';

        $result = $this->invokeMethod($this->user, 'buildValuesList', [$data[0]]);

        $this->assertEquals($expected, $result);
    }

    public function testInLineArraySimple()
    {
        $data = [
            ['id' => 1, 'email' => 'user1@email.com', 'name' => 'User One']
        ];

        $expected = [1, 'user1@email.com', 'User One'];

        $result = $this->invokeMethod($this->user, 'inLineArray', [$data]);

        $this->assertEquals($expected, $result);
    }

    public function testBuildQuestionMarks()
    {
        $data = $this->getDataForInsert();

        $expected = '(?,?,?), (?,?,?), (?,?,?)';

        $result = $this->invokeMethod($this->user, 'buildQuestionMarks', [$data]);

        $this->assertEquals($expected, $result);
    }

    public function testInLineArrayThreeRows()
    {
        $data = $this->getDataForInsert();

        $expected = [
            1, 'user1@email.com', 'User One',
            2, 'user2@email.com', 'User Two',
            3, 'user3@email.com', 'User Three',
        ];

        $result = $this->invokeMethod($this->user, 'inLineArray', [$data]);

        $this->assertEquals($expected, $result);
    }

    public function testBuildSqlSimple()
    {
        $data = [
            ['id' => 1, 'email' => 'user1@email.com', 'name' => 'User One']
        ];

        $expected = 'INSERT INTO `test_user_table`(`id`,`email`,`name`) VALUES
(?,?,?)
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`), `email` = VALUES(`email`), `name` = VALUES(`name`)';

        $result = $this->invokeMethod($this->user, 'buildSql', [$data]);

        $this->assertEquals($expected, $result);
    }

    public function testBuildSqlMultiple()
    {
        $data = $this->getDataForInsert();

        $expected = 'INSERT INTO `test_user_table`(`id`,`email`,`name`) VALUES
(?,?,?), (?,?,?), (?,?,?)
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`), `email` = VALUES(`email`), `name` = VALUES(`name`)';

        $result = $this->invokeMethod($this->user, 'buildSql', [$data]);

        $this->assertEquals($expected, $result);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInsertWithBadId()
    {
        $data =  ['incorrect_id_field' => 1, 'email' => 'user1@email.com', 'name' => 'User One'];

        $this->user->insertOnDuplicateKey($data);
    }
}
