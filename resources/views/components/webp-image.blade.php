@props(['src', 'alt' => '', 'class' => ''])

@php
    // Convertir la ruta PNG a WebP
    $webpSrc = str_replace('.png', '.webp', $src);
    // Asegurar que comience con asset() si no lo tiene
    $pngPath = str_starts_with($src, 'http') ? $src : asset($src);
    $webpPath = str_starts_with($webpSrc, 'http') ? $webpSrc : asset($webpSrc);
@endphp

<picture>
    <source srcset="{{ $webpPath }}" type="image/webp">
    <source srcset="{{ $pngPath }}" type="image/png">
    <img src="{{ $pngPath }}" alt="{{ $alt }}" class="{{ $class }}" {{ $attributes }}>
</picture>
