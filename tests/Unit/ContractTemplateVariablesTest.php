<?php

use App\Support\ContractTemplateVariables;

test('sanitize html strips scripts and keeps variables', function () {
    $html = '<p>Olá <span data-template-variable="inquilino_nome" class="x">{{inquilino_nome}}</span></p><script>bad()</script>';

    $clean = ContractTemplateVariables::sanitizeHtml($html);

    expect($clean)
        ->toContain('<span data-template-variable="inquilino_nome">{{inquilino_nome}}</span>')
        ->toContain('<p>')
        ->not->toContain('<script>')
        ->not->toContain('class="x"');
});

test('sanitize html wraps bare mustache variables', function () {
    $clean = ContractTemplateVariables::sanitizeHtml('<p>Valor {{valor_aluguel}}</p>');

    expect($clean)->toContain(
        '<span data-template-variable="valor_aluguel">{{valor_aluguel}}</span>',
    );
});

test('blank detection ignores empty paragraphs', function () {
    expect(ContractTemplateVariables::isBlank('<p></p>'))->toBeTrue()
        ->and(ContractTemplateVariables::isBlank('<p>ok</p>'))->toBeFalse()
        ->and(ContractTemplateVariables::isBlank(
            '<p><span data-template-variable="imovel_nome">{{imovel_nome}}</span></p>',
        ))->toBeFalse();
});

test('catalog keys are unique', function () {
    $keys = ContractTemplateVariables::keys();

    expect($keys)->not->toBeEmpty()
        ->and($keys)->toHaveCount(count(array_unique($keys)));
});

test('fotos_vistoria is a known catalog key marked as html', function () {
    expect(ContractTemplateVariables::keys())->toContain('fotos_vistoria')
        ->and(ContractTemplateVariables::isHtmlKey('fotos_vistoria'))->toBeTrue()
        ->and(ContractTemplateVariables::isHtmlKey('inquilino_nome'))->toBeFalse();
});

test('isReferenced detects the literal token in template content', function () {
    expect(ContractTemplateVariables::isReferenced('<p>{{fotos_vistoria}}</p>', 'fotos_vistoria'))->toBeTrue()
        ->and(ContractTemplateVariables::isReferenced('<p>Sem fotos aqui</p>', 'fotos_vistoria'))->toBeFalse();
});
