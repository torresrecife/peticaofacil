<?php

namespace Tests\Unit;

use App\Policies\UserPolicy;
use App\User;
use PHPUnit\Framework\TestCase;

class UserPolicyTest extends TestCase
{
    public function test_management_requires_lower_role_and_complete_client_coverage()
    {
        $policy = new UserPolicy();
        $manager = new User(['nivel_usu' => 'GER', 'id_cliente' => '1,2', 'id_setor' => 1]);
        $manager->id = 1;
        foreach ([
            ['USU', '1', true], ['USU', '1, 2', true], ['USU', '2,3', false],
            ['USU', '11', false], ['USU', '0', false], ['USU', '', false],
            ['ADM', '1', false], ['GER', '1', false], ['USU', '0,1', false],
        ] as $case) {
            $target = new User(['nivel_usu' => $case[0], 'id_cliente' => $case[1], 'id_setor' => 1]);
            $target->id = 2;
            $this->assertSame($case[2], $policy->update($manager, $target), implode(':', $case));
        }
        $this->assertFalse($policy->update($manager, $manager));
        $this->assertTrue($policy->create($manager));
        $target->id_cliente = '1';
        $target->id_setor = 2;
        $this->assertFalse($policy->update($manager, $target));
        $target->id_setor = null;
        $this->assertFalse($policy->update($manager, $target));
        $manager->id_setor = null;
        $this->assertFalse($policy->create($manager));
        $this->assertFalse($policy->update($manager, $target));
        $manager->id_setor = 1;
        $manager->id_cliente = '0';
        $this->assertFalse($policy->create($manager));
        $manager->nivel_usu = 'USU';
        $manager->id_cliente = '1,2';
        $this->assertFalse($policy->create($manager));
        $this->assertFalse($policy->update($manager, $target));
        $manager->nivel_usu = 'ADM';
        $this->assertTrue($policy->create($manager));
        $this->assertTrue($policy->update($manager, $target));
    }
}
