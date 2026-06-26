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
