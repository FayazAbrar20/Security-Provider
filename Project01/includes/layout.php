<?php
/*
 * Shared page chrome for the sign-in, sign-up and password pages, so every one of them
 * uses the same dark theme as the landing page. $root is the path back to Project01/
 * ("" from Project01/, "../" from Client/, Guard/ or Admin/).
 */

const ICONS = [
    'shield' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 5 3.4 8.3 8 9 4.6-.7 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>',
    'user'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>',
    'key'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 9.3-9.3M17 6l3 3M14 9l2 2"/></svg>',
    'lock'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>',
    'badge'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2.5"/><path d="M5.5 16.5a3.5 3.5 0 0 1 7 0M15 10h3M15 13h3"/></svg>',
    'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>',
    'clock'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    'check'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>',
    'users'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/></svg>',
    'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
];

function icon(string $name): string
{
    return ICONS[$name] ?? '';
}

function page_head(string $title, string $root): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?> · Security Provider</title>
    <link rel="stylesheet" href="<?php echo e($root); ?>assets/theme.css">
</head>
<?php
}

function auth_start(string $title, string $subtitle, string $root, string $iconName = 'shield', bool $wide = false): void
{
    page_head($title, $root);
    ?>
<body>
    <nav class="brandbar">
        <a class="brand" href="<?php echo e($root); ?>index.php">
            <img src="<?php echo e($root); ?>uploads/index/Image (12).jpg" alt="">
            <span>SECURITY PROVIDER</span>
        </a>
        <a class="pill-link" href="<?php echo e($root); ?>index.php">&larr; Home</a>
    </nav>
    <main class="auth-wrap">
        <section class="auth-card<?php echo $wide ? ' wide' : ''; ?>">
            <div class="auth-icon"><?php echo icon($iconName); ?></div>
            <h2><?php echo e($title); ?></h2>
            <p class="auth-sub"><?php echo e($subtitle); ?></p>
<?php
}

function auth_end(): void
{
    ?>
        </section>
    </main>
</body>
</html>
<?php
}
