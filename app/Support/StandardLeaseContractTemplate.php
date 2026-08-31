<?php

namespace App\Support;

class StandardLeaseContractTemplate
{
    public const NAME = 'Contrato de Locação Residencial (Padrão)';

    /**
     * HTML já no formato canônico produzido por ContractTemplateVariables::sanitizeHtml()
     * (spans com data-template-variable), para que o seed não precise de normalização
     * e o admin veja os chips corretamente ao abrir o modelo no editor.
     */
    public static function content(): string
    {
        return <<<'HTML'
<h1>CONTRATO DE LOCAÇÃO DE IMÓVEL RESIDENCIAL</h1>
<p>Pelo presente instrumento particular, de um lado <span data-template-variable="proprietario_nome">{{proprietario_nome}}</span>, portador(a) do(s) documento(s) nº <span data-template-variable="proprietario_documento">{{proprietario_documento}}</span>, e-mail <span data-template-variable="proprietario_email">{{proprietario_email}}</span>, telefone <span data-template-variable="proprietario_telefone">{{proprietario_telefone}}</span>, doravante denominado(a) <strong>LOCADOR(A)</strong>; e de outro lado <span data-template-variable="inquilino_nome">{{inquilino_nome}}</span>, portador(a) do documento nº <span data-template-variable="inquilino_documento">{{inquilino_documento}}</span>, e-mail <span data-template-variable="inquilino_email">{{inquilino_email}}</span>, WhatsApp <span data-template-variable="inquilino_whatsapp">{{inquilino_whatsapp}}</span>, doravante denominado(a) <strong>LOCATÁRIO(A)</strong>, têm entre si justo e contratado o presente Contrato de Locação de Imóvel Residencial, mediante as cláusulas e condições seguintes.</p>
<h2>Cláusula 1ª — Do objeto</h2>
<p>O LOCADOR dá em locação ao LOCATÁRIO o imóvel do tipo <span data-template-variable="imovel_tipo">{{imovel_tipo}}</span>, denominado <span data-template-variable="imovel_nome">{{imovel_nome}}</span>, localizado em <span data-template-variable="imovel_endereco">{{imovel_endereco}}</span>, destinado exclusivamente para fins residenciais.</p>
<h2>Cláusula 2ª — Do prazo</h2>
<p>O prazo da locação vigora de <span data-template-variable="data_inicio">{{data_inicio}}</span> até <span data-template-variable="data_fim">{{data_fim}}</span>, podendo ser renovado mediante acordo expresso entre as partes.</p>
<h2>Cláusula 3ª — Do aluguel e forma de pagamento</h2>
<p>O valor mensal do aluguel é de <span data-template-variable="valor_aluguel">{{valor_aluguel}}</span>, com vencimento todo dia <span data-template-variable="dia_vencimento">{{dia_vencimento}}</span> de cada mês.</p>
<p>Os pagamentos deverão ser realizados em favor de <span data-template-variable="recebedor_nome">{{recebedor_nome}}</span>, documento nº <span data-template-variable="recebedor_documento">{{recebedor_documento}}</span>, responsável pelo recebimento dos valores devidos por este contrato.</p>
<h2>Cláusula 4ª — Do atraso no pagamento</h2>
<p>O não pagamento do aluguel até a data de vencimento sujeitará o LOCATÁRIO à multa de <span data-template-variable="multa_percentual">{{multa_percentual}}</span>% sobre o valor devido, acrescida de juros de mora de <span data-template-variable="juros_percentual">{{juros_percentual}}</span>% ao mês, após decorrido o prazo de carência de <span data-template-variable="carencia_dias">{{carencia_dias}}</span> dias.</p>
<h2>Cláusula 5ª — Do reajuste</h2>
<p>O valor do aluguel será reajustado anualmente, na menor periodicidade permitida em lei, pelo índice oficial aplicável ou por outro que vier a substituí-lo.</p>
<h2>Cláusula 6ª — Das obrigações do LOCATÁRIO</h2>
<ul>
<li>Pagar pontualmente o aluguel e os encargos que lhe couberem;</li>
<li>Usar o imóvel exclusivamente para fins residenciais, conservando-o como se seu fosse;</li>
<li>Não realizar modificações no imóvel sem autorização prévia e por escrito do LOCADOR;</li>
<li>Permitir a vistoria do imóvel pelo LOCADOR, mediante aviso prévio;</li>
<li>Restituir o imóvel, ao final da locação, nas mesmas condições em que o recebeu, ressalvado o desgaste natural de uso.</li>
</ul>
<h2>Cláusula 7ª — Das obrigações do LOCADOR</h2>
<ul>
<li>Entregar o imóvel em condições de uso para os fins a que se destina;</li>
<li>Garantir, durante o prazo da locação, o uso pacífico do imóvel;</li>
<li>Responder pelos vícios ou defeitos anteriores à locação.</li>
</ul>
<h2>Cláusula 8ª — Da rescisão</h2>
<p>O descumprimento de qualquer cláusula deste contrato por qualquer das partes poderá ensejar a sua rescisão, sem prejuízo das penalidades e do ressarcimento de perdas e danos cabíveis, nos termos da legislação aplicável.</p>
<h2>Cláusula 9ª — Do foro</h2>
<p>Fica eleito o foro da comarca de localização do imóvel para dirimir quaisquer dúvidas ou litígios oriundos deste contrato, com renúncia expressa a qualquer outro, por mais privilegiado que seja.</p>
<h2>Cláusula 10ª — Da vistoria de entrada</h2>
<p>Segue registro fotográfico do estado do imóvel no ato da vistoria de entrada, parte integrante deste contrato:</p>
<p><span data-template-variable="fotos_vistoria">{{fotos_vistoria}}</span></p>
<p>E, por estarem justos e contratados, as partes assinam o presente instrumento em duas vias de igual teor.</p>
<p>Documento gerado em <span data-template-variable="data_geracao">{{data_geracao}}</span>.</p>
<p>_____________________________________<br>LOCADOR(A): <span data-template-variable="proprietario_nome">{{proprietario_nome}}</span></p>
<p>_____________________________________<br>LOCATÁRIO(A): <span data-template-variable="inquilino_nome">{{inquilino_nome}}</span></p>
<p>_____________________________________<br>TESTEMUNHA</p>
<p>_____________________________________<br>TESTEMUNHA</p>
HTML;
    }
}
