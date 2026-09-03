<?php
/**
 * icons.php — Bibliothèque d'icônes SVG en ligne (aucune dépendance externe).
 * Usage : icon('cart'), icon('heart', 'my-class')
 */
function icon(string $name, string $class = ''): string
{
    $paths = [
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'cart'     => '<circle cx="9" cy="21" r="1.6"/><circle cx="18" cy="21" r="1.6"/><path d="M2.5 3h2.2l2.3 12.4a2 2 0 0 0 2 1.6h8.4a2 2 0 0 0 2-1.6L22 7H6"/>',
        'heart'    => '<path d="M20.8 5.6a5.4 5.4 0 0 0-7.7 0L12 6.7l-1.1-1.1a5.4 5.4 0 1 0-7.7 7.7L12 22l8.8-8.7a5.4 5.4 0 0 0 0-7.7z"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
        'menu'     => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'close'    => '<path d="M6 6l12 12M18 6L6 18"/>',
        'star'     => '<path d="M12 2l3 6.5 7 .8-5.2 4.7 1.5 7L12 17.8 5.2 21l1.5-7L1.5 9.3l7-.8z"/>',
        'truck'    => '<rect x="1" y="6" width="13" height="11" rx="1"/><path d="M14 9h4l3 3v5h-7"/><circle cx="6" cy="18" r="1.8"/><circle cx="18" cy="18" r="1.8"/>',
        'shield'   => '<path d="M12 2l8 3v6c0 5-3.4 8.6-8 11-4.6-2.4-8-6-8-11V5z"/>',
        'refresh'  => '<path d="M21 12a9 9 0 1 1-2.6-6.3M21 4v5h-5"/>',
        'gift'     => '<rect x="3" y="8" width="18" height="13" rx="1"/><path d="M3 12h18M12 8v13M12 8s-1-5-4-5-2 5 4 5zm0 0s1-5 4-5 2 5-4 5z"/>',
        'chat'     => '<path d="M21 12a8 8 0 0 1-11.5 7.2L3 21l1.8-6.5A8 8 0 1 1 21 12z"/>',
        'phone'    => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
        'mail'     => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 6l10 7L22 6"/>',
        'pin'      => '<path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check'    => '<path d="M20 6L9 17l-5-5"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'minus'    => '<path d="M5 12h14"/>',
        'eye'      => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'compare'  => '<path d="M9 3v18M15 3v18M3 9h6M15 9h6M3 15h6M15 15h6"/>',
        'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'chevron'  => '<path d="M9 6l6 6-6 6"/>',
        'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'box'      => '<path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
        'tag'      => '<path d="M20 12l-8 8-9-9V3h8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'sun'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>',
        'moon'     => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
        'leaf'     => '<path d="M11 20A7 7 0 0 1 4 13c0-6 8-10 16-10 0 8-4 16-9 17z"/><path d="M4 20c4-4 7-6 12-8"/>',
        'sparkles' => '<path d="M12 3l1.8 4.2L18 9l-4.2 1.8L12 15l-1.8-4.2L6 9l4.2-1.8z"/><path d="M18 14l.9 2.1L21 17l-2.1.9L18 20l-.9-2.1L15 17l2.1-.9z"/>',
        'instagram'=> '<rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/>',
        'facebook' => '<path d="M15 3h-3a5 5 0 0 0-5 5v3H4v4h3v6h4v-6h3l1-4h-4V8a1 1 0 0 1 1-1h3z"/>',
        'tiktok'   => '<path d="M15 3v9.5a3.5 3.5 0 1 1-3.5-3.5M15 3a5 5 0 0 0 5 5"/>',
        'wallet'   => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M16 12h.01M2 9h20"/>',
        'award'    => '<circle cx="12" cy="8" r="6"/><path d="M8.2 13L7 22l5-3 5 3-1.2-9"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'trash'    => '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>',
        'edit'     => '<path d="M11 4H4v16h16v-7M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/>',
        'flower'   => '<circle cx="12" cy="12" r="3"/><path d="M12 5a3 3 0 0 1 0 4 3 3 0 0 1 0-4zM12 15a3 3 0 0 1 0 4 3 3 0 0 1 0-4zM5 12a3 3 0 0 1 4 0 3 3 0 0 1-4 0zM15 12a3 3 0 0 1 4 0 3 3 0 0 1-4 0z"/>',
        'book'     => '<path d="M4 5a2 2 0 0 1 2-2h13v18H6a2 2 0 0 1-2-2z"/><path d="M19 3v18M8 7h7M8 11h7"/>',
        'book-open'=> '<path d="M12 6.5C10.5 5 7 4.5 3 5v14c4-.5 7.5 0 9 1.5 1.5-1.5 5-2 9-1.5V5c-4-.5-7.5 0-9 1.5z"/><path d="M12 6.5v14"/>',
        'bookmark' => '<path d="M6 3h12v18l-6-4-6 4z"/>',
        'lock'     => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'unlock'   => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 7.5-2"/>',
        'download' => '<path d="M12 3v12M7 10l5 5 5-5M5 21h14"/>',
        'upload'   => '<path d="M12 21V9M7 14l5-5 5 5M5 3h14"/>',
        'feather'  => '<path d="M20 4C13 4 8 9 5 16l-2 5 5-2c7-3 12-8 12-15zM8 16l6-6"/>',
        'presentation' => '<rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M12 16v4M8.5 21l3.5-1.6 3.5 1.6"/><path d="M7.5 12.5l2.6-3 2.2 2.2 3.2-3.7"/>',
        'slides'   => '<rect x="2.5" y="5" width="15" height="11" rx="1.5"/><path d="M21.5 8v9a1.5 1.5 0 0 1-1.5 1.5H7"/>',
        'file'     => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/>',
        'receipt'  => '<path d="M5 3l1.5 1.5L8 3l1.5 1.5L11 3l1.5 1.5L14 3l1.5 1.5L17 3v18l-1.5-1.5L14 21l-1.5-1.5L11 21l-1.5-1.5L8 21l-1.5-1.5L5 21z"/><path d="M8 8h6M8 12h6"/>',
        'pages'    => '<path d="M8 3h9a2 2 0 0 1 2 2v14M6 7h9a2 2 0 0 1 2 2v12H6a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z"/>',
        'copy'     => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'library'  => '<path d="M3 20h18"/><path d="M5 20V6.5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1V20"/><path d="M11 20V4.5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1V20"/><path d="M16.6 7.4l2.6-.7a1 1 0 0 1 1.2.7L22 20"/>',
    ];
    $p = $paths[$name] ?? $paths['sparkles'];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function icon_filled(string $name, string $class = ''): string
{
    // Variantes pleines pour cœurs/étoiles actifs.
    if ($name === 'heart') {
        return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.8 5.6a5.4 5.4 0 0 0-7.7 0L12 6.7l-1.1-1.1a5.4 5.4 0 1 0-7.7 7.7L12 22l8.8-8.7a5.4 5.4 0 0 0 0-7.7z"/></svg>';
    }
    return icon($name, $class);
}
