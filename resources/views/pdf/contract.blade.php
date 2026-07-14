<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Contrato</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; color: #111; }
        h1 { font-size: 16px; margin-bottom: 16px; }
        .photo { margin-bottom: 16px; page-break-inside: avoid; }
        .photo img { max-width: 100%; height: auto; }
        .caption { font-size: 10px; color: #555; margin-top: 4px; }
        .contract-text { white-space: pre-wrap; margin-top: 24px; }
    </style>
</head>
<body>
    @foreach ($photos as $photo)
        <div class="photo">
            @if (file_exists(storage_path('app/'.$photo->storage_path)))
                <img src="{{ storage_path('app/'.$photo->storage_path) }}" alt="{{ $photo->caption }}">
            @endif
            @if ($photo->caption || $photo->room)
                <div class="caption">{{ $photo->room }} {{ $photo->caption }}</div>
            @endif
        </div>
    @endforeach

    <div class="contract-text">{{ $contractText }}</div>
</body>
</html>
