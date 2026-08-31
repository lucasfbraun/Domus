<?php

namespace App\Support;

class ContractTemplateVariables
{
    /**
     * Chaves cujo valor substituido e HTML confiavel, gerado pelo servidor
     * (nunca a partir de conteudo autorado pelo admin) — ver ADR 0003. Ao
     * contrario das demais variaveis (texto simples, sempre escapado),
     * essas tem a substituicao inserida sem escape e sobrevivem ao
     * strip_tags de render em ContractDocumentService::renderTemplate().
     *
     * @var list<string>
     */
    private const array HTML_KEYS = ['fotos_vistoria'];

    /**
     * @return list<array{key: string, label: string, group: string}>
     */
    public static function catalog(): array
    {
        return [
            ['key' => 'inquilino_nome', 'label' => 'Nome', 'group' => 'Inquilino'],
            ['key' => 'inquilino_documento', 'label' => 'Documento', 'group' => 'Inquilino'],
            ['key' => 'inquilino_email', 'label' => 'E-mail', 'group' => 'Inquilino'],
            ['key' => 'inquilino_whatsapp', 'label' => 'WhatsApp', 'group' => 'Inquilino'],
            ['key' => 'imovel_nome', 'label' => 'Nome', 'group' => 'Imóvel'],
            ['key' => 'imovel_endereco', 'label' => 'Endereço', 'group' => 'Imóvel'],
            ['key' => 'imovel_tipo', 'label' => 'Tipo', 'group' => 'Imóvel'],
            ['key' => 'recebedor_nome', 'label' => 'Nome', 'group' => 'Recebedor'],
            ['key' => 'recebedor_documento', 'label' => 'Documento', 'group' => 'Recebedor'],
            ['key' => 'proprietario_nome', 'label' => 'Nome (todos, separados por vírgula)', 'group' => 'Proprietário'],
            ['key' => 'proprietario_documento', 'label' => 'Documento (todos, separados por vírgula)', 'group' => 'Proprietário'],
            ['key' => 'proprietario_email', 'label' => 'E-mail (todos, separados por vírgula)', 'group' => 'Proprietário'],
            ['key' => 'proprietario_telefone', 'label' => 'Telefone (todos, separados por vírgula)', 'group' => 'Proprietário'],
            ['key' => 'valor_aluguel', 'label' => 'Valor do aluguel', 'group' => 'Contrato'],
            ['key' => 'dia_vencimento', 'label' => 'Dia de vencimento', 'group' => 'Contrato'],
            ['key' => 'data_inicio', 'label' => 'Data de início', 'group' => 'Contrato'],
            ['key' => 'data_fim', 'label' => 'Data de término', 'group' => 'Contrato'],
            ['key' => 'multa_percentual', 'label' => 'Multa (%)', 'group' => 'Contrato'],
            ['key' => 'juros_percentual', 'label' => 'Juros (%)', 'group' => 'Contrato'],
            ['key' => 'carencia_dias', 'label' => 'Carência (dias)', 'group' => 'Contrato'],
            ['key' => 'data_geracao', 'label' => 'Data de geração', 'group' => 'Sistema'],
            ['key' => 'fotos_vistoria', 'label' => 'Fotos da vistoria (galeria com legendas)', 'group' => 'Vistoria'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_column(self::catalog(), 'key');
    }

    /**
     * @return list<string>
     */
    public static function htmlKeys(): array
    {
        return self::HTML_KEYS;
    }

    public static function isHtmlKey(string $key): bool
    {
        return in_array($key, self::HTML_KEYS, true);
    }

    /**
     * Se o token {{$key}} aparece literalmente no conteudo do modelo —
     * usado para decidir se um fallback automatico (ex. galeria de fotos
     * no topo do PDF) deve ceder lugar ao ponto em que o admin colocou a
     * variavel manualmente, evitando duplicar o conteudo.
     */
    public static function isReferenced(string $content, string $key): bool
    {
        return str_contains($content, '{{'.$key.'}}');
    }

    public static function isBlank(string $html): bool
    {
        $text = trim(html_entity_decode(strip_tags($html)));

        return $text === '' && ! str_contains($html, 'data-template-variable');
    }

    public static function sanitizeHtml(string $html): string
    {
        $keys = self::keys();
        $tokens = [];
        $index = 0;

        $withTokens = preg_replace_callback(
            '/<span[^>]*\bdata-template-variable=["\'](\w+)["\'][^>]*>.*?<\/span>|\{\{\s*(\w+)\s*\}\}/us',
            function (array $matches) use (&$tokens, &$index, $keys): string {
                $key = ($matches[1] ?? '') !== '' ? $matches[1] : ($matches[2] ?? '');

                if ($key === '' || ! in_array($key, $keys, true)) {
                    return $matches[0];
                }

                $token = '___TMPL_VAR_'.$index.'___';
                $tokens[$token] = $key;
                $index++;

                return $token;
            },
            $html,
        ) ?? $html;

        $cleaned = strip_tags(
            $withTokens,
            '<p><br><strong><b><em><i><u><h1><h2><h3><ul><ol><li>',
        );

        foreach ($tokens as $token => $key) {
            $cleaned = str_replace(
                $token,
                '<span data-template-variable="'.$key.'">{{'.$key.'}}</span>',
                $cleaned,
            );
        }

        return trim($cleaned);
    }
}
