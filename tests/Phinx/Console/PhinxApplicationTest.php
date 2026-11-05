<?php

namespace Test\Phinx\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use Phinx\Console\Command\AbstractCommand;
use Phinx\Console\PhinxApplication;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

class PhinxApplicationTest extends TestCase
{
    /**
     *
     * @param AbstractCommand $command
     * @param $result
     */
    #[DataProvider('provider')]
    public function testRun($command, $result)
    {
        $app = new PhinxApplication('testing');
        $app->setAutoExit(false); // Set autoExit to false when testing
        $app->setCatchExceptions(false);

        $appTester = new ApplicationTester($app);
        $appTester->run(['command' => $command]);
        $stream = $appTester->getOutput()->getStream();
        rewind($stream);

        $this->assertMatchesRegularExpression($result, stream_get_contents($stream));
    }

    public static function provider()
    {
        return [
            ['help', '/help \[options\] \[--\] \[<command_name>\]/']
        ];
    }
}
