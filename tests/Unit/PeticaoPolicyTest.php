<?php

namespace Tests\Unit;

use App\PeticaoNormalizada;
use App\Policies\PeticaoPolicy;
use App\User;
use PHPUnit\Framework\TestCase;

class PeticaoPolicyTest extends TestCase
{
    private function user($id, $level, $sector, $legacyId = null)
    {
        $user = new User(['nivel_usu' => $level, 'id_setor' => $sector, 'legacy_usuario_id' => $legacyId]);
        $user->id = $id;

        return $user;
    }

    private function peticaoOf(User $owner = null, array $attributes = [])
    {
        $peticao = new PeticaoNormalizada($attributes + ['user_id' => $owner ? $owner->id : null]);
        $peticao->setRelation('user', $owner);

        return $peticao;
    }

    public function test_owner_and_admin_can_access()
    {
        $policy = new PeticaoPolicy();
        $owner = $this->user(10, 'USU', 5);
        $peticao = $this->peticaoOf($owner);

        $this->assertTrue($policy->access($owner, $peticao));
        $this->assertTrue($policy->access($this->user(1, 'ADM', null), $peticao));
    }

    public function test_colleague_of_the_same_sector_cannot_access()
    {
        $policy = new PeticaoPolicy();
        $peticao = $this->peticaoOf($this->user(10, 'USU', 5));

        $this->assertFalse($policy->access($this->user(11, 'USU', 5), $peticao));
    }

    public function test_manager_can_access_only_within_own_sector()
    {
        $policy = new PeticaoPolicy();
        $peticao = $this->peticaoOf($this->user(10, 'USU', 5));

        $this->assertTrue($policy->access($this->user(20, 'GER', 5), $peticao));
        $this->assertFalse($policy->access($this->user(21, 'GER', 6), $peticao));
        $this->assertFalse($policy->access($this->user(22, 'GER', null), $peticao));
    }

    public function test_manager_without_sector_does_not_match_owner_without_sector()
    {
        $policy = new PeticaoPolicy();
        $peticao = $this->peticaoOf($this->user(10, 'USU', null));

        $this->assertFalse($policy->access($this->user(22, 'GER', null), $peticao));
        $this->assertFalse($policy->access($this->user(23, 'GER', 0), $peticao));
    }

    public function test_peticao_without_author_is_restricted_to_admin()
    {
        $policy = new PeticaoPolicy();
        $peticao = $this->peticaoOf(null);

        $this->assertFalse($policy->access($this->user(10, 'USU', 5), $peticao));
        $this->assertFalse($policy->access($this->user(20, 'GER', 5), $peticao));
        $this->assertTrue($policy->access($this->user(1, 'ADM', null), $peticao));
    }

    public function test_unlinked_legacy_peticao_belongs_to_the_legacy_author()
    {
        $policy = new PeticaoPolicy();
        $peticao = $this->peticaoOf(null, ['legacy_usuario_id' => 901]);

        $this->assertTrue($policy->access($this->user(10, 'USU', 5, 901), $peticao));
        $this->assertFalse($policy->access($this->user(11, 'USU', 5, 902), $peticao));
        $this->assertFalse($policy->access($this->user(12, 'USU', 5, null), $peticao));
    }
}
