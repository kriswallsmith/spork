[![Build Status](https://secure.travis-ci.org/kriswallsmith/spork.png?branch=master)](http://travis-ci.org/kriswallsmith/spork)

Spork: PHP on a Fork
--------------------

```php
<?php

$manager = new Spork\ProcessManager();
$manager->fork(function() {
    // do something in another process!
    return 'Hello from '.getmypid();
})->then(function(Spork\Fork $fork) {
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

$manager->process($files, function(SplFileInfo $file) {
    // upload this file
});
```
