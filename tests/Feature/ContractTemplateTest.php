<?php

use App\Models\ContractTemplate;
use App\Models\User;
use App\Support\ContractTemplateVariables;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admin template form receives variable catalog', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.templates.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/templates/Form')
            ->has('variables', count(ContractTemplateVariables::catalog()))
            ->where('variables.0.key', 'inquilino_nome'));
});

test('admin can store template with html content and variables', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.templates.store'), [
            'name' => 'Locação',
            'content' => '<p>Contrato de <span data-template-variable="inquilino_nome">{{inquilino_nome}}</span></p><script>alert(1)</script>',
        ])
        ->assertRedirect(route('admin.templates.index'));

    $template = ContractTemplate::query()->first();

    expect($template)->not->toBeNull()
        ->and($template->name)->toBe('Locação')
        ->and($template->content)->toContain('data-template-variable="inquilino_nome"')
        ->and($template->content)->toContain('{{inquilino_nome}}')
        ->and($template->content)->not->toContain('<script>');
});

test('admin cannot store blank template content', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('admin.templates.create'))
        ->post(route('admin.templates.store'), [
            'name' => 'Vazio',
            'content' => '<p></p>',
        ])
        ->assertRedirect(route('admin.templates.create'))
        ->assertSessionHasErrors('content');
});

test('admin can update template', function () {
    $admin = User::factory()->admin()->create();
    $template = ContractTemplate::factory()->create([
        'name' => 'Antigo',
        'content' => '<p>Texto</p>',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.templates.update', $template), [
            'name' => 'Novo',
            'content' => '<p>Aluguel: {{valor_aluguel}}</p>',
        ])
        ->assertRedirect(route('admin.templates.index'));

    expect($template->fresh())
        ->name->toBe('Novo')
        ->content->toContain('data-template-variable="valor_aluguel"');
});
