[![Build Status](https://secure.travis-ci.org/kriswallsmith/spork.png?branch=master)](http://travis-ci.org/kriswallsmith/spork)

Spork: PHP on a Fork
--------------------

```php
<?php

$manager = new Spork\ProcessManager();
$manager->fork(function () {
    // do something in another process!
    return 'Hello from ' . getmypid();
})->then(function (Spork\Fork $fork) {
    // do something in the parent process when it's done!
    echo "{$fork->getPid()} says '{$fork->getResult()}'\n";
});
```

### Example: Upload images to your CDN

Feed an iterator into the process manager and it will break the job into
multiple batches and spread them across many processes.

```php
<?php

$files = new RecursiveDirectoryIterator('/path/to/images');
$files = new RecursiveIteratorIterator($files);

$manager = new Spork\ProcessManager();
$manager->process($files, function(SplFileInfo $file) {
    // upload this file
});

$manager->wait();
```

### Example: Working with Doctrine DBAL

When working with database connections, there is a known issue regarding parent/child processes.
From http://php.net/manual/en/function.pcntl-fork.php#70721:

> the child process inherits the parent's database connection. 
> When the child exits, the connection is closed.
> If the parent is performing a query at this very moment, it is doing it on an already closed connection

This will mean that in our example, we will see a `SQLSTATE[HY000]: General error: 2006 MySQL server has gone away` 
exception being thrown in the parent process.

One work-around for this situation is to force-close the DB connection before forking, by using the PRE_FORK event.

```php
<?php

$params = array(
    'dbname'    => '...',
    'user'      => '...',
    'password'  => '...',
    'host'      => '...',
    'driver'    => 'pdo_mysql',
);

$forks = 4;
$dataArray = range(0, 15);

$callback = function ($value) use ($params) {
    // Child process acquires its own DB connection
    $conn = Doctrine\DBAL\DriverManager::getConnection($params);
    $conn->connect();

    $sql = 'SELECT NOW() AS now';
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $dbResult = $stmt->fetch();
    $conn->close();

    return ['pid' => getmypid(), 'value' => $value, 'result' => $dbResult];
};

// Get DB connection in parent
$parentConnection = Doctrine\DBAL\DriverManager::getConnection($params);
$parentConnection->connect();

$dispatcher = new Spork\EventDispatcher\EventDispatcher();
$dispatcher->addListener(Spork\EventDispatcher\Events::PRE_FORK, function () use ($parentConnection) {
    $parentConnection->close();
});

$manager = new Spork\ProcessManager($dispatcher, null, true);

/** @var Spork\Fork $fork */
$fork = $manager->process($dataArray, $callback, new Spork\Batch\Strategy\ChunkStrategy($forks));
$manager->wait();

$result = $fork->getResult();

// Safe to use now
$sql = 'SELECT NOW() AS now_parent';
$stmt = $parentConnection->prepare($sql);
$stmt->execute();
$dbResult = $stmt->fetch();
$parentConnection->close();
```
