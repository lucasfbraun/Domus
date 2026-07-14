# Domus — Gestão de Imóveis e Aluguéis

Sistema de gestão imobiliária com painel administrativo, portais para inquilinos e recebedores, cobrança de aluguéis via **Pix (Mercado Pago Orders API)** e integrações opcionais (e-mail, WhatsApp).

**Stack:** Laravel 13 · Inertia.js v3 · Vue 3 · Tailwind CSS v4 · Fortify · Spatie Media Library & Permission

---

## Índice

- [Requisitos](#requisitos)
- [Início rápido](#início-rápido)
- [Setup manual](#setup-manual)
- [Desenvolvimento](#desenvolvimento)
- [Dados de demonstração](#dados-de-demonstração)
- [Mercado Pago (Pix)](#mercado-pago-pix)
  - [Modo local com token de teste](#modo-local-com-token-de-teste)
  - [OAuth por recebedor (produção)](#oauth-por-recebedor-produção)
  - [Webhooks](#webhooks)
  - [Testar pagamento Pix no sandbox](#testar-pagamento-pix-no-sandbox)
- [Integrações opcionais](#integrações-opcionais)
- [Agendamentos e filas](#agendamentos-e-filas)
- [Testes](#testes)
- [Papéis de usuário](#papéis-de-usuário)
- [Variáveis de ambiente](#variáveis-de-ambiente)

---

## Requisitos

| Ferramenta | Versão mínima |
|------------|---------------|
| PHP        | 8.3+ (recomendado 8.5) |
| Composer   | 2.x           |
| Node.js    | 20+           |
| npm        | 10+           |

**Extensões PHP:** `pdo`, `sqlite3` (padrão), `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd` ou `imagick` (conversões WebP da Media Library).

O projeto usa **SQLite** por padrão. MySQL/PostgreSQL também funcionam — ajuste `DB_*` no `.env`.

---

## Início rápido

Com PHP, Composer e Node instalados:

```bash
git clone <url-do-repositorio> property-manager
cd property-manager

composer setup
```

O script `composer setup` executa:

1. `composer install`
2. Copia `.env.example` → `.env` (se não existir)
3. `php artisan key:generate`
4. `php artisan migrate`
5. `npm install`
6. `npm run build`

Depois, popule o banco com dados de demonstração:

```bash
php artisan db:seed
```

Inicie o ambiente de desenvolvimento:

```bash
composer dev
```

Acesse [http://localhost:8000](http://localhost:8000) (ou a porta exibida no terminal).

---

## Setup manual

Se preferir configurar passo a passo:

```bash
# 1. Dependências PHP
composer install

# 2. Ambiente
cp .env.example .env
php artisan key:generate

# 3. Banco (SQLite — cria o arquivo automaticamente na migrate)
touch database/database.sqlite   # só se ainda não existir
php artisan migrate

# 4. Link simbólico para mídia pública (fotos de imóveis, vistoria, etc.)
php artisan storage:link

# 5. Frontend
npm install
npm run build        # produção
# ou npm run dev     # hot reload (junto com php artisan serve)

# 6. Seed (opcional, mas recomendado para explorar o sistema)
php artisan db:seed
```

### Configuração do banco de dados

#### SQLite (padrão)

O projeto já vem configurado para SQLite — ideal para desenvolvimento local sem instalar um servidor de banco.

No `.env`:

```env
DB_CONNECTION=sqlite
# DB_DATABASE=   # opcional; padrão: database/database.sqlite
```

Crie o arquivo do banco (se ainda não existir) e rode as migrations:

```bash
touch database/database.sqlite
php artisan migrate
```

> A extensão PHP `pdo_sqlite` precisa estar habilitada (`php -m | grep pdo_sqlite`).

#### MySQL / PostgreSQL

No `.env`, substitua a configuração SQLite:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=domus
DB_USERNAME=root
DB_PASSWORD=
```

Para PostgreSQL, use `DB_CONNECTION=pgsql` e `DB_PORT=5432`.

Crie o banco vazio e rode `php artisan migrate`.

---

## Desenvolvimento

```bash
composer dev
```

Sobe em paralelo (via `php artisan dev`):

- Servidor PHP (`php artisan serve`)
- Vite (`npm run dev`)
- Queue worker (`php artisan queue:listen`)
- Logs em tempo real (`php artisan pail`)

**Alternativa manual** (dois terminais):

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

> Se alterações no frontend não aparecerem, confira se o Vite está rodando ou rode `npm run build`.

---

## Dados de demonstração

O `DatabaseSeeder` cria roles, permissões e um cenário completo via `DemoSeeder`.

| Papel      | E-mail                  | Senha      |
|------------|-------------------------|------------|
| Admin      | `admin@example.com`     | `password` |
| Inquilino  | `tenant@example.com`    | `password` |
| Recebedor  | `receiver@example.com`| `password` |

O cenário inclui proprietário, imóveis, contrato ativo, cobranças (paga, vencida, em aberto) e tentativa de gerar Pix sandbox na cobrança do mês atual.

**Contas de acesso após login:**

| Papel     | Rota principal        |
|-----------|-----------------------|
| Admin     | `/dashboard`          |
| Inquilino | `/inquilino`          |
| Recebedor | `/recebedor`          |

---

## Mercado Pago (Pix)

O sistema usa a **Orders API** do Mercado Pago para gerar cobranças Pix. Cada cobrança é vinculada a um **recebedor** (conta que recebe o pagamento).

Há dois modos de operação:

| Modo | Quando usar | Variáveis |
|------|-------------|-----------|
| **Atalho local** | Desenvolvimento / testes rápidos | `MP_ACCESS_TOKEN` |
| **OAuth por recebedor** | Produção (cada recebedor conecta a própria conta) | `MP_CLIENT_ID` + `MP_CLIENT_SECRET` |

O status das integrações aparece em **Admin → Integrações** (`/integracoes`).

### Criar aplicação no Mercado Pago

1. Acesse [Mercado Pago Developers](https://www.mercadopago.com.br/developers/panel/app).
2. Crie uma aplicação (tipo **Checkout / Pagamentos online**).
3. Anote:
   - **Client ID** → `MP_CLIENT_ID`
   - **Client Secret** → `MP_CLIENT_SECRET`
4. Em **Credenciais de producao**, copie o **Access Token** (`APP_USR-...`) → `MP_ACCESS_TOKEN` (atalho local; a Orders API nao aceita `TEST-`).

### Modo local com token de producao

A Orders API **nao aceita** Access Tokens `TEST-`. Use o Access Token de **producao** (`APP_USR-`) mesmo em desenvolvimento. Para testar pagamentos sem cobrar de verdade, use [usuarios de teste](https://www.mercadopago.com.br/developers/pt/docs/your-integrations/test/accounts) do Mercado Pago.

Ideal para rodar o projeto sem configurar OAuth. O token da plataforma substitui a conta do recebedor **apenas** quando `APP_ENV=local` ou `testing`.

```env
APP_ENV=local
APP_URL=http://localhost:8000

# Credenciais da aplicacao (necessarias para OAuth em producao; recomendadas tambem no local)
MP_CLIENT_ID=seu_client_id
MP_CLIENT_SECRET=seu_client_secret

# Atalho local — Access Token de PRODUCAO do painel MP (APP_USR-..., nao TEST-)
MP_ACCESS_TOKEN=APP_USR-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx

# false = OAuth devolve token de producao (obrigatorio para Orders API / Pix)
MP_SANDBOX_CONNECT=false
```

Depois de configurar o `.env`:

```bash
php artisan config:clear
php artisan db:seed   # ou migrate:fresh --seed para recriar o Pix demo
```

**Limitacoes do sandbox Orders API:**

- Valor maximo por order: **R$ 1.000,00**
- O e-mail do **inquilino** (payer na API) deve usar dominio `@testuser.com` no sandbox  
  O seed demo ja usa `tenant.demo@testuser.com` no cadastro do inquilino (login continua `tenant@example.com`)
- O aluguel demo e **R$ 900** para caber dentro do limite mesmo com multa/juros
- Nao use `MP_SANDBOX_CONNECT=true` nem tokens `TEST-` — a API responde `invalid_credentials`
### OAuth por recebedor (produção)

Em produção, **não** use `MP_ACCESS_TOKEN` como atalho — cada recebedor conecta a própria conta Mercado Pago.

```env
APP_ENV=production
APP_URL=https://seu-dominio.com.br
APP_BASE_URL=https://seu-dominio.com.br

MP_CLIENT_ID=seu_client_id
MP_CLIENT_SECRET=seu_client_secret
MP_SANDBOX_CONNECT=false

# Não defina MP_ACCESS_TOKEN em produção
```

**Passo a passo no admin:**

1. **Cadastros → Recebedores** → editar o recebedor
2. Clicar em **Conectar Mercado Pago**
3. Autorizar no site do MP (redirect OAuth)
4. O callback salva `access_token`, `refresh_token` e `user_id` no recebedor

**Redirect URI** a cadastrar no painel do Mercado Pago:

```
https://seu-dominio.com.br/receivers/mercadopago/callback
```

Em local com tunnel (ex.: ngrok, Cloudflare Tunnel):

```
https://seu-tunnel.example.com/receivers/mercadopago/callback
```

Atualize `APP_URL` e `APP_BASE_URL` para a URL pública do tunnel.

### Webhooks

Pagamentos Pix são confirmados automaticamente via webhook `order.processed`.

**URL do webhook** (POST):

```
https://seu-dominio.com.br/webhooks/mercadopago
```

Configure no painel MP em **Webhooks → Orders** (ou notificações da aplicação).

**Assinatura (recomendado em produção):**

```env
MP_WEBHOOK_SECRET=sua_chave_secreta_do_webhook
```

Sem `MP_WEBHOOK_SECRET`, o endpoint aceita notificações sem validar assinatura (útil só em desenvolvimento).

**Desenvolvimento local com tunnel:**

1. Exponha a aplicação (ex.: `cloudflared tunnel --url http://localhost:8000`)
2. Defina `APP_URL` e `APP_BASE_URL` com a URL do tunnel
3. Cadastre `https://<tunnel>/webhooks/mercadopago` no MP
4. Rode `php artisan config:clear`

A rota `POST /webhooks/mercadopago` está **isenta de CSRF** (configurado em `bootstrap/app.php`).

**Confirmação manual:** se o webhook não chegar, admin ou inquilino podem usar **Sincronizar pagamento** na cobrança (`POST /charges/{id}/sync`).

### Testar pagamento Pix no sandbox

1. Configure `MP_ACCESS_TOKEN` (teste) e rode o seed
2. Login como inquilino (`tenant@example.com`)
3. Abra a cobrança em aberto e gere/visualize o QR Code Pix
4. No [painel de testes do MP](https://www.mercadopago.com.br/developers/panel/test-cards), simule o pagamento da order
5. Confirme via webhook ou botão **Sincronizar pagamento**

Fluxo resumido:

```
Inquilino gera Pix → MP cria Order → Payer paga → Webhook order.processed → Cobrança marcada como paga
```

---

## Integrações opcionais

### E-mail

Padrão: `MAIL_MAILER=log` (mensagens vão para `storage/logs`).

Para SMTP real:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.exemplo.com
MAIL_PORT=587
MAIL_USERNAME=usuario
MAIL_PASSWORD=senha
MAIL_FROM_ADDRESS=noreply@exemplo.com
MAIL_FROM_NAME="${APP_NAME}"
```

### WhatsApp (WAHA)

Lembretes e confirmações podem ser enviados via [WAHA](https://github.com/devlikeapro/waha):

```env
WAHA_BASE_URL=http://localhost:3000
WAHA_API_KEY=sua_api_key
WAHA_SESSION=default
```

Sem WAHA configurado, mensagens são apenas logadas (não quebram o fluxo).

---

## Agendamentos e filas

Tarefas agendadas (`routes/console.php`):

| Horário (America/Sao_Paulo) | Job | Função |
|-----------------------------|-----|--------|
| 09:00 | `GenerateMonthlyChargesJob` | Marca cobranças vencidas + gera cobranças mensais (5 dias antes do vencimento) |
| 10:00 | `RunReminderSweepJob` | Envia lembretes de cobrança |

**Em desenvolvimento**, `composer dev` já sobe o queue worker.

**Em produção**, configure o cron do servidor:

```cron
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

E mantenha um worker de fila rodando:

```bash
php artisan queue:work --tries=3
```

O `.env` usa `QUEUE_CONNECTION=database` — as tabelas `jobs` e `failed_jobs` são criadas na migrate.

---

## Testes

```bash
# Suite completa (Pint + PHPStan + testes)
composer test

# Apenas testes Pest
php artisan test --compact

# Filtrar por nome
php artisan test --compact --filter=MercadoPago
```

Testes de Mercado Pago usam `Http::fake()` — não chamam a API real.

---

## Papéis de usuário

| Papel | Acesso |
|-------|--------|
| **Admin** | Painel completo: cadastros, contratos, cobranças, rateios, integrações |
| **Inquilino** | Portal: cobranças, Pix, contrato, ocorrências |
| **Recebedor** | Portal de leitura: cobranças e contratos vinculados |
| **Proprietário** | Cadastro administrativo (sem login próprio) |

---

## Variáveis de ambiente

Referência completa em `.env.example`. Principais:

| Variável | Descrição |
|----------|-----------|
| `APP_NAME` | Nome exibido na UI (padrão: Domus) |
| `APP_URL` | URL base da aplicação |
| `APP_BASE_URL` | URL pública (webhooks, links externos); padrão = `APP_URL` |
| `DB_*` | Conexão do banco |
| `MP_CLIENT_ID` | Client ID da aplicação MP |
| `MP_CLIENT_SECRET` | Client Secret da aplicação MP |
| `MP_ACCESS_TOKEN` | Atalho local com token de teste (não usar em produção) |
| `MP_PUBLIC_KEY` | Chave pública (reservado para futuras integrações frontend) |
| `MP_WEBHOOK_SECRET` | Segredo para validar assinatura dos webhooks |
| `MP_SANDBOX_CONNECT` | `true` = OAuth gera tokens de teste |
| `WAHA_*` | Integração WhatsApp |
| `MAIL_*` | Envio de e-mail |
| `MEDIA_DISK` | Disco da Media Library (padrão: `public`) |

---

## Comandos úteis

```bash
composer setup          # Instalação inicial completa
composer dev            # Ambiente de desenvolvimento
composer test           # Lint + análise estática + testes
php artisan migrate:fresh --seed   # Recria banco + demo
php artisan storage:link           # Mídia pública
php artisan config:clear           # Após alterar .env
php artisan route:list             # Lista rotas
vendor/bin/pint --dirty            # Formata PHP alterado
```

---

## Licença

MIT
