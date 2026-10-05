<?php

namespace Nails\Cli\Tests\Command\Dev;

use Nails\Cli\Command\Dev\Pull;
use Nails\Cli\Entities\Repository;
use Nails\Cli\Exceptions\Directory\DoesNotExistException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

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

        $this->assertTrue($command->getDefinition()->hasOption('dir'));
        $dirOption = $command->getDefinition()->getOption('dir');
        $this->assertSame('d', $dirOption->getShortcut());
        $this->assertTrue($dirOption->isValueOptional());
        $this->assertNull($dirOption->getDefault());

        $this->assertTrue($command->getDefinition()->hasOption('concurrency'));
        $concurrencyOption = $command->getDefinition()->getOption('concurrency');
        $this->assertSame('c', $concurrencyOption->getShortcut());
        $this->assertTrue($concurrencyOption->isValueOptional());
        $this->assertSame(4, $concurrencyOption->getDefault());
    }

    public function testGetRepositoryPathDefaultsToCurrentWorkingDirectory(): void
    {
        $command = new Pull();
        $input = new ArrayInput([], $command->getDefinition());
        $input->bind($command->getDefinition());

        $refProp = new \ReflectionProperty(Pull::class, 'oInput');
        $refProp->setValue($command, $input);

        $repo = new Repository((object) [
            'name'      => 'common',
            'full_name' => 'nails/common',
        ]);

        $refMethod = new \ReflectionMethod(Pull::class, 'getRepositoryPath');
        $path = $refMethod->invoke($command, $repo);

        $this->assertSame(getcwd() . '/common', $path);
    }

    public function testGetRepositoryPathUsesOverriddenDirectory(): void
    {
        $command = new Pull();
        $input = new ArrayInput(['--dir' => '/custom/path'], $command->getDefinition());
        $input->bind($command->getDefinition());

        $refProp = new \ReflectionProperty(Pull::class, 'oInput');
        $refProp->setValue($command, $input);

        $repo = new Repository((object) [
            'name'      => 'common',
            'full_name' => 'nails/common',
        ]);

        $refMethod = new \ReflectionMethod(Pull::class, 'getRepositoryPath');
        $path = $refMethod->invoke($command, $repo);

        $this->assertSame('/custom/path/common', $path);
    }

    public function testValidateDirectorySucceedsWhenDirectoryExists(): void
    {
        $command = new Pull();
        $input = new ArrayInput(['--dir' => __DIR__], $command->getDefinition());
        $input->bind($command->getDefinition());

        $refProp = new \ReflectionProperty(Pull::class, 'oInput');
        $refProp->setValue($command, $input);

        $refMethod = new \ReflectionMethod(Pull::class, 'validateDirectory');
        $result = $refMethod->invoke($command);

        $this->assertSame($command, $result);
    }

    public function testValidateDirectoryThrowsExceptionWhenDirectoryDoesNotExist(): void
    {
        $nonExistentPath = sys_get_temp_dir() . '/non_existent_nails_dir_' . uniqid();

        $command = new Pull();
        $input = new ArrayInput(['--dir' => $nonExistentPath], $command->getDefinition());
        $input->bind($command->getDefinition());

        $refProp = new \ReflectionProperty(Pull::class, 'oInput');
        $refProp->setValue($command, $input);

        $this->expectException(DoesNotExistException::class);
        $this->expectExceptionMessage('"' . $nonExistentPath . '/" does not exist');

        $refMethod = new \ReflectionMethod(Pull::class, 'validateDirectory');
        $refMethod->invoke($command);
    }

    public function testCreateRepositoryProcessGeneratesBranchCheckForUpdate(): void
    {
        $command = new Pull();
        $input = new ArrayInput(['--branch' => 'feature-test'], $command->getDefinition());
        $input->bind($command->getDefinition());

        $refProp = new \ReflectionProperty(Pull::class, 'oInput');
        $refProp->setValue($command, $input);

        $repo = new Repository((object) [
            'name'           => 'common',
            'full_name'      => 'nails/common',
            'default_branch' => 'master',
        ]);

        $refMethod = new \ReflectionMethod(Pull::class, 'createRepositoryProcess');
        /** @var \Symfony\Component\Process\Process $process */
        $process = $refMethod->invoke($command, $repo, 'update');

        $commandLine = $process->getCommandLine();
        $this->assertStringContainsString('refs/heads/\'feature-test\'', $commandLine);
        $this->assertStringContainsString('refs/remotes/origin/\'feature-test\'', $commandLine);
        $this->assertStringContainsString('echo \'branch feature-test does not exist\' 1>&2', $commandLine);
    }

    public function testCreateRepositoryProcessFailsWhenBranchDoesNotExist(): void
    {
        $tempDir = sys_get_temp_dir() . '/nails_test_repo_' . uniqid();
        mkdir($tempDir, 0777, true);

        // Initialize a minimal git repository with master branch
        exec('cd ' . escapeshellarg($tempDir) . ' && git init && git config user.email "test@example.com" && git config user.name "Test" && git commit --allow-empty -m "Initial commit"');

        $command = new Pull();
        $input = new ArrayInput(['--dir' => sys_get_temp_dir(), '--branch' => 'non-existent-branch'], $command->getDefinition());
        $input->bind($command->getDefinition());

        $refProp = new \ReflectionProperty(Pull::class, 'oInput');
        $refProp->setValue($command, $input);

        $repo = new Repository((object) [
            'name'           => basename($tempDir),
            'full_name'      => 'nails/' . basename($tempDir),
            'default_branch' => 'master',
        ]);

        $refMethod = new \ReflectionMethod(Pull::class, 'createRepositoryProcess');
        /** @var \Symfony\Component\Process\Process $process */
        $process = $refMethod->invoke($command, $repo, 'update');
        $process->run();

        $this->assertFalse($process->isSuccessful());
        $this->assertSame('branch non-existent-branch does not exist', trim($process->getErrorOutput()));

        // Cleanup
        exec('rm -rf ' . escapeshellarg($tempDir));
    }

    public function testLinkAgentKitSkipsWhenAgentsDirectoryIsMissing(): void
    {
        $tempDir = sys_get_temp_dir() . '/nails_agent_kit_' . uniqid();
        mkdir($tempDir, 0777, true);

        $command = $this->makePullCommand($tempDir);
        $refMethod = new \ReflectionMethod(Pull::class, 'linkAgentKit');
        $refMethod->invoke($command);

        $this->assertFileDoesNotExist($tempDir . '/AGENTS.md');
        $this->assertFileDoesNotExist($tempDir . '/CLAUDE.md');

        exec('rm -rf ' . escapeshellarg($tempDir));
    }

    public function testLinkAgentKitCreatesSymlinksWhenAgentsCloneExists(): void
    {
        $tempDir = sys_get_temp_dir() . '/nails_agent_kit_' . uniqid();
        mkdir($tempDir . '/agents/skills', 0777, true);
        file_put_contents($tempDir . '/agents/AGENTS.md', "# Nails\n");
        file_put_contents($tempDir . '/agents/CLAUDE.md', "@AGENTS.md\n");

        $command = $this->makePullCommand($tempDir);
        $refMethod = new \ReflectionMethod(Pull::class, 'linkAgentKit');
        $refMethod->invoke($command);

        $this->assertTrue(is_link($tempDir . '/AGENTS.md'));
        $this->assertSame('agents/AGENTS.md', readlink($tempDir . '/AGENTS.md'));
        $this->assertTrue(is_link($tempDir . '/CLAUDE.md'));
        $this->assertSame('agents/CLAUDE.md', readlink($tempDir . '/CLAUDE.md'));
        $this->assertTrue(is_link($tempDir . '/.agents/skills'));
        $this->assertSame('../agents/skills', readlink($tempDir . '/.agents/skills'));
        $this->assertTrue(is_link($tempDir . '/.claude/skills'));
        $this->assertSame('../agents/skills', readlink($tempDir . '/.claude/skills'));
        $this->assertTrue(is_link($tempDir . '/.cursor/skills'));
        $this->assertSame('../agents/skills', readlink($tempDir . '/.cursor/skills'));
        $this->assertSame("# Nails\n", file_get_contents($tempDir . '/AGENTS.md'));

        exec('rm -rf ' . escapeshellarg($tempDir));
    }

    public function testLinkAgentKitSkipsExistingRegularFiles(): void
    {
        $tempDir = sys_get_temp_dir() . '/nails_agent_kit_' . uniqid();
        mkdir($tempDir . '/agents', 0777, true);
        file_put_contents($tempDir . '/agents/AGENTS.md', "# Kit\n");
        file_put_contents($tempDir . '/AGENTS.md', "# Local\n");

        $command = $this->makePullCommand($tempDir);
        $refMethod = new \ReflectionMethod(Pull::class, 'linkAgentKit');
        $refMethod->invoke($command);

        $this->assertFalse(is_link($tempDir . '/AGENTS.md'));
        $this->assertSame("# Local\n", file_get_contents($tempDir . '/AGENTS.md'));

        exec('rm -rf ' . escapeshellarg($tempDir));
    }

    public function testLinkAgentKitReplacesIncorrectSymlink(): void
    {
        $tempDir = sys_get_temp_dir() . '/nails_agent_kit_' . uniqid();
        mkdir($tempDir . '/agents', 0777, true);
        file_put_contents($tempDir . '/agents/AGENTS.md', "# Kit\n");
        symlink('elsewhere', $tempDir . '/AGENTS.md');

        $command = $this->makePullCommand($tempDir);
        $refMethod = new \ReflectionMethod(Pull::class, 'linkAgentKit');
        $refMethod->invoke($command);

        $this->assertTrue(is_link($tempDir . '/AGENTS.md'));
        $this->assertSame('agents/AGENTS.md', readlink($tempDir . '/AGENTS.md'));

        exec('rm -rf ' . escapeshellarg($tempDir));
    }

    private function makePullCommand(string $dir): Pull
    {
        $command = new Pull();
        $input = new ArrayInput(['--dir' => $dir], $command->getDefinition());
        $input->bind($command->getDefinition());

        $refInput = new \ReflectionProperty(Pull::class, 'oInput');
        $refInput->setValue($command, $input);

        $refOutput = new \ReflectionProperty(Pull::class, 'oOutput');
        $refOutput->setValue($command, new BufferedOutput());

        return $command;
    }
}
