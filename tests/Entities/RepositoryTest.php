<?php

namespace Nails\Cli\Tests\Entities;

use Nails\Cli\Entities\Repository;
use PHPUnit\Framework\TestCase;

final class RepositoryTest extends TestCase
{
    public function testPropertiesAreInitializedCorrectly(): void
    {
        $raw = (object) [
            'name'           => 'common',
            'full_name'      => 'nails/common',
            'default_branch' => 'develop',
            'ssh_url'        => 'git@github.com:nails/common.git',
            'archived'       => false,
        ];

        $repo = new Repository($raw);

        $this->assertSame($raw, $repo->oRepository);
        $this->assertSame('common', $repo->name);
        $this->assertSame('nails/common', $repo->full_name);
        $this->assertSame('develop', $repo->default_branch);
        $this->assertSame('git@github.com:nails/common.git', $repo->ssh_url);
        $this->assertFalse($repo->archived);
    }

    public function testMissingPropertiesDefaultToNull(): void
    {
        $raw = (object) [];

        $repo = new Repository($raw);

        $this->assertSame($raw, $repo->oRepository);
        $this->assertNull($repo->name);
        $this->assertNull($repo->full_name);
        $this->assertNull($repo->default_branch);
        $this->assertNull($repo->ssh_url);
        $this->assertNull($repo->archived);
    }
}
