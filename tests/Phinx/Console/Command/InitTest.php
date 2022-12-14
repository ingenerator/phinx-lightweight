<?php

namespace Test\Phinx\Console\Command;

use Phinx\Console\Command\Init;
use Phinx\Console\PhinxApplication;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class InitTest extends TestCase
{
    protected function setUp(): void
    {
        $file = sys_get_temp_dir() . '/phinx.yml';
        if (is_file($file)) {
            unlink($file);
        }
    }

    public function testConfigIsWritten()
    {
        $application = new PhinxApplication('testing');
        $application->add(new Init());

        $command = $application->find('init');

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'path' => sys_get_temp_dir()
        ], [
            'decorated' => false
        ]);

        $this->assertMatchesRegularExpression(
            '/created (.*)phinx.yml(.*)/',
            $commandTester->getDisplay()
        );

        $this->assertFileExists(
            sys_get_temp_dir() . '/phinx.yml',
            'Phinx configuration not existent'
        );
    }

    public function testThrowsExceptionWhenConfigFilePresent()
    {
        touch(sys_get_temp_dir() . '/phinx.yml');
        $application = new PhinxApplication('testing');
        $application->add(new Init());

        $command = $application->find('init');

        $commandTester = new CommandTester($command);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/The file "(.*)" already exists/');
        $commandTester->execute([
            'command' => $command->getName(),
            'path' => sys_get_temp_dir()
        ], [
            'decorated' => false
        ]);
    }
}
