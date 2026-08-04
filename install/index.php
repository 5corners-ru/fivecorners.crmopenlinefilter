<?php
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

// DO NOT redeclare $MODULE_ID, $MODULE_NAME, $MODULE_VERSION etc. — они уже объявлены
// в CModule без типов. Типизированное переобъявление роняет E_COMPILE_ERROR на PHP 8.1+.
class fivecorners_crmopenlinefilter extends CModule
{
    public const MODULE_ID = 'fivecorners.crmopenlinefilter';

    public function __construct()
    {
        $version = [];
        include __DIR__ . '/version.php';

        $this->MODULE_ID           = self::MODULE_ID;
        $this->MODULE_VERSION      = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME         = Loc::getMessage('FCO_COLF_MODULE_NAME');
        $this->MODULE_DESCRIPTION  = Loc::getMessage('FCO_COLF_MODULE_DESC');
        $this->PARTNER_NAME        = '5 УГЛОВ';
        $this->PARTNER_URI         = 'https://5corners.ru';
    }

    public function DoInstall(): bool
    {
        global $APPLICATION;

        if (!Loader::includeModule('crm')) {
            $APPLICATION->ThrowException(Loc::getMessage('FCO_COLF_INSTALL_ERROR_CRM'));
            return false;
        }

        $this->InstallDB();
        $this->InstallFiles();
        $this->InstallEvents();

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('FCO_COLF_INSTALL_TITLE'),
            __DIR__ . '/step.php'
        );

        return true;
    }

    public function DoUninstall(): bool
    {
        global $APPLICATION;

        $step = (int)($_REQUEST['step'] ?? 1);

        if ($step < 2) {
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('FCO_COLF_UNINSTALL_TITLE'),
                __DIR__ . '/unstep1.php'
            );
            return true;
        }

        $saveData = (($_REQUEST['save_data'] ?? '') === 'Y');

        $this->UnInstallEvents();
        $this->UnInstallFiles();

        // Модуль не хранит собственных пользовательских данных (опций, таблиц,
        // UF-полей) — чекбокс сохранён по канону двухшагового удаления (Правило 3),
        // но реально нечего сохранять/стирать.
        if (!$saveData) {
            Option::delete(self::MODULE_ID);
        }

        $this->UnInstallDB();

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('FCO_COLF_UNINSTALL_TITLE'),
            __DIR__ . '/unstep.php'
        );

        return true;
    }

    public function InstallDB(): bool
    {
        RegisterModule(self::MODULE_ID);
        return true;
    }

    public function UnInstallDB(): bool
    {
        UnRegisterModule(self::MODULE_ID);
        return true;
    }

    public function InstallFiles(): bool
    {
        $docRoot = Application::getDocumentRoot();

        // (1) Per-module admin icon: install/images/admin_module_icon.png
        $moduleImgDir = $docRoot . '/local/images/' . self::MODULE_ID;
        if (!is_dir($moduleImgDir)) {
            @mkdir($moduleImgDir, 0755, true);
        }
        $adminIconSrc = __DIR__ . '/images/admin_module_icon.png';
        if (is_file($adminIconSrc)) {
            @copy($adminIconSrc, $moduleImgDir . '/admin_module_icon.png');
        }

        // (2) Section icon: shared /local/images/fivecorners/logo.svg
        // file_exists guard: не перезаписываем иконку секции, если её уже поставил другой модуль.
        $sharedImgDir   = $docRoot . '/local/images/fivecorners';
        $sectionIconSrc = __DIR__ . '/images/section_icon.svg';
        $sectionIconDst = $sharedImgDir . '/logo.svg';
        if (!is_dir($sharedImgDir)) {
            @mkdir($sharedImgDir, 0755, true);
        }
        if (is_file($sectionIconSrc) && !is_file($sectionIconDst)) {
            @copy($sectionIconSrc, $sectionIconDst);
        }

        return true;
    }

    public function UnInstallFiles(): bool
    {
        $docRoot = Application::getDocumentRoot();

        // Per-module icon directory (recursive, owns it entirely)
        $moduleImgDir = $docRoot . '/local/images/' . self::MODULE_ID;
        if (is_dir($moduleImgDir)) {
            $this->removeDir($moduleImgDir);
        }

        // Section icon: удаляем только если md5 совпадает (не трогаем чужую версию)
        $sectionIconDst = $docRoot . '/local/images/fivecorners/logo.svg';
        $sectionIconSrc = __DIR__ . '/images/section_icon.svg';
        if (
            is_file($sectionIconDst)
            && is_file($sectionIconSrc)
            && md5_file($sectionIconDst) === md5_file($sectionIconSrc)
        ) {
            @unlink($sectionIconDst);
        }

        // Shared dir: удаляем только если пустая
        $sharedImgDir = $docRoot . '/local/images/fivecorners';
        if (is_dir($sharedImgDir) && $this->isDirEmpty($sharedImgDir)) {
            @rmdir($sharedImgDir);
        }

        return true;
    }

    public function InstallEvents(): bool
    {
        // Снимаем хендлер до регистрации: registerEventHandler не дедуплицирует,
        // иначе при re-install (save_data=Y) обработчик задваивается (Правило 16).
        $this->UnInstallEvents();

        $em = EventManager::getInstance();

        // OnProlog выполняется на каждый хит и переустанавливает подмену DI-сервиса
        // 'crm.filter.factory' (ServiceLocator не переживает между запросами — см.
        // docs/ARCHITECTURE.md). Это НЕ обработчик бизнес-события, а обязательная
        // per-request инициализация — поэтому она в EventHandler::onProlog, а не в
        // include.php напрямую (Правило 11 запрещает регистрировать хендлеры в
        // include.php, но сама регистрация здесь как раз в install/index.php).
        $em->registerEventHandler(
            'main', 'OnProlog',
            self::MODULE_ID,
            '\\FiveCorners\\CrmOpenlineFilter\\EventHandler',
            'onProlog'
        );

        $em->clearLoadedHandlers();

        return true;
    }

    public function UnInstallEvents(): bool
    {
        $em = EventManager::getInstance();

        $em->unRegisterEventHandler(
            'main', 'OnProlog',
            self::MODULE_ID,
            '\\FiveCorners\\CrmOpenlineFilter\\EventHandler',
            'onProlog'
        );

        $em->clearLoadedHandlers();

        return true;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    private function isDirEmpty(string $dir): bool
    {
        return count(array_diff(scandir($dir), ['.', '..'])) === 0;
    }
}
