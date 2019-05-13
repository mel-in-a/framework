# PostgreSQL setup

PostgreSQL is work in progress since and brings a number of challenges. I not happy with previous concept of datasource injecting itself into driver and then storing driver on datasource. Not tested the postgre since this change.

Issues
======
1. The getMetadata does not return the alias for table
2. User is a reserved word and force escaping of everything
3. Getting the primary key is a bit of mission.


## Thoughts

Two ideas:

1. Use oldschool mapping method datasource::mapResults
2. Instead of using Model.column do Model__column for postgres. This is complicating things maybe just to use getColumnMeta. Although maybe doing this way on both MySQL and Postgre dont have to worry about quoting.

```php
ConnectionManager::config('default', array(
  'host' => 'pg',
  'database' => 'origin',
  'username' => 'root',
  'password' => 'root',
  'engine' => 'pgsql'
));
```

Add the following to docker-compose.yml

```yml
 pg:
    image: postgres
    restart: always
    environment:
      POSTGRES_USER: root
      POSTGRES_PASSWORD: root
    ports:
        - "5432:5432"
```

To dockerfile setup to install the following

```
 postgresql-client
 php-pgsql
```