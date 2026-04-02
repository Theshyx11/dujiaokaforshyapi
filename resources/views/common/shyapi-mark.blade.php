@php($gradientId = 'shyapi-gold-' . substr(md5((string) microtime(true) . rand()), 0, 8))
<svg class="{{ $class ?? '' }}" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="{{ $gradientId }}" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#FDE08A" />
            <stop offset="55%" stop-color="#F4C15D" />
            <stop offset="100%" stop-color="#B97818" />
        </linearGradient>
    </defs>
    <path d="M50 15V85" stroke="url(#{{ $gradientId }})" stroke-width="8" fill="none" stroke-linecap="round" />
    <path d="M35 30C35 20 65 20 65 30C65 40 35 45 35 55C35 65 65 65 65 55" stroke="url(#{{ $gradientId }})" stroke-width="8" fill="none" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M75 15L95 35L75 55Z" fill="url(#{{ $gradientId }})" />
    <path d="M75 15L95 35L75 55" stroke="url(#{{ $gradientId }})" stroke-width="4" fill="none" stroke-linejoin="round" />
</svg>
