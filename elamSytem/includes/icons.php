<?php
/**
 * Material Icons helper — requires Material Icons font in page head (styles.css).
 */
function mi(string $name, int $sizePx = 20): string {
    return '<span class="material-icons" style="font-size:' . $sizePx . 'px" aria-hidden="true">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</span>';
}

function mi_btn(string $name, string $label, int $sizePx = 18): string {
    return mi($name, $sizePx) . '<span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
}
