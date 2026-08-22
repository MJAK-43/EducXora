<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learner\Models\Learner;
use App\Domain\Learning\Models\Group;
use App\Domain\Learning\Models\GroupLearnerAssignment;
use App\Domain\Pedagogy\Enums\QuestionSource;
use App\Domain\Pedagogy\Models\LearnerLevelHistory;
use App\Domain\Pedagogy\Models\PlacementAttempt;
use App\Domain\Pedagogy\Models\PlacementAttemptQuestion;
use App\Domain\Pedagogy\Models\PlacementQuestion;
use App\Enums\MembershipStatus;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsPhaseTwoTenancy;
use Tests\TestCase;

final class PedagogyManagementTest extends TestCase
{
    use BuildsPhaseTwoTenancy;
    use RefreshDatabase;

    public function test_question_bank_combines_system_and_tenant_questions_without_cross_tenant_leak(): void
    {
        [$organizationA, $managerA, $membershipA] = $this->tenantWithUser('Manager');
        [$organizationB, , $membershipB] = $this->tenantWithUser('Manager');
        PlacementQuestion::factory()->create(['prompt' => 'Question système ?', 'level' => LearnerLevel::A1]);
        $this->setTenant($organizationA, $membershipA);
        $own = $this->organizationQuestion($organizationA, ['prompt' => 'Question A ?', 'level' => LearnerLevel::A1]);
        $this->setTenant($organizationB, $membershipB);
        $foreign = $this->organizationQuestion($organizationB, ['prompt' => 'Question B ?', 'level' => LearnerLevel::A1]);

        $this->actingAs($managerA)->withSession($this->tenantSession($organizationA))
            ->get('/pedagogy/questions')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Pedagogy/Questions/Index')
            ->has('questions.data', 2)
            ->where('questions.data.0.prompt', 'Question A ?')
            ->where('questions.data.1.prompt', 'Question système ?'));
        $this->actingAs($managerA)->withSession($this->tenantSession($organizationA))
            ->get('/pedagogy/questions/'.$foreign->uuid.'/edit')->assertNotFound();
        self::assertNotSame($own->uuid, $foreign->uuid);
    }

    public function test_manager_crud_is_audited_while_system_question_is_readonly_and_staff_cannot_mutate(): void
    {
        [$organization, $manager] = $this->tenantWithUser('Manager');
        $system = PlacementQuestion::factory()->create();
        $session = $this->tenantSession($organization);

        $this->actingAs($manager)->withSession($session)->post('/pedagogy/questions', $this->questionPayload())
            ->assertRedirect();
        $this->setTenant($organization, null);
        $question = PlacementQuestion::query()->where('organization_id', $organization->getKey())->sole();
        $this->actingAs($manager)->withSession($session)->patch('/pedagogy/questions/'.$question->uuid, $this->questionPayload(['prompt' => 'Wie heißt du genau?']))->assertRedirect();
        $this->actingAs($manager)->withSession($session)->patch('/pedagogy/questions/'.$question->uuid.'/disable')->assertRedirect();
        $this->actingAs($manager)->withSession($session)->patch('/pedagogy/questions/'.$question->uuid.'/enable')->assertRedirect();
        $this->actingAs($manager)->withSession($session)->patch('/pedagogy/questions/'.$system->uuid, $this->questionPayload())->assertForbidden();
        self::assertEqualsCanonicalizing(
            ['placement_question.created', 'placement_question.updated', 'placement_question.disabled', 'placement_question.enabled'],
            AuditLog::query()->where('organization_id', $organization->getKey())->where('action', 'like', 'placement_question.%')->pluck('action')->all(),
        );

        [$staffOrganization, $staff] = $this->tenantWithUser('Staff');
        $this->actingAs($staff)->withSession($this->tenantSession($staffOrganization))->get('/pedagogy/questions')->assertOk();
        $this->actingAs($staff)->withSession($this->tenantSession($staffOrganization))->post('/pedagogy/questions', $this->questionPayload())->assertForbidden();
    }

    public function test_attempt_uses_immutable_snapshots_and_records_each_answer_once(): void
    {
        [$organization, $staff, $membership] = $this->tenantWithUser('Staff');
        $this->seedQuestionBank();
        $this->setTenant($organization, $membership);
        $learner = Learner::factory()->create(['initial_level' => LearnerLevel::A1]);
        $session = $this->tenantSession($organization);

        $this->actingAs($staff)->withSession($session)->post('/pedagogy/learners/'.$learner->uuid.'/tests')->assertRedirect();
        $this->setTenant($organization, $membership);
        $attempt = PlacementAttempt::query()->sole();
        self::assertSame(18, $attempt->question_count);
        $snapshot = PlacementAttemptQuestion::query()->where('attempt_id', $attempt->getKey())->orderBy('position')->firstOrFail();
        $source = PlacementQuestion::query()->findOrFail($snapshot->source_question_id);
        $originalPrompt = $snapshot->prompt_snapshot;
        $source->update(['prompt' => 'Texte modifié après démarrage']);
        self::assertSame($originalPrompt, $snapshot->fresh()->prompt_snapshot);

        $answer = ['question_uuid' => $snapshot->uuid, 'answer' => 'A', 'raw_score' => 999, 'suggested_level' => 'C2'];
        $this->actingAs($staff)->withSession($session)->post('/pedagogy/tests/'.$attempt->uuid.'/answers', $answer)->assertRedirect();
        $this->actingAs($staff)->withSession($session)->post('/pedagogy/tests/'.$attempt->uuid.'/answers', $answer)->assertSessionHasErrors('answer');
        $this->assertDatabaseHas('placement_attempt_questions', ['id' => $snapshot->getKey(), 'selected_choice' => 'A', 'is_correct' => true]);
        $this->assertDatabaseHas('placement_attempts', ['id' => $attempt->getKey(), 'raw_score' => null, 'suggested_level' => null]);
    }

    public function test_incomplete_and_duplicate_finalization_are_rejected_and_server_suggests_available_group(): void
    {
        [$organization, $staff, $membership] = $this->tenantWithUser('Staff');
        $this->seedQuestionBank();
        $teacher = $this->teacherFor($organization);
        $this->setTenant($organization, $membership);
        $learner = Learner::factory()->create(['initial_level' => LearnerLevel::A1]);
        $group = Group::factory()->create(['level' => LearnerLevel::C2, 'teacher_membership_id' => $teacher->getKey()]);
        $session = $this->tenantSession($organization);
        $this->actingAs($staff)->withSession($session)->post('/pedagogy/learners/'.$learner->uuid.'/tests')->assertRedirect();
        $this->setTenant($organization, $membership);
        $attempt = PlacementAttempt::query()->sole();

        $this->actingAs($staff)->withSession($session)->patch('/pedagogy/tests/'.$attempt->uuid.'/complete')->assertSessionHasErrors('test');
        $this->setTenant($organization, $membership);
        foreach ($attempt->questions()->get() as $question) {
            $this->actingAs($staff)->withSession($session)->post('/pedagogy/tests/'.$attempt->uuid.'/answers', ['question_uuid' => $question->uuid, 'answer' => 'A'])->assertRedirect();
        }
        $this->actingAs($staff)->withSession($session)->patch('/pedagogy/tests/'.$attempt->uuid.'/complete')->assertRedirect();
        $this->actingAs($staff)->withSession($session)->patch('/pedagogy/tests/'.$attempt->uuid.'/complete')->assertSessionHasErrors('test');
        $this->assertDatabaseHas('placement_attempts', [
            'id' => $attempt->getKey(), 'raw_score' => 18, 'percentage' => 100,
            'suggested_level' => 'C2', 'suggested_group_id' => $group->getKey(), 'status' => 'completed',
        ]);
        $this->assertDatabaseHas('audit_logs', ['organization_id' => $organization->getKey(), 'action' => 'placement_test.completed']);
    }

    public function test_director_review_preserves_suggestion_updates_level_assigns_group_and_is_not_replayable(): void
    {
        [$organization, $manager, $membership] = $this->tenantWithUser('Manager');
        $this->seedQuestionBank();
        $teacher = $this->teacherFor($organization);
        $this->setTenant($organization, $membership);
        $learner = Learner::factory()->create(['initial_level' => LearnerLevel::A1]);
        $group = Group::factory()->create(['level' => LearnerLevel::B2, 'teacher_membership_id' => $teacher->getKey(), 'capacity' => 4]);
        $attempt = $this->completedAttempt($learner, $manager, LearnerLevel::C2);
        $session = $this->tenantSession($organization);

        $payload = ['validated_level' => 'B2', 'group_uuid' => $group->uuid, 'reason' => 'Entretien oral plus représentatif.'];
        $this->actingAs($manager)->withSession($session)->patch('/pedagogy/tests/'.$attempt->uuid.'/review', $payload)->assertRedirect();
        $this->actingAs($manager)->withSession($session)->patch('/pedagogy/tests/'.$attempt->uuid.'/review', $payload)->assertSessionHasErrors('test');
        $this->assertDatabaseHas('placement_attempts', [
            'id' => $attempt->getKey(), 'suggested_level' => 'C2', 'validated_level' => 'B2',
            'validated_group_id' => $group->getKey(), 'status' => 'reviewed',
        ]);
        $this->assertDatabaseHas('learners', ['id' => $learner->getKey(), 'initial_level' => 'A1', 'current_level' => 'B2']);
        $this->assertDatabaseHas('learner_level_histories', [
            'learner_id' => $learner->getKey(), 'from_level' => 'A1', 'to_level' => 'B2', 'source' => 'director_override',
        ]);
        $this->assertDatabaseHas('group_learner_assignments', ['learner_id' => $learner->getKey(), 'group_id' => $group->getKey(), 'detached_at' => null]);
        $this->assertDatabaseHas('audit_logs', ['organization_id' => $organization->getKey(), 'action' => 'placement_test.reviewed']);
    }

    public function test_teacher_can_change_only_own_active_group_learner_level_and_history_is_append_only(): void
    {
        [$organization, $manager, $managerMembership] = $this->tenantWithUser('Manager');
        $teacher = $this->teacherFor($organization);
        $otherTeacher = $this->teacherFor($organization);
        $this->setTenant($organization, $managerMembership);
        $ownLearner = Learner::factory()->create(['initial_level' => LearnerLevel::A1]);
        $otherLearner = Learner::factory()->create(['initial_level' => LearnerLevel::A1]);
        $ownGroup = Group::factory()->create(['level' => LearnerLevel::A1, 'teacher_membership_id' => $teacher->getKey()]);
        $otherGroup = Group::factory()->create(['level' => LearnerLevel::A1, 'teacher_membership_id' => $otherTeacher->getKey()]);
        GroupLearnerAssignment::query()->create(['group_id' => $ownGroup->getKey(), 'learner_id' => $ownLearner->getKey(), 'assigned_at' => now()]);
        GroupLearnerAssignment::query()->create(['group_id' => $otherGroup->getKey(), 'learner_id' => $otherLearner->getKey(), 'assigned_at' => now()]);
        $session = $this->tenantSession($organization);

        $this->actingAs($teacher->user)->withSession($session)->patch('/learners/'.$ownLearner->uuid.'/level', ['level' => 'A2', 'reason' => 'Progression confirmée en classe.'])->assertRedirect();
        $this->actingAs($teacher->user)->withSession($session)->patch('/learners/'.$otherLearner->uuid.'/level', ['level' => 'A2', 'reason' => 'Tentative sur un autre groupe.'])->assertForbidden();
        $this->setTenant($organization, $managerMembership);
        $this->assertDatabaseHas('learner_level_histories', ['learner_id' => $ownLearner->getKey(), 'source' => 'teacher_evaluation']);
        $history = LearnerLevelHistory::query()->where('learner_id', $ownLearner->getKey())->sole();
        $this->expectException(\LogicException::class);
        $history->update(['reason' => 'Réécriture interdite']);
    }

    public function test_locked_capacity_allows_only_one_of_two_competing_group_validations(): void
    {
        [$organization, $manager, $membership] = $this->tenantWithUser('Manager');
        $teacher = $this->teacherFor($organization);
        $this->setTenant($organization, $membership);
        $firstLearner = Learner::factory()->create(['initial_level' => LearnerLevel::A1]);
        $secondLearner = Learner::factory()->create(['initial_level' => LearnerLevel::A1]);
        $group = Group::factory()->create(['level' => LearnerLevel::B1, 'teacher_membership_id' => $teacher->getKey(), 'capacity' => 1]);
        $firstAttempt = $this->completedAttempt($firstLearner, $manager, LearnerLevel::B1);
        $secondAttempt = $this->completedAttempt($secondLearner, $manager, LearnerLevel::B1);
        $session = $this->tenantSession($organization);
        $payload = ['validated_level' => 'B1', 'group_uuid' => $group->uuid, 'reason' => 'Affectation confirmée après entretien.'];

        $this->actingAs($manager)->withSession($session)->patch('/pedagogy/tests/'.$firstAttempt->uuid.'/review', $payload)->assertRedirect();
        $this->actingAs($manager)->withSession($session)->patch('/pedagogy/tests/'.$secondAttempt->uuid.'/review', $payload)->assertSessionHasErrors('learner_uuid');
        $this->assertDatabaseCount('group_learner_assignments', 1);
        $this->assertDatabaseHas('placement_attempts', ['id' => $secondAttempt->getKey(), 'status' => 'completed', 'validated_level' => null]);
        $this->assertDatabaseHas('learners', ['id' => $secondLearner->getKey(), 'current_level' => 'A1']);
    }

    public function test_tenant_cannot_access_foreign_attempt_and_accountant_has_no_pedagogy_access(): void
    {
        [$organizationA, $managerA, $membershipA] = $this->tenantWithUser('Manager');
        [$organizationB, $managerB, $membershipB] = $this->tenantWithUser('Manager');
        $this->seedQuestionBank();
        $this->setTenant($organizationB, $membershipB);
        $learnerB = Learner::factory()->create();
        $teacherB = $this->teacherFor($organizationB);
        $groupB = Group::factory()->create(['level' => LearnerLevel::A1, 'teacher_membership_id' => $teacherB->getKey()]);
        $attemptB = $this->completedAttempt($learnerB, $managerB, LearnerLevel::A1);

        $this->setTenant($organizationA, $membershipA);
        $learnerA = Learner::factory()->create();
        $attemptA = $this->completedAttempt($learnerA, $managerA, LearnerLevel::A1);

        $this->actingAs($managerA)->withSession($this->tenantSession($organizationA))->get('/pedagogy/tests/'.$attemptB->uuid)->assertNotFound();
        $this->actingAs($managerA)->withSession($this->tenantSession($organizationA))->patch('/pedagogy/tests/'.$attemptB->uuid.'/review', ['validated_level' => 'A1'])->assertNotFound();
        $this->actingAs($managerA)->withSession($this->tenantSession($organizationA))->post('/pedagogy/learners/'.$learnerB->uuid.'/tests')->assertNotFound();
        $this->actingAs($managerA)->withSession($this->tenantSession($organizationA))->patch('/pedagogy/tests/'.$attemptA->uuid.'/review', ['validated_level' => 'A1', 'group_uuid' => $groupB->uuid, 'reason' => 'Injection groupe étranger.'])->assertNotFound();

        [$accountantOrganization, $accountant] = $this->tenantWithUser('Accountant');
        $this->actingAs($accountant)->withSession($this->tenantSession($accountantOrganization))->get('/pedagogy/questions')->assertForbidden();
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function questionPayload(array $overrides = []): array
    {
        return [...[
            'language' => 'de', 'level' => 'A1', 'prompt' => 'Wie heißt du?',
            'choice_a' => 'Ich heiße Ada.', 'choice_b' => 'Guten Abend.',
            'choice_c' => 'Danke schön.', 'choice_d' => 'Bis morgen.', 'correct_choice' => 'A',
        ], ...$overrides];
    }

    private function seedQuestionBank(): void
    {
        foreach (LearnerLevel::cases() as $level) {
            PlacementQuestion::factory()->count(3)->create(['level' => $level, 'correct_choice' => 'A']);
        }
    }

    /** @param array<string, mixed> $overrides */
    private function organizationQuestion(Organization $organization, array $overrides = []): PlacementQuestion
    {
        return PlacementQuestion::factory()->create([...[
            'organization_id' => $organization->getKey(), 'source' => QuestionSource::Organization,
        ], ...$overrides]);
    }

    private function completedAttempt(Learner $learner, User $actor, LearnerLevel $suggested): PlacementAttempt
    {
        return PlacementAttempt::query()->create([
            'learner_id' => $learner->getKey(), 'status' => 'completed', 'language' => 'de',
            'question_count' => 18, 'scoring_version' => 'v1', 'raw_score' => 18,
            'percentage' => 100, 'suggested_level' => $suggested, 'started_by' => $actor->getKey(),
            'started_at' => now()->subMinute(), 'completed_by' => $actor->getKey(), 'completed_at' => now(),
        ]);
    }

    private function teacherFor(Organization $organization): OrganizationMembership
    {
        $user = User::factory()->create();
        $membership = OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(), 'user_id' => $user->getKey(),
            'status' => MembershipStatus::Active, 'joined_at' => now(),
        ]);
        $role = Role::query()->where('organization_id', $organization->getKey())->where('name', 'Teacher/Trainer')->sole();
        app(MembershipAuthorizer::class)->syncRoles($membership, [$role->getKey()]);

        return $membership->load('user');
    }

    private function setTenant(Organization $organization, ?OrganizationMembership $membership): void
    {
        app(TenantContext::class)->set($organization, $membership);
    }
}
