<?php
/**
 * Шаг 1 удаления — форма подтверждения (DoUninstall, step 1).
 *
 * Rule 3 + Rule 24: method="post" + bitrix_sessid_post() (sessid не должен
 * течь в access-логи через GET, prefetch-риск).
 */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/index.php');
?>
<form method="post" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="id"        value="fivecorners.crmopenlinefilter">
    <input type="hidden" name="uninstall" value="Y">
    <input type="hidden" name="step"      value="2">

    <p style="text-align:center;"><?= Loc::getMessage('FCO_COLF_UNINSTALL_CONFIRM') ?></p>

    <p style="text-align:center;">
        <label>
            <input type="checkbox" name="save_data" value="Y" checked>
            <?= Loc::getMessage('FCO_COLF_SAVE_DATA') ?>
        </label>
    </p>

    <p style="text-align:center;">
        <input type="submit" class="adm-btn-save" value="<?= htmlspecialcharsbx(Loc::getMessage('FCO_COLF_UNINSTALL_DO')) ?>">
    </p>
</form>
