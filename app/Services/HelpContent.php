<?php

namespace App\Services;

class HelpContent
{
    /**
     * @var list<array{id: string, title: string, keywords: list<string>, answer: string}>
     */
    public const ENTRIES = [
        [
            'id' => 'papeis',
            'title' => 'Quais sao os papeis de usuario (admin, inquilino, recebedor, proprietario)?',
            'keywords' => ['papel', 'perfil', 'acesso', 'admin', 'inquilino', 'recebedor', 'proprietario'],
            'answer' => 'Admin: acesso total ao painel. Inquilino: portal proprio com cobrancas, Pix e contrato. Recebedor: portal de leitura das cobrancas e contratos vinculados. Proprietario: cadastro administrativo sem login.',
        ],
        [
            'id' => 'gerar-cobranca',
            'title' => 'Como e gerada a cobranca mensal?',
            'keywords' => ['gerar cobranca', 'cobranca automatica', 'cron'],
            'answer' => 'Manualmente pelo botao Gerar cobranca ou automaticamente via agendamento diario, 5 dias antes do vencimento. O sistema nao duplica cobrancas do mesmo mes.',
        ],
        [
            'id' => 'rateio',
            'title' => 'Como fazer um rateio de despesa entre imoveis?',
            'keywords' => ['rateio', 'agua', 'condominio', 'gas', 'iptu'],
            'answer' => 'Informe categoria, mes de referencia, valor total e imoveis participantes. O valor pode ser dividido igualmente ou proporcional ao numero de moradores.',
        ],
        [
            'id' => 'assinatura',
            'title' => 'Como funciona a assinatura do contrato?',
            'keywords' => ['assinatura', 'testemunha', 'proprietario', 'upload'],
            'answer' => 'O inquilino assina por ultimo. Antes disso, testemunhas e proprietario (se houver) devem ser marcados como assinados pelo admin.',
        ],
        [
            'id' => 'pix',
            'title' => 'Como funciona a cobranca via Pix?',
            'keywords' => ['pix', 'mercado pago', 'qr code'],
            'answer' => 'Cada cobranca gera uma order Pix (Orders API) na conta Mercado Pago do recebedor via OAuth. O admin conecta cada recebedor em Cadastros > Recebedores. Em local/testes, MP_ACCESS_TOKEN pode substituir o OAuth. Pagamentos sao confirmados via webhook order.processed ou verificacao manual.',
        ],
    ];

    /**
     * @return list<array{id: string, title: string, keywords: list<string>, answer: string, score: int}>
     */
    public function search(string $query, int $limit = 3): array
    {
        $queryTokens = $this->tokenize($query);

        if ($queryTokens === []) {
            return [];
        }

        $scored = [];

        foreach (self::ENTRIES as $entry) {
            $score = 0;

            foreach ($queryTokens as $token) {
                foreach ($entry['keywords'] as $keyword) {
                    if (str_contains($this->normalize($keyword), $token)) {
                        $score += 4;
                    }
                }

                if (str_contains($this->normalize($entry['title']), $token)) {
                    $score += 3;
                }

                if (str_contains($this->normalize($entry['answer']), $token)) {
                    $score += 1;
                }
            }

            if ($score > 0) {
                $scored[] = [...$entry, 'score' => $score];
            }
        }

        usort($scored, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }

    /**
     * @return list<string>
     */
    private function tokenize(string $text): array
    {
        $stopwords = ['a', 'o', 'de', 'do', 'da', 'e', 'para', 'como', 'qual', 'quais'];

        return array_values(array_filter(
            preg_split('/\s+/', $this->normalize($text)) ?: [],
            fn (string $word) => strlen($word) > 1 && ! in_array($word, $stopwords, true),
        ));
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;

        return preg_replace('/[^a-z0-9\s]/', ' ', $text) ?? $text;
    }
}
