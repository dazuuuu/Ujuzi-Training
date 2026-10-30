<?php
/**
 * Root fallback front controller. Used only when a host serves the project
 * root directly and mod_rewrite isn't available to map requests into public/.
 * It simply runs the real front controller. (Static assets still need the
 * root .htaccess, or the document root pointed at public/, to resolve.)
 */
require __DIR__ . '/public/index.php';
