<?php

namespace FiveCorners\CrmOpenlineFilter;

use Bitrix\Crm\Badge\Model\BadgeTable;
use Bitrix\Crm\Filter;
use Bitrix\Main;
use Bitrix\Main\Filter\DataProvider;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Подменяет штатный DI-сервис 'crm.filter.factory' (Bitrix\Crm\Filter\Factory) —
 * см. lib/EventHandler.php::onProlog(). Добавляет в фильтр Лидов и Сделок
 * псевдо-поле "Чат с клиентом прочитан?", транслируя его в подзапрос к штатной
 * таблице бейджей CRM (b_crm_item_badge, TYPE=open_line_status,
 * VALUE=not_read_chat) — той самой, что ведёт ядро в реальном времени при
 * получении/прочтении сообщений открытых линий. Модуль ничего не считает сам.
 */
class FilterFactory extends Filter\Factory
{
    public const CUSTOM_FIELD_ID = 'FCO_COLF_CHAT_READ';

    private const BADGE_TYPE          = 'open_line_status';
    private const BADGE_VALUE_UNREAD  = 'not_read_chat';

    public function getDataProvider(Main\Filter\EntitySettings $settings): DataProvider
    {
        if ($settings instanceof Filter\LeadSettings) {
            return new class($settings) extends Filter\LeadDataProvider {
                public function applyCounterFilter(int $entityTypeId, array &$filterFields, array $extras = []): void
                {
                    FilterFactory::applyChatReadFilter($entityTypeId, $filterFields);
                    parent::applyCounterFilter($entityTypeId, $filterFields, $extras);
                }
            };
        }

        if ($settings instanceof Filter\DealSettings) {
            return new class($settings) extends Filter\DealDataProvider {
                public function applyCounterFilter(int $entityTypeId, array &$filterFields, array $extras = []): void
                {
                    FilterFactory::applyChatReadFilter($entityTypeId, $filterFields);
                    parent::applyCounterFilter($entityTypeId, $filterFields, $extras);
                }
            };
        }

        return parent::getDataProvider($settings);
    }

    public function getUserFieldDataProvider(Main\Filter\EntitySettings $settings): DataProvider
    {
        if ($settings instanceof Filter\LeadSettings || $settings instanceof Filter\DealSettings) {
            return new class($settings) extends Filter\ItemUfDataProvider {
                public function prepareFields(): array
                {
                    return array_merge(parent::prepareFields(), [
                        FilterFactory::CUSTOM_FIELD_ID => $this->createField(
                            FilterFactory::CUSTOM_FIELD_ID,
                            [
                                'type'    => 'checkbox',
                                'name'    => Loc::getMessage('FCO_COLF_FIELD_NAME'),
                                'data'    => ['valueType' => 'number'],
                                'subtype' => 'boolean',
                            ]
                        ),
                    ]);
                }
            };
        }

        return parent::getUserFieldDataProvider($settings);
    }

    /**
     * Переводит псевдо-поле чекбокса в реальное условие фильтра CRM-выборки.
     * 'Y' ("прочитан") — исключить ID с непрочитанным чатом; иначе — оставить только их.
     */
    public static function applyChatReadFilter(int $entityTypeId, array &$filterFields): void
    {
        if (!isset($filterFields[self::CUSTOM_FIELD_ID]) || $filterFields[self::CUSTOM_FIELD_ID] === '') {
            return;
        }

        $isRead = ($filterFields[self::CUSTOM_FIELD_ID] === 'Y');
        unset($filterFields[self::CUSTOM_FIELD_ID]);

        $unreadIds = self::getEntityIdsWithUnreadChat($entityTypeId);

        if ($isRead) {
            if ($unreadIds) {
                $filterFields['!=ID'] = $unreadIds;
            }
            return;
        }

        $filterFields['=ID'] = $unreadIds ?: [0];
    }

    private static function getEntityIdsWithUnreadChat(int $entityTypeId): array
    {
        $rows = BadgeTable::query()
            ->setSelect(['ENTITY_ID'])
            ->where('ENTITY_TYPE_ID', $entityTypeId)
            ->where('TYPE', self::BADGE_TYPE)
            ->where('VALUE', self::BADGE_VALUE_UNREAD)
            ->exec();

        $result = [];
        while ($row = $rows->fetch()) {
            $result[] = (int)$row['ENTITY_ID'];
        }

        return $result;
    }
}
