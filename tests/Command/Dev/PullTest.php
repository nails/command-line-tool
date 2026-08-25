<?php

namespace Nails\Cli\Tests\Command\Dev;

use Nails\Cli\Command\Dev\Pull;
use PHPUnit\Framework\TestCase;

final class PullTest extends TestCase
{
    public function testCommandConfiguration(): void
    {
        $command = new Pull();

        $this->assertSame('dev:pull', $command->getName());
        $this->assertTrue($command->getDefinition()->hasOption('branch'));

        $option = $command->getDefinition()->getOption('branch');
        $this->assertSame('b', $option->getShortcut());
        $this->assertTrue($option->isValueOptional());
    }
}
