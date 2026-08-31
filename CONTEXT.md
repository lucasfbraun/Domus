# Domus — Gestão de Imóveis e Aluguéis

Sistema de gestão imobiliária: cadastro de imóveis e contratos de locação, cobrança mensal de aluguel via Pix, vistorias, ocorrências e rateio de despesas entre imóveis.

## Language

### Pessoas e contas

**Usuário (User)**:
Conta de autenticação do sistema. Pode acumular mais de um papel ao mesmo tempo (Admin, Inquilino, Recebedor, Proprietário) — um Recebedor ou Proprietário pode compartilhar o mesmo login já usado por um Admin, em vez de ter uma conta dedicada.

**Administrador (Admin)**:
Papel de Usuário com acesso completo ao painel: cadastros, contratos, cobranças, rateios e integrações. Não é uma entidade de domínio própria, só um papel de Usuário.

**Inquilino (Tenant)**:
Pessoa que ocupa o Imóvel e é responsável pelo pagamento do aluguel de um Contrato. Pode ter um Usuário vinculado para acessar o portal do inquilino. Status próprio, definido pelo admin (não muda sozinho): ativo, inativo, inadimplente ou ex-inquilino.
_Avoid_: Locatário (usado apenas no texto jurídico do contrato gerado, nunca no código ou na UI).

**Proprietário (Owner)**:
Dono de um Imóvel. Um Imóvel pode ter mais de um Proprietário. Pode ter um Usuário vinculado para acessar seu próprio portal — um login dedicado, ou o mesmo já usado por um Admin/Recebedor.
_Avoid_: Locador (usado apenas no texto jurídico do contrato gerado).

**Recebedor (Receiver)**:
Conta que recebe os pagamentos (Cobranças e Cauções) de um Contrato, podendo estar conectada ao Mercado Pago. Também é o tipo de cadastro reaproveitado para registrar uma Testemunha de assinatura — um Recebedor pode assinar como testemunha de um contrato sem ser quem recebe os pagamentos dele.
_Avoid_: Beneficiário.

**Testemunha (Witness)**:
Um Recebedor atuando como testemunha da assinatura de um Contrato específico, com sua própria data de assinatura. Não implica vínculo de pagamento com aquele contrato.

**Pré-cadastro (TenantPreRegistration)**:
Convite de auto-preenchimento enviado a um futuro Inquilino: o admin gera um link único (`PreRegistrationStatus::Pending`), a pessoa preenche nome, documento, e-mail, WhatsApp e número de moradores pelo link público (`InReview`), e o admin aceita (`Approved`, cria o Inquilino de verdade com login e Troca obrigatória de senha pendente) ou recusa (`Rejected`). Não existe Inquilino nem Usuário antes do aceite — o pré-cadastro é só a coleta de dados.
_Avoid_: Convite (usar só como verbo, "convidar um inquilino"; o registro em si é sempre "pré-cadastro").

**Troca obrigatória de senha (forced password change)**:
Estado de um Usuário (`must_change_password`) que bloqueia qualquer tela — inclusive o próprio portal — até a senha ser trocada. Ativado quando o login é criado com uma senha temporária conhecida (hoje só no aceite de um Pré-cadastro); desativado automaticamente na primeira troca de senha bem-sucedida. Não é exclusivo de Inquilino — é um estado de Usuário, reutilizável por qualquer fluxo futuro que precise entregar uma senha temporária.

### Imóvel e contrato

**Imóvel (Property)**:
A unidade oferecida para locação (apartamento, casa, comercial ou studio). Pertence a um ou mais Proprietários. Status próprio, definido pelo admin (nada no sistema muda sozinho): disponível, alugado, em manutenção ou inativo.

**Contrato (Contract)**:
Acordo de locação de um Imóvel por um Inquilino, com vigência, valor de aluguel, dia de vencimento e um Recebedor responsável pelos pagamentos. Não referencia Proprietário diretamente — chega até eles pelo Imóvel.
_Avoid_: Locação (usar apenas como adjetivo, ex. "contrato de locação").

**Status do contrato (Contract status)**:
Estado da vigência de um Contrato: rascunho, ativo, encerrando, encerrado ou cancelado. Independente do Status de assinatura — um contrato pode estar ativo mesmo sem documento assinado.

**Modelo de contrato (ContractTemplate)**:
Texto padrão e reutilizável usado para gerar o documento de um Contrato, escrito com Variáveis de modelo que são substituídas por dados reais na geração.
_Avoid_: Template (o nome em português é o termo de negócio; o código usa `ContractTemplate` como identificador).

**Variável de modelo (Template variable)**:
Token reconhecido (ex.: `inquilino_nome`, `valor_aluguel`) que o motor de geração substitui pelo dado correspondente do Contrato ao montar o documento final. Uma chave fora do catálogo reconhecido não é substituída — permanece como texto literal no documento.

**Status de assinatura (Signature status)**:
Estado do processo de assinatura do documento gerado de um Contrato: não gerado, aguardando assinatura, em análise, aprovado ou rejeitado. Distinto do Status do contrato.

### Cobrança e pagamento

**Cobrança (Charge)**:
Uma parcela mensal do aluguel de um Contrato, com vencimento e status próprio (aberta, aguardando pagamento, paga, vencida ou cancelada).
_Avoid_: Fatura, boleto.

**Caução (Deposit)**:
Valor cobrado do Inquilino fora do ciclo mensal de aluguel, com ciclo de pagamento e status próprios (pendente, aguardando pagamento, paga ou devolvida), podendo ser reembolsado.
_Avoid_: Depósito (nome da classe/tabela no código; o termo de negócio usado na UI é Caução).

**Pagamento (Payment)**:
Registro de que uma Cobrança ou uma Caução foi efetivamente paga: valor líquido, taxas e método (ex. Pix).

### Vistoria e ocorrência

**Vistoria (Inspection)**:
Registro fotográfico do estado do Imóvel, anexado a um Contrato — precisa existir antes da geração do documento para aparecer nele. O admin controla onde as fotos aparecem no PDF inserindo a variável `{{fotos_vistoria}}` (galeria com cômodo + legenda de cada foto) em qualquer ponto do modelo de contrato, ex. numa cláusula própria antes da assinatura (ver [ADR 0003](docs/adr/0003-restricted-token-substitution-for-contract-templates.md)). Modelos que não usam essa variável caem no comportamento antigo: as fotos entram automaticamente antes de todo o texto contratual.

**Ocorrência (Occurrence)**:
Incidente ou solicitação (ex. manutenção) reportado durante a vigência de um Contrato, com fotos próprias e ciclo de status: aberta, em análise, resolvida ou fechada.

### Rateio

**Rateio (Rateio)**:
Despesa (ex. conta de água, taxa de condomínio) dividida entre vários Imóveis/Contratos ativos ou encerrando — igualmente ou por número de moradores. O valor de cada Imóvel participante é somado à Cobrança do ciclo daquele Contrato (não gera uma Cobrança separada); a Cobrança guarda o quanto dela é Rateio, para poder discriminar aluguel e rateio no recibo.
_Avoid_: Divisão de despesa, apuração.

### Configuração de cobrança

**Dia de geração (Billing setting)**:
Configuração única do sistema (não por Contrato) que define a partir de qual dia do mês a geração automática mensal de Cobranças começa a rodar, para todos os Contratos ativos. Não altera o dia de vencimento de nenhum Contrato — só quando a Cobrança daquele ciclo é criada. Ver [ADR 0007](docs/adr/0007-configurable-charge-generation-day.md).
