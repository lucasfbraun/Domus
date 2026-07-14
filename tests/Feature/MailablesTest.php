<?php

use App\Mail\ContractDocumentReviewedMail;
use App\Mail\OccurrenceReportedMail;
use App\Mail\OccurrenceUpdatedMail;

test('occurrence reported mailable has expected content', function () {
    $mailable = new OccurrenceReportedMail(
        tenantName: 'Maria',
        propertyName: 'Apartamento Centro',
        description: 'Vazamento no banheiro',
        photoCount: 2,
    );

    $mailable->assertHasSubject('Nova ocorrencia reportada: Apartamento Centro');
    $mailable->assertSeeInHtml('Maria');
    $mailable->assertSeeInHtml('Apartamento Centro');
    $mailable->assertSeeInHtml('Vazamento no banheiro');
    $mailable->assertSeeInHtml('2 foto(s) anexada(s)');
});

test('occurrence updated mailable has expected content', function () {
    $mailable = new OccurrenceUpdatedMail(
        propertyName: 'Casa Jardim',
        statusLabel: 'Resolvida',
        resolutionNote: 'Consertado',
    );

    $mailable->assertHasSubject('Ocorrencia atualizada: Casa Jardim');
    $mailable->assertSeeInHtml('Casa Jardim');
    $mailable->assertSeeInHtml('Resolvida');
    $mailable->assertSeeInHtml('Consertado');
});

test('contract document reviewed mailable has expected content', function () {
    $mailable = new ContractDocumentReviewedMail(
        propertyName: 'Sala Comercial',
        approved: false,
        reviewNote: 'Assinatura incompleta',
    );

    $mailable->assertHasSubject('Contrato rejeitado: Sala Comercial');
    $mailable->assertSeeInHtml('Sala Comercial');
    $mailable->assertSeeInHtml('rejeitado');
    $mailable->assertSeeInHtml('Assinatura incompleta');
    $mailable->assertSeeInHtml('novo arquivo assinado');
});
