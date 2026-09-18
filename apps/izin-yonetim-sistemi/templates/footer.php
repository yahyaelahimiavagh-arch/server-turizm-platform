<?php

declare(strict_types=1);

$jsFile = dirname(__DIR__) . '/assets/js/app.js';
$jsVersion = is_file($jsFile) ? (string) filemtime($jsFile) : '1';
?>
</main>
<footer class="site-footer">
    <div class="container footer-wrap">
        <span><?= e(application_name()) ?></span>
        <a class="developer-mark" href="<?= e(developer_url()) ?>" target="_blank" rel="noopener noreferrer">Powered by <?= e(developer_name()) ?></a>
    </div>
</footer>
<script src="<?= e(base_path('assets/js/app.js?v=' . rawurlencode($jsVersion))) ?>" defer></script>
</body>
</html>
