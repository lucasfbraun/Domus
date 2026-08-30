<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Curated catalog of the app's features for the "Funcionalidades" admin
 * page: each entry points at the source file(s) that implement it and the
 * test file(s) that actually exercise it. Hand-maintained rather than
 * derived from routes, because "is this genuinely tested" requires judgment
 * (a filename match doesn't mean the behavior is actually asserted) — see
 * docs/adr/0008-feature-checks-page.md.
 *
 * Test paths are re-checked for existence on every call, so a renamed or
 * deleted test file shows up as untested instead of silently lying.
 */
class FeatureCatalog
{
    /**
     * @return list<array{area: string, name: string, description: string, source: list<string>, tests: list<string>, note: string|null}>
     */
    public static function all(): array
    {
        return array_map(
            fn (array $entry) => [
                ...$entry,
                'tests' => array_values(array_filter(
                    $entry['tests'],
                    fn (string $path) => File::exists(base_path($path)),
                )),
            ],
            self::entries(),
        );
    }

    /**
     * @return list<array{area: string, name: string, description: string, source: list<string>, tests: list<string>, note: string|null}>
     */
    private static function entries(): array
    {
        return [
            // Cadastros
            [
                'area' => 'Cadastros',
                'name' => 'Proprietários (CRUD + login compartilhado)',
                'description' => 'Cadastro de proprietários, com opção de login dedicado ou compartilhado com um usuário já existente.',
                'source' => ['app/Http/Controllers/Admin/OwnerController.php'],
                'tests' => ['tests/Feature/OwnerCrudTest.php', 'tests/Feature/PaginationTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Cadastros',
                'name' => 'Imóveis (CRUD + foto de capa)',
                'description' => 'Cadastro de imóveis, tipos, e foto de capa via Media Library.',
                'source' => ['app/Http/Controllers/Admin/PropertyController.php'],
                'tests' => ['tests/Feature/PropertyFormTest.php', 'tests/Feature/PropertyCoverPhotoTest.php', 'tests/Feature/PaginationTest.php'],
                'note' => 'Exclusão (destroy) não tem teste.',
            ],
            [
                'area' => 'Cadastros',
                'name' => 'Inquilinos (CRUD + login + bloqueio de exclusão)',
                'description' => 'Cadastro de inquilinos, login do portal, e bloqueio de exclusão quando há contrato ativo.',
                'source' => ['app/Http/Controllers/Admin/TenantController.php'],
                'tests' => ['tests/Feature/TenantCrudTest.php', 'tests/Feature/TenantAccessTest.php', 'tests/Feature/PaginationTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Cadastros',
                'name' => 'Recebedores (CRUD + login compartilhado)',
                'description' => 'Cadastro de recebedores de pagamento, com login dedicado ou compartilhado.',
                'source' => ['app/Http/Controllers/Admin/ReceiverController.php'],
                'tests' => ['tests/Feature/ReceiverCrudTest.php', 'tests/Feature/PaginationTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Cadastros',
                'name' => 'Administradores (CRUD)',
                'description' => 'Cadastro de contas com papel Admin.',
                'source' => ['app/Http/Controllers/Admin/AdminUserController.php'],
                'tests' => ['tests/Feature/AdminUserCrudTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Cadastros',
                'name' => 'Validação de CPF/CNPJ e telefone',
                'description' => 'Normalização e validação de documentos e telefones brasileiros usados nos formulários de cadastro.',
                'source' => ['app/Rules', 'app/Support'],
                'tests' => ['tests/Feature/BrazilianFieldsValidationTest.php', 'tests/Unit/BrazilianDocumentTest.php', 'tests/Unit/BrazilianPhoneTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Cadastros',
                'name' => 'Login compartilhado entre papéis',
                'description' => 'Serviço central que permite um Recebedor/Proprietário usar um login já existente em vez de criar um novo, acumulando papéis.',
                'source' => ['app/Services/PortalAccountService.php'],
                'tests' => ['tests/Feature/PortalAccountServiceTest.php'],
                'note' => null,
            ],

            // Contratos
            [
                'area' => 'Contratos',
                'name' => 'Cadastro de contrato (dados financeiros, testemunhas)',
                'description' => 'Criação de contrato com valores, vencimento, juros/multa e testemunhas.',
                'source' => ['app/Http/Controllers/Admin/ContractController.php'],
                'tests' => ['tests/Feature/ContractParityTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Contratos',
                'name' => 'Listagem, edição e assinatura de testemunha',
                'description' => 'Telas de listar/criar/editar contrato e marcar testemunha como assinada.',
                'source' => ['app/Http/Controllers/Admin/ContractController.php'],
                'tests' => ['tests/Feature/ContractCrudTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Contratos',
                'name' => 'Bloqueio de exclusão de contrato assinado',
                'description' => 'Só permite excluir contrato em rascunho e sem assinatura gerada.',
                'source' => ['app/Http/Controllers/Admin/ContractController.php'],
                'tests' => ['tests/Feature/ContractDestroyValidationTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Contratos',
                'name' => 'Página de visualização do contrato (inquilino)',
                'description' => 'Tela pública/autenticada de visualização de um contrato específico.',
                'source' => ['app/Http/Controllers/ContractShowController.php'],
                'tests' => ['tests/Feature/ContractShowTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Contratos',
                'name' => 'Geração e revisão do documento do contrato',
                'description' => 'Gera o PDF do contrato a partir do modelo, permite aprovar/rejeitar e subir a via assinada.',
                'source' => ['app/Http/Controllers/Admin/ContractDocumentController.php', 'app/Services/ContractDocumentService.php'],
                'tests' => ['tests/Feature/ContractParityTest.php'],
                'note' => 'Download do documento gerado/assinado tem cobertura parcial.',
            ],
            [
                'area' => 'Contratos',
                'name' => 'Assinatura do proprietário (upload + marcação)',
                'description' => 'Upload do documento assinado pelo proprietário e marcação de assinatura.',
                'source' => ['app/Http/Controllers/Admin/ContractDocumentController.php', 'app/Http/Controllers/Admin/ContractController.php'],
                'tests' => ['tests/Feature/ContractOwnerSignatureTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Contratos',
                'name' => 'Variáveis de modelo de contrato',
                'description' => 'Substituição de variáveis ({{inquilino_nome}}, etc.) e o modelo padrão de locação.',
                'source' => ['app/Support/ContractTemplateVariables.php', 'app/Support/StandardLeaseContractTemplate.php'],
                'tests' => ['tests/Feature/ContractParityTest.php', 'tests/Feature/StandardLeaseContractTemplateTest.php', 'tests/Unit/ContractTemplateVariablesTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Contratos',
                'name' => 'Modelos de contrato (CRUD)',
                'description' => 'Cadastro de modelos de contrato reutilizáveis.',
                'source' => ['app/Http/Controllers/Admin/ContractTemplateController.php'],
                'tests' => ['tests/Feature/ContractTemplateTest.php'],
                'note' => 'Bloqueio de exclusão de modelo em uso não tem teste.',
            ],
            [
                'area' => 'Contratos',
                'name' => 'Liberação de assinatura (proprietário + testemunhas)',
                'description' => 'Regra que decide quando um contrato está pronto para o inquilino assinar.',
                'source' => ['app/Services/ContractSignatureService.php'],
                'tests' => ['tests/Feature/ContractSignatureServiceTest.php', 'tests/Feature/ContractParityTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Contratos',
                'name' => 'Fotos de vistoria',
                'description' => 'Upload, visualização e exclusão de fotos de vistoria vinculadas a um contrato.',
                'source' => ['app/Http/Controllers/Admin/ContractInspectionPhotoController.php'],
                'tests' => ['tests/Feature/ContractInspectionPhotoTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Contratos',
                'name' => 'Ocorrências (abertura, atualização, e-mails)',
                'description' => 'Inquilino abre ocorrência (manutenção etc.), admin acompanha e atualiza o status.',
                'source' => ['app/Http/Controllers/Admin/ContractOccurrenceController.php'],
                'tests' => ['tests/Feature/OccurrenceFlowTest.php', 'tests/Feature/MailablesTest.php', 'tests/Feature/PaginationTest.php'],
                'note' => 'A autorização de showPhoto (admin ou inquilino dono) não tem teste dedicado.',
            ],

            // Cobrança
            [
                'area' => 'Cobrança',
                'name' => 'Listagem de cobranças',
                'description' => 'Tela admin com todas as cobranças, valor com rateio discriminado.',
                'source' => ['app/Http/Controllers/Admin/ChargeController.php'],
                'tests' => ['tests/Feature/PaginationTest.php'],
                'note' => 'Só a renderização é testada.',
            ],
            [
                'area' => 'Cobrança',
                'name' => 'Gerar cobrança manualmente (botão admin)',
                'description' => 'Ação do admin para gerar a cobrança do mês de um contrato específico.',
                'source' => ['app/Http/Controllers/Admin/ChargeController.php'],
                'tests' => ['tests/Feature/ChargeGenerateTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Cobrança',
                'name' => 'Gerar e sincronizar Pix de uma cobrança',
                'description' => 'Botões "Gerar Pix" e "Sincronizar" na listagem de cobranças.',
                'source' => ['app/Http/Controllers/Admin/ChargeController.php'],
                'tests' => ['tests/Feature/MercadoPagoOrdersTest.php', 'tests/Feature/ChargePixHttpTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Cobrança',
                'name' => 'Lembrete manual de cobrança',
                'description' => 'Botão do admin para reenviar lembrete de uma cobrança específica.',
                'source' => ['app/Http/Controllers/Admin/ChargeController.php', 'app/Services/ReminderService.php'],
                'tests' => ['tests/Feature/ChargeGenerateTest.php', 'tests/Feature/ReminderServiceTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Cobrança',
                'name' => 'Recibo em PDF (com rateio discriminado)',
                'description' => 'Download do recibo de uma cobrança paga, mostrando aluguel e rateio separados quando houver.',
                'source' => ['app/Http/Controllers/Admin/ChargeController.php', 'resources/views/pdf/receipt.blade.php'],
                'tests' => ['tests/Feature/ReceiptPdfTest.php', 'tests/Feature/ReceiverPortalTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Cobrança',
                'name' => 'Geração automática mensal + dia configurável',
                'description' => 'Job diário que marca cobranças vencidas e gera a cobrança do mês a partir do dia configurado em Configurações.',
                'source' => ['app/Services/ChargeScheduler.php', 'app/Jobs/GenerateMonthlyChargesJob.php'],
                'tests' => ['tests/Feature/ChargeOverdueTest.php', 'tests/Feature/ChargeSchedulerTest.php'],
                'note' => 'A classe do Job em si (o wrapper agendado) não tem teste próprio, só o serviço que ela chama.',
            ],
            [
                'area' => 'Cobrança',
                'name' => 'Lembretes automáticos (varredura diária)',
                'description' => 'Job diário que envia lembretes de cobrança (antes/no dia/depois do vencimento) e de contrato perto de vencer.',
                'source' => ['app/Services/ReminderService.php', 'app/Jobs/RunReminderSweepJob.php'],
                'tests' => ['tests/Feature/ReminderServiceTest.php', 'tests/Feature/RunReminderSweepJobTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Cobrança',
                'name' => 'Caução: gerar e reembolsar',
                'description' => 'Cadastro de caução e marcação de reembolso.',
                'source' => ['app/Http/Controllers/Admin/DepositController.php'],
                'tests' => ['tests/Feature/DepositTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Cobrança',
                'name' => 'Caução: editar, excluir, Pix',
                'description' => 'Edição/exclusão de caução e geração/sincronismo de Pix.',
                'source' => ['app/Http/Controllers/Admin/DepositController.php'],
                'tests' => ['tests/Feature/DepositTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Cobrança',
                'name' => 'Dia de geração de cobrança (configuração)',
                'description' => 'Tela de Configurações que define o dia do mês em que as cobranças são geradas para todos os contratos.',
                'source' => ['app/Http/Controllers/Admin/BillingSettingController.php', 'app/Models/BillingSetting.php'],
                'tests' => ['tests/Feature/BillingSettingTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Cobrança',
                'name' => 'Rateio: calcular e aplicar em cobrança',
                'description' => 'Divide uma despesa entre imóveis e soma o valor à cobrança do mês de cada contrato.',
                'source' => ['app/Services/RateioService.php'],
                'tests' => ['tests/Feature/RateioServiceTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Cobrança',
                'name' => 'Rateio: tela admin (CRUD)',
                'description' => 'Cadastro, edição, exclusão e comprovante de rateio pela interface admin.',
                'source' => ['app/Http/Controllers/Admin/RateioController.php'],
                'tests' => ['tests/Feature/RateioControllerTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Cobrança',
                'name' => 'Cálculo de juros, multa e valores',
                'description' => 'Fórmula de juros/multa por atraso e divisão proporcional de valores.',
                'source' => ['app/Services/Finance.php', 'app/Support/Money.php'],
                'tests' => ['tests/Unit/FinanceTest.php', 'tests/Feature/MoneyTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Cobrança',
                'name' => 'Painel (dashboard) com indicadores',
                'description' => 'Resumo de cobranças, valores em aberto e vencidos no painel admin.',
                'source' => ['app/Http/Controllers/Admin/DashboardController.php'],
                'tests' => ['tests/Feature/DashboardTest.php'],
                'note' => 'Só renderização/autorização são testadas — os números calculados não são conferidos contra dados conhecidos.',
            ],

            // Integrações
            [
                'area' => 'Integrações',
                'name' => 'Mercado Pago: conectar/desconectar recebedor (OAuth)',
                'description' => 'Cada recebedor conecta sua própria conta Mercado Pago via OAuth.',
                'source' => ['app/Http/Controllers/Admin/ReceiverController.php', 'app/Services/MercadoPagoService.php'],
                'tests' => ['tests/Feature/MercadoPagoOAuthTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Integrações',
                'name' => 'Mercado Pago: gerar e sincronizar Pix (Orders API)',
                'description' => 'Criação de cobrança Pix e sincronismo de pagamento via Orders API.',
                'source' => ['app/Services/MercadoPagoService.php'],
                'tests' => ['tests/Feature/MercadoPagoOrdersTest.php', 'tests/Feature/DepositTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Integrações',
                'name' => 'Sincronismo automático de pagamentos Pix',
                'description' => 'Job agendado que verifica cobranças/cauções com Pix pendente, sem depender de webhook.',
                'source' => ['app/Jobs/SyncPendingPixPaymentsJob.php'],
                'tests' => ['tests/Feature/SyncPendingPixPaymentsJobTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Integrações',
                'name' => 'Webhook do Mercado Pago',
                'description' => 'Recebe notificações de pagamento processado, com verificação de assinatura.',
                'source' => ['app/Http/Controllers/Webhooks/MercadoPagoWebhookController.php'],
                'tests' => ['tests/Feature/MercadoPagoOrdersTest.php', 'tests/Feature/DepositTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Integrações',
                'name' => 'Página de status de integrações',
                'description' => 'Tela admin que resume se Mercado Pago, WhatsApp, e-mail e o cron estão configurados.',
                'source' => ['app/Http/Controllers/Admin/IntegrationController.php'],
                'tests' => ['tests/Feature/MercadoPagoOAuthTest.php'],
                'note' => 'Só o bloco do Mercado Pago é conferido; WAHA, e-mail e cron não têm teste.',
            ],
            [
                'area' => 'Integrações',
                'name' => 'Envio de WhatsApp (WAHA)',
                'description' => 'Canal de notificação que envia lembretes por WhatsApp.',
                'source' => ['app/Services/WhatsAppClient.php'],
                'tests' => ['tests/Feature/WhatsAppChannelTest.php'],
                'note' => 'Só o caminho de sucesso é testado; falha de configuração e erro HTTP não.',
            ],
            [
                'area' => 'Integrações',
                'name' => 'Busca da central de ajuda',
                'description' => 'Busca de artigos de ajuda dentro do painel admin.',
                'source' => ['app/Http/Controllers/Admin/HelpController.php', 'app/Services/HelpContent.php'],
                'tests' => ['tests/Feature/HelpControllerTest.php'],
                'note' => null,
            ],

            // Backup
            [
                'area' => 'Backup',
                'name' => 'Gerar, listar, baixar e excluir backup',
                'description' => 'Exportação do banco inteiro (sqlite) para um arquivo, gerenciamento da lista de backups.',
                'source' => ['app/Services/DatabaseBackupService.php', 'app/Http/Controllers/Admin/BackupController.php'],
                'tests' => ['tests/Feature/DatabaseBackupServiceTest.php', 'tests/Feature/BackupControllerTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Backup',
                'name' => 'Restaurar backup (com backup de segurança automático)',
                'description' => 'Substitui o banco atual pelo conteúdo de um backup, exigindo confirmação digitada.',
                'source' => ['app/Services/DatabaseBackupService.php', 'app/Http/Controllers/Admin/BackupController.php'],
                'tests' => ['tests/Feature/DatabaseBackupServiceTest.php', 'tests/Feature/BackupControllerTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Backup',
                'name' => 'Importar backup externo',
                'description' => 'Upload de um .sql.gz gerado em outro ambiente para a lista de backups, com validação de conteúdo.',
                'source' => ['app/Services/DatabaseBackupService.php', 'app/Http/Controllers/Admin/BackupController.php'],
                'tests' => ['tests/Feature/BackupControllerTest.php'],
                'note' => null,
            ],

            // Portais
            [
                'area' => 'Portais',
                'name' => 'Portal do inquilino',
                'description' => 'Contratos, cobranças (com Pix e juros/multa) e cauções do próprio inquilino.',
                'source' => ['app/Http/Controllers/Portal/TenantPortalController.php'],
                'tests' => ['tests/Feature/TenantPortalTest.php', 'tests/Feature/PaginationTest.php', 'tests/Feature/DepositTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Portais',
                'name' => 'Portal do recebedor',
                'description' => 'Contratos e cobranças vinculados ao recebedor logado, com download de recibo.',
                'source' => ['app/Http/Controllers/Portal/ReceiverPortalController.php'],
                'tests' => ['tests/Feature/ReceiverPortalTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Portais',
                'name' => 'Portal do proprietário',
                'description' => 'Imóveis e contratos do proprietário logado (pode ter mais de um imóvel).',
                'source' => ['app/Http/Controllers/Portal/OwnerPortalController.php'],
                'tests' => ['tests/Feature/OwnerPortalTest.php'],
                'note' => null,
            ],

            // Auth / Configurações
            [
                'area' => 'Autenticação',
                'name' => 'Login, limite de tentativas e logout',
                'description' => 'Fluxo de login do Fortify, incluindo bloqueio por tentativas e redirecionamento para 2FA.',
                'source' => ['routes/auth.php'],
                'tests' => ['tests/Feature/Auth/AuthenticationTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Autenticação',
                'name' => 'Cadastro público (desabilitado)',
                'description' => 'Registro de novos usuários fica desabilitado — só o admin cria contas.',
                'source' => ['routes/auth.php'],
                'tests' => ['tests/Feature/Auth/RegistrationTest.php', 'tests/Feature/RoleRedirectTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Autenticação',
                'name' => 'Recuperação e confirmação de senha',
                'description' => 'Esqueci minha senha e confirmação de senha para ações sensíveis.',
                'source' => ['routes/auth.php'],
                'tests' => ['tests/Feature/Auth/PasswordResetTest.php', 'tests/Feature/Auth/PasswordConfirmationTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Autenticação',
                'name' => 'Verificação de e-mail',
                'description' => 'Confirmação de e-mail obrigatória para acessar o sistema.',
                'source' => ['routes/auth.php'],
                'tests' => ['tests/Feature/Auth/EmailVerificationTest.php', 'tests/Feature/Auth/VerificationNotificationTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Autenticação',
                'name' => 'Redirecionamento por papel (home route)',
                'description' => 'Após login, cada papel (admin/inquilino/recebedor/proprietário) vai para seu próprio painel.',
                'source' => ['app/Models/User.php', 'app/Enums/UserRole.php'],
                'tests' => ['tests/Feature/RoleRedirectTest.php', 'tests/Feature/UserHomeRouteTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Autenticação',
                'name' => 'Perfil (nome, e-mail, exclusão de conta)',
                'description' => 'Tela de configurações de perfil do próprio usuário.',
                'source' => ['app/Http/Controllers/Settings/ProfileController.php'],
                'tests' => ['tests/Feature/Settings/ProfileUpdateTest.php'],
                'note' => null,
            ],
            [
                'area' => 'Autenticação',
                'name' => 'Segurança (senha, 2FA)',
                'description' => 'Troca de senha e tela de autenticação de dois fatores.',
                'source' => ['app/Http/Controllers/Settings/SecurityController.php'],
                'tests' => ['tests/Feature/Settings/SecurityTest.php'],
                'note' => null,
            ],

            // PWA
            [
                'area' => 'PWA',
                'name' => 'Instalar aplicativo (PWA)',
                'description' => 'Manifest, ícones, service worker offline e o botão "Instalar aplicativo" na tela de login.',
                'source' => ['resources/js/components/PwaInstallCard.vue', 'public/sw.js', 'public/manifest.webmanifest'],
                'tests' => ['tests/Feature/PwaInstallTest.php'],
                'note' => 'Cobre os arquivos estáticos (manifest, ícones, sw.js); não simula o prompt de instalação num navegador de verdade.',
            ],

            // Tema / Marca
            [
                'area' => 'Tema',
                'name' => 'Paleta de cores da marca (azul, sem teal/verde antigo)',
                'description' => 'Garante que a marca continua azul/branca e não volta ao verde-água antigo.',
                'source' => ['resources/css/app.css', 'resources/views/app.blade.php'],
                'tests' => ['tests/Feature/ThemeColorsTest.php'],
                'note' => 'Verifica o texto do CSS, não a cor realmente renderizada na tela.',
            ],
        ];
    }
}
