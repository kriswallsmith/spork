<?php

namespace Spork\Test\Batch;

class BatchJobTest extends \PHPUnit_Framework_TestCase
{
    public function testErrorPropagation()
    {
        $manager = new \Spork\ProcessManager();
        $batch = $manager->createBatchJob(range(1, 5));

        $expectedExitStatus = 20;

        $failingClosure = function ($data) use ($expectedExitStatus) {
            // Simple condition to simulate only one point of failure
            if (3 === $data) {
                exit($expectedExitStatus);
            }

            exit(0);
        };

        $promise = $batch->execute($failingClosure);

        $promise->wait();

        $success = null;

        $promise->done(function () use (& $success) {
            $success = true;
        });

        $promise->fail(function () use (& $success) {
            $success = false;
        });

        $this->assertFalse($success, 'Promise should fail');

        $this->assertNull($promise->getError(), 'Child process did not throw and exception so error should be null');

        $actualExitStatus = $promise->getExitStatus();

        $this->assertEquals($expectedExitStatus, $actualExitStatus, 'Parent process exit status should match the child one');
    }

    public function testErrorMessagePropagation()
    {
        $manager = new \Spork\ProcessManager();
        $batch = $manager->createBatchJob(range(1, 5));

        $expectedErrorMessage = 'This is a test message';

        $failingClosure = function ($data) use ($expectedErrorMessage) {
            // Simple condition to simulate only one point of failure
            if (3 === $data) {
                throw new \InvalidArgumentException($expectedErrorMessage);
            }

            exit(0);
        };

        $promise = $batch->execute($failingClosure);

        $promise->wait();

        $success = null;

        $promise->done(function () use (& $success) {
            $success = true;
        });

        $promise->fail(function () use (& $success) {
            $success = false;
        });

        $this->assertFalse($success, 'Promise should fail');

        $this->assertNotNull($promise->getError(), 'Since child process thrown an exception, parent process should have an error');

        $this->assertEquals($expectedErrorMessage, $promise->getError()->getMessage(), 'Parent process error message should match the child one');
    }

    public function testExitStatusIsZeroOnSuccess()
    {
        $manager = new \Spork\ProcessManager();
        $batch = $manager->createBatchJob(range(1, 5));

        $simpleClosure = function () {
            exit(0);
        };

        $promise = $batch->execute($simpleClosure);

        $promise->wait();

        $success = null;

        $promise->done(function () use (& $success) {
            $success = true;
        });

        $promise->fail(function () use (& $success) {
            $success = false;
        });

        $this->assertTrue($success, 'Promise should be successful');

        $actualExitStatus = $promise->getExitStatus();

        $this->assertEquals(0, $actualExitStatus, 'When the promise is successful the exit status should be zero');
    }
}
