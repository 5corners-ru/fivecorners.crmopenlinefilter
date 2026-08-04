<?php

namespace FiveCorners\CrmOpenlineFilter;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;

class EventHandler
{
    /**
     * DI\ServiceLocator живёт только в рамках одного HTTP-запроса — Bitrix не
     * персистит его между хитами. Поэтому подмену 'crm.filter.factory' нужно
     * переустанавливать на каждый прогон прolog'а, а не один раз при установке.
     */
    public static function onProlog(): void
    {
        if (!Loader::includeModule('crm')) {
            return;
        }

        ServiceLocator::getInstance()->addInstance('crm.filter.factory', new FilterFactory());
    }
}
