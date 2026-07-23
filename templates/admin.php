<?php if (!defined('WPINC')) { die; } ?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form method="post" action="options.php">
        <?php
        settings_fields('lh_seznamcz_autocomplete');
        do_settings_sections('lh_seznamcz_autocomplete');
        submit_button();
        ?>
    </form>
</div>
<script>
(function () {
    var toggle = document.getElementById('lh-seznam-enable_cf7');
    var intro = document.querySelector('.lh-seznam-cf7-section-intro');
    if (!toggle || !intro) {
        return;
    }
    // do_settings_sections() renderuje sekci jako <h2> + vystup callbacku + <table>.
    var parts = [intro, intro.previousElementSibling, intro.nextElementSibling].filter(Boolean);

    function sync() {
        parts.forEach(function (elem) {
            // Pole zamerne nedisablujeme - disablovana se neodeslou a ulozene
            // mapovani by se pri ulozeni ztratilo. Staci je vizualne odstavit.
            elem.style.opacity = toggle.checked ? '' : '0.5';
            elem.style.pointerEvents = toggle.checked ? '' : 'none';
        });
    }

    toggle.addEventListener('change', sync);
    sync();
})();
</script>
