### MySQL Insert On Duplicate Key Update Eloquent Trait

[Insert Duplicate Key Update](http://dev.mysql.com/doc/refman/5.7/en/insert-on-duplicate.html) is a quick way to do mass insert.

### Code Example

```php
use Illuminate\Database\Eloquent\Model;
use Yadakhov\InsertOnDuplicateKey;

/**
 * Class User.
 */
class UserTest extends Model
{
    use InsertOnDuplicateKey;
}
```

#### Multi values insert.
```
    $users = [
        ['id' => 1, 'email' => 'user1@email.com', 'name' => 'User One'],
        ['id' => 2, 'email' => 'user2@email.com', 'name' => 'User Two'],
        ['id' => 3, 'email' => 'user3@email.com', 'name' => 'User Three'],
    ];

    User::insertOnDuplicateKey($users);
```

#### This is equivalent to running the following SQL statment:

```sql
    INSERT INTO `test_user_table`(`id`,`email`,`name`) VALUES
    (1,'user1@email.com','User One'), (2,'user3@email.com','User Two'), (3,'user3email.com','User Three')
    ON DUPLICATE KEY UPDATE `id` = VALUES(`id`), `email` = VALUES(`email`), `name` = VALUES(`name`)
```

### Will this work on Postgresql?

No.  On Duplicate Key Update is only available on MySQL.  Postgresql 9.4 has a similar feature called [UPSERT](https://wiki.postgresql.org/wiki/UPSERT).

### Isn't this the same as updateOrCreate()?

It's similar but not the same.  The updateOrCreate will only work on one row at a time. InsertOnDuplicateKey will on many rows.
