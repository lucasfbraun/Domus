# Domus — Gestão de Imóveis e Aluguéis

Sistema de gestão imobiliária: cadastro de imóveis e contratos de locação, cobrança mensal de aluguel via Pix, vistorias, ocorrências e rateio de despesas entre imóveis.

## Language

### Pessoas e contas

**Usuário (User)**:
Conta de autenticação do sistema. Pode acumular mais de um papel ao mesmo tempo (Admin, Inquilino, Recebedor, Proprietário) — um Recebedor ou Proprietário pode compartilhar o mesmo login já usado por um Admin, em vez de ter uma conta dedicada.

**Administrador (Admin)**:
Papel de Usuário com acesso completo ao painel: cadastros, contratos, cobranças, rateios e integrações. Não é uma entidade de domínio própria, só um papel de Usuário.

**Inquilino (Tenant)**:
Pessoa que ocupa o Imóvel e é responsável pelo pagamento do aluguel de um Contrato. Pode ter um Usuário vinculado para acessar o portal do inquilino.
_Avoid_: Locatário (usado apenas no texto jurídico do contrato gerado, nunca no código ou na UI).

**Proprietário (Owner)**:
Dono de um Imóvel. Um Imóvel pode ter mais de um Proprietário. Pode ter um Usuário vinculado para acessar seu próprio portal — um login dedicado, ou o mesmo já usado por um Admin/Recebedor.
_Avoid_: Locador (usado apenas no texto jurídico do contrato gerado).

**Recebedor (Receiver)**:
Conta que recebe os pagamentos (Cobranças e Cauções) de um Contrato, podendo estar conectada ao Mercado Pago. Também é o tipo de cadastro reaproveitado para registrar uma Testemunha de assinatura — um Recebedor pode assinar como testemunha de um contrato sem ser quem recebe os pagamentos dele.
_Avoid_: Beneficiário.

**Testemunha (Witness)**:
Um Recebedor atuando como testemunha da assinatura de um Contrato específico, com sua própria data de assinatura. Não implica vínculo de pagamento com aquele contrato.

### Imóvel e contrato

**Imóvel (Property)**:
A unidade oferecida para locação (apartamento, casa, comercial ou studio). Pertence a um ou mais Proprietários.

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
Valor cobrado do Inquilino fora do ciclo mensal de aluguel, com ciclo de pagamento e status próprios, podendo ser reembolsado.
_Avoid_: Depósito (nome da classe/tabela no código; o termo de negócio usado na UI é Caução).

**Pagamento (Payment)**:
Registro de que uma Cobrança ou uma Caução foi efetivamente paga: valor líquido, taxas e método (ex. Pix).

### Vistoria e ocorrência

**Vistoria (Inspection)**:
Registro fotográfico do estado do Imóvel, anexado a um Contrato. As fotos entram automaticamente no documento gerado do contrato, antes do texto contratual — precisam existir antes da geração para aparecerem no documento assinado.

**Ocorrência (Occurrence)**:
Incidente ou solicitação (ex. manutenção) reportado durante a vigência de um Contrato, com fotos próprias e ciclo de status: aberta, em análise, resolvida ou fechada.

### Rateio

**Rateio (Rateio)**:
Despesa (ex. conta de água, taxa de condomínio) dividida entre vários Imóveis/Contratos ativos — igualmente ou por número de moradores —, gerando uma Cobrança extra em cada Contrato participante.
_Avoid_: Divisão de despesa, apuração.
