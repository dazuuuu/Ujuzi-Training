<?php
/** Public student lookup, laid out by Super Admin → Pages. Requires $lookupNumber and $lookupRecord (PublicLookup::find). */
$pbPage = 'lookup';
$pbTitle = 'Student lookup | ' . appName();
$pbDescription = 'Check that a person is a genuine ' . appName() . ' student and see the courses they completed.';
require __DIR__ . '/partials/pb-page.php';
