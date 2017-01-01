# MySQL Insert On Duplicate Key Update Eloquent Trait

[![Latest Stable Version](https://poser.pugx.org/yadakhov/insert-on-duplicate-key/version)](https://packagist.org/packages/yadakhov/insert-on-duplicate-key)
[![License](https://poser.pugx.org/yadakhov/insert-on-duplicate-key/license)](https://packagist.org/packages/yadakhov/insert-on-duplicate-key)
[![Build Status](https://travis-ci.org/yadakhov/insert-on-duplicate-key.svg)](https://travis-ci.org/yadakhov/insert-on-duplicate-key)

[Insert Duplicate Key Update](http://dev.mysql.com/doc/refman/5.7/en/insert-on-duplicate.html) is a quick way to do mass insert.

It's a trait meant to be used with Laravel's Eloquent ORM.

### Code Example

```php
use Illuminate\Database\Eloquent\Model;
use Yadakhov\InsertOnDuplicateKey;

/**
 * Class User.
 */
class User extends Model
{
    // The function is implemented as a trait.
    use InsertOnDuplicateKey;
}
```

#### Multi values insert.
```php
    $users = [
        ['id' => 1, 'email' => 'user1@email.com', 'name' => 'User One'],
        ['id' => 2, 'email' => 'user2@email.com', 'name' => 'User Two'],
        ['id' => 3, 'email' => 'user3@email.com', 'name' => 'User Three'],
    ];
```

#### INSERT ON DUPLICATE KEY UPDATE

```php
    User::insertOnDuplicateKey($users);
```
```sql
    -- produces this query
    INSERT INTO `users`(`id`,`email`,`name`) VALUES
    (1,'user1@email.com','User One'), (2,'user3@email.com','User Two'), (3,'user3email.com','User Three')
    ON DUPLICATE KEY UPDATE `id` = VALUES(`id`), `email` = VALUES(`email`), `name` = VALUES(`name`)
```

```php
    User::insertOnDuplicateKey($users, ['email']);
```
```sql
    -- produces this query
    INSERT INTO `users`(`id`,`email`,`name`) VALUES
    (1,'user1@email.com','User One'), (2,'user3@email.com','User Two'), (3,'user3email.com','User Three')
    ON DUPLICATE KEY UPDATE `email` = VALUES(`email`)
```

#### INSERT IGNORE
```php
    User::insertIgnore($users);
```
```sql
    -- produces this query
    INSERT IGNORE INTO `users`(`id`,`email`,`name`) VALUES
    (1,'user1@email.com','User One'), (2,'user3@email.com','User Two'), (3,'user3email.com','User Three');
```

#### REPLACE INTO
```php
    User::replace($users);
```
```sql
    -- produces this query
    REPLACE INTO `users`(`id`,`email`,`name`) VALUES
    (1,'user1@email.com','User One'), (2,'user3@email.com','User Two'), (3,'user3email.com','User Three');
```

### created_at and updated_at fields.

created_at and updated_at will *not* be updated automatically.  To update you can pass the fields in the insert array.

```php
['id' => 1, 'email' => 'user1@email.com', 'name' => 'User One', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
```

### Run unit tests

```
./vendor/bin/phpunit
```

### Will this work on Postgresql?

No.  On Duplicate Key Update is only available on MySQL.  Postgresql 9.4 has a similar feature called [UPSERT](https://wiki.postgresql.org/wiki/UPSERT).
Implementing UPSERT is left as an exercise for the reader.

### Isn't this the same as updateOrCreate()?

It is similar but not the same.  The updateOrCreate() will only work for one row at a time which doesn't allow bulk insert. InsertOnDuplicateKey will work on many rows.
