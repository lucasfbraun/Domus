<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Contrato</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; color: #111; }
        h1 { font-size: 16px; margin-bottom: 16px; }
        h2 { font-size: 14px; margin: 14px 0 8px; }
        h3 { font-size: 13px; margin: 12px 0 6px; }
        p { margin: 0 0 8px; }
        ul, ol { margin: 0 0 8px; padding-left: 18px; }
        li { margin: 2px 0; }
        strong, b { font-weight: bold; }
        em, i { font-style: italic; }
        /* .photo/.caption podem ser inseridos como <span> (quando a
           variavel fotos_vistoria e substituida dentro do <span
           data-template-variable> salvo pelo editor) ou como <div> (banner
           automatico no topo, fora de qualquer elemento inline) — por isso
           forcam display:block independente da tag. */
        .photo { display: block; margin-bottom: 16px; page-break-inside: avoid; }
        .photo img { display: block; max-width: 100%; height: auto; }
        .caption { display: block; font-size: 10px; color: #555; margin-top: 4px; }
        .contract-text { margin-top: 24px; }
    </style>
</head>
<body>
    {!! $photosHtml !!}

    <div class="contract-text">{!! $contractText !!}</div>
</body>
</html>
