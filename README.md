Spork: PHP on a Fork
--------------------

```php
<?php

use Spork\Deferred\DeferredFactory;
use Spork\ProcessManager;

$manager = new ProcessManager(new DeferredFactory());
$manager->fork(function() {
    // do something in another process!
})->then(function($output, $status) {
    // do something in the parent process when it's done!
});
```
