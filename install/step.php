<?php
/**
 * Страница после успешной установки модуля (DoInstall → step.php).
 *
 * У модуля нет собственной admin-страницы (Правило 2) — фильтр появляется
 * сразу в стандартном фильтре CRM, настраивать нечего.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
    <table class="adm-detail-content-table edit-table" width="100%">
        <tr>
            <td align="center" width="100%" style="text-align:center;">
                <div class="adm-info-message-wrap adm-info-message-green">
                    <div class="adm-info-message">
                        <div class="adm-info-message-title" style="text-align:center;">
                            <?= Loc::getMessage('FCO_COLF_INSTALL_SUCCESS_TITLE') ?>
                        </div>
                        <div class="adm-info-message-body" style="text-align:center;">
                            <?= Loc::getMessage('FCO_COLF_INSTALL_SUCCESS_TEXT') ?>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td align="center" style="text-align:center;">
                <input type="hidden" name="lang"    value="<?= LANGUAGE_ID ?>">
                <input type="hidden" name="id"      value="fivecorners.crmopenlinefilter">
                <input type="hidden" name="install" value="Y">
                <input type="hidden" name="step"    value="2">
                <input type="submit" name="inst"    value="<?= Loc::getMessage('FCO_COLF_INSTALL_BACK') ?>">
            </td>
        </tr>
    </table>
</form>
