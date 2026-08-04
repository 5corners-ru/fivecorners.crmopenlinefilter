<?php
use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('fivecorners.crmopenlinefilter', [
    'FiveCorners\CrmOpenlineFilter\FilterFactory' => 'lib/FilterFactory.php',
    'FiveCorners\CrmOpenlineFilter\EventHandler'  => 'lib/EventHandler.php',
]);
