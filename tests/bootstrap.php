<?php

require __DIR__.'/../vendor/autoload.php';

use Illuminate\Database\Eloquent\Model;
use Yadakhov\InsertOnDuplicateKey;

class BootstrapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}

/**
 * Class UserTest.
 *
 * A user class for testing purpose.
 */
class UserTest extends Model
{
    use InsertOnDuplicateKey;

    protected $table = 'test_user_table';

    protected $primaryKey = 'uuid';

    /**
     * Override this method for unit test because we don't have a table connection.
     *
     * @return string
     */
    public static function getTablePrefix()
    {
        return 'prefix_';
    }
}
