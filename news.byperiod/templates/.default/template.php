<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
$this->setFrameMode(true);
?>

<div class="news-by-period" id="newsByPeriod-<?= $this->randString() ?>">
    <div class="news-filter">
        <div class="filter-row">
            <div class="filter-item">
                <label for="news-year"><?= Loc::getMessage('NEWS_BY_PERIOD_YEAR_LABEL') ?>:</label>
                <select id="news-year" class="filter-select">
                    <option value=""><?= Loc::getMessage('NEWS_BY_PERIOD_SELECT_YEAR') ?></option>
                    <?php
                    foreach ($arResult['YEARS'] as $year) {
                        $selected = $year == $arResult['YEAR'] ? 'selected' : '';
                        ?>
                        <option value="<?= $year ?>" <?= $selected ?>>
                            <?= $year ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </div>

            <div class="filter-item">
                <label for="news-month"><?= Loc::getMessage('NEWS_BY_PERIOD_MONTH_LABEL') ?>:</label>
                <select id="news-month" class="filter-select">
                    <option value=""><?= Loc::getMessage('NEWS_BY_PERIOD_SELECT_MONTH') ?></option>
                    <?php
                    foreach ($arResult['MONTHS'] as $value => $name) {
                        $selected = $value == $arResult['MONTH'] ? 'selected' : '';
                        ?>
                        <option value="<?= $value ?>" <?= $selected ?>>
                            <?= $name ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>

    <div class="news-results">
        <div class="news-list" id="news-list">
            <?php
            if (!empty($arResult['ITEMS'])) {
                foreach ($arResult['ITEMS'] as $item) {
                    ?>
                    <div class="news-item">
                        <h3 class="news-title"><?= htmlspecialcharsbx($item['NAME']) ?></h3>
                        <div class="news-date">
                            <?= formatDate('j F Y', makeTimeStamp($item['ACTIVE_FROM'])) ?>
                        </div>
                        <div class="news-preview">
                            <?= htmlspecialcharsbx($item['PREVIEW_TEXT']) ?>
                        </div>
                        <?php
                        if ($item['DETAIL_PAGE_URL']) {
                            ?>
                            <a href="<?= htmlspecialcharsbx($item['DETAIL_PAGE_URL']) ?>" class="news-detail-link">
                                <?= Loc::getMessage('NEWS_BY_PERIOD_DETAIL_LINK') ?>
                            </a>
                            <?php
                        }
                        ?>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="news-empty">
                    <?= Loc::getMessage('NEWS_BY_PERIOD_EMPTY_RESULT') ?>
                </div>
                <?php
            }
            ?>
        </div>

        <?php
        if (!empty($arResult['ITEMS']) && $arResult['NAV_OBJECT']->getPageCount() > 1) {
            ?>
            <div class="news-pagination" id="news-pagination">
                <?php
                if ($arResult['NAV_OBJECT']->getCurrentPage() > 1) {
                    ?>
                    <button class="pagination-btn" data-page="<?= $arResult['NAV_OBJECT']->getCurrentPage() - 1 ?>">
                        <?= Loc::getMessage('NEWS_BY_PERIOD_PREV_BUTTON') ?>
                    </button>
                    <?php
                }

                for ($i = 1; $i <= $arResult['NAV_OBJECT']->getPageCount(); $i++) {
                    $activeClass = $i == $arResult['NAV_OBJECT']->getCurrentPage() ? 'active' : '';
                    ?>
                    <button class="pagination-btn <?= $activeClass ?>" data-page="<?= $i ?>">
                        <?= $i ?>
                    </button>
                    <?php
                }

                if ($arResult['NAV_OBJECT']->getCurrentPage() < $arResult['NAV_OBJECT']->getPageCount()) {
                    ?>
                    <button class="pagination-btn" data-page="<?= $arResult['NAV_OBJECT']->getCurrentPage() + 1 ?>">
                        <?= Loc::getMessage('NEWS_BY_PERIOD_NEXT_BUTTON') ?>
                    </button>
                    <?php
                }
                ?>
            </div>
            <?php
        }
        ?>
    </div>

    <div class="news-loading" id="news-loading" style="display: none;">
        <div class="loading-spinner"><?= Loc::getMessage('NEWS_BY_PERIOD_LOADING') ?></div>
    </div>
</div>

<script>
    BX.ready(function () {
        window.newsByPeriodParams = {
            componentName: '<?= $component->getName() ?>',
            signedParameters: '<?= $this->getComponent()->getSignedParameters() ?>',
            siteId: '<?= SITE_ID ?>',
            year: <?= $arResult['YEAR'] ?: 'null' ?>,
            month: <?= $arResult['MONTH'] ?: 'null' ?>,
            pageSize: <?= (int) $arParams['PAGE_SIZE'] ?>,
            messages: {
                selectYearAndMonth: '<?= Loc::getMessage('NEWS_BY_PERIOD_SELECT_YEAR_AND_MONTH') ?>',
                error: '<?= Loc::getMessage('NEWS_BY_PERIOD_LOADING_ERROR') ?>',
                emptyResult: '<?= Loc::getMessage('NEWS_BY_PERIOD_EMPTY_RESULT') ?>'
            }
        };
    });
</script>
