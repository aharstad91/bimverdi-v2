<?php
// Demo: Veikart-graf (kraft-graf)
// Wrapper som inkluderer template-veikart-prosjekter.php uten dobbel header/footer
$GLOBALS['bimverdi_skip_header_footer'] = true;
include get_theme_file_path('template-veikart-prosjekter.php');
unset($GLOBALS['bimverdi_skip_header_footer']);
