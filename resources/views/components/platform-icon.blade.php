@props(['platform', 'size' => 'sm'])

@php
$sizeClasses = [
    'xs' => 'h-3 w-3 text-[10px]',
    'sm' => 'h-4 w-4 text-xs',
    'md' => 'h-5 w-5 text-sm',
    'lg' => 'h-6 w-6 text-base',
    'xl' => 'h-8 w-8 text-lg',
];

$platformConfig = [
    'instagram' => ['icon' => 'fab fa-instagram', 'color' => '#E4405F', 'bg' => 'bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400'],
    'facebook' => ['icon' => 'fab fa-facebook-f', 'color' => '#1877F2', 'bg' => 'bg-blue-600'],
    'twitter' => ['icon' => 'fab fa-x-twitter', 'color' => '#000000', 'bg' => 'bg-black'],
    'linkedin' => ['icon' => 'fab fa-linkedin-in', 'color' => '#0A66C2', 'bg' => 'bg-blue-700'],
    'tiktok' => ['icon' => 'fab fa-tiktok', 'color' => '#000000', 'bg' => 'bg-black'],
    'youtube' => ['icon' => 'fab fa-youtube', 'color' => '#FF0000', 'bg' => 'bg-red-600'],
    'threads' => ['icon' => 'fab fa-threads', 'color' => '#000000', 'bg' => 'bg-black'],
    'pinterest' => ['icon' => 'fab fa-pinterest-p', 'color' => '#E60023', 'bg' => 'bg-red-600'],
    'snapchat' => ['icon' => 'fab fa-snapchat-ghost', 'color' => '#FFFC00', 'bg' => 'bg-yellow-400'],
    'reddit' => ['icon' => 'fab fa-reddit-alien', 'color' => '#FF4500', 'bg' => 'bg-orange-600'],
    'tumblr' => ['icon' => 'fab fa-tumblr', 'color' => '#35465C', 'bg' => 'bg-slate-700'],
    'google' => ['icon' => 'fab fa-google', 'color' => '#4285F4', 'bg' => 'bg-blue-500'],
    'meta' => ['icon' => 'fab fa-meta', 'color' => '#0668E1', 'bg' => 'bg-blue-600'],
];

$config = $platformConfig[strtolower($platform)] ?? ['icon' => 'fas fa-globe', 'color' => '#6B7280', 'bg' => 'bg-gray-500'];
$sizeClass = $sizeClasses[$size] ?? $sizeClasses['sm'];
@endphp

<span class="inline-flex items-center justify-center rounded-full {{ $config['bg'] }} {{ $sizeClass }} text-white"
      title="{{ ucfirst($platform) }}">
    <i class="{{ $config['icon'] }}"></i>
</span>
