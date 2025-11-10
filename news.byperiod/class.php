<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\PageNavigation;

class NewsByPeriodComponent extends CBitrixComponent implements Controllerable
{
    private static int $iblockId;

    /**
     * @return array
     */
    public function configureActions(): array
    {
        return [
            'getNews' => [
                'prefilters' => [
                    new ActionFilter\Csrf(),
                    new ActionFilter\HttpMethod(['POST']),
                ],
            ],
        ];
    }

    /**
     * @param array $arParams
     *
     * @return array
     */
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['IBLOCK_ID'] = (int) ($arParams['IBLOCK_ID'] ?? 0);
        $arParams['IBLOCK_CODE'] = trim($arParams['IBLOCK_CODE'] ?? 'news');
        $arParams['PAGE_SIZE'] = (int) ($arParams['PAGE_SIZE'] ?? 5);
        $arParams['CACHE_TIME'] = (int) ($arParams['CACHE_TIME'] ?? 36000000);

        return $arParams;
    }

    /**
     * @return void
     */
    public function executeComponent()
    {
        try {
            if (!$this->loadModules()) {
                return;
            }

            if (!$this->getIblockId()) {
                ShowError('Инфоблок не найден');

                return;
            }

            $this->processRequest();
            $this->prepareResult();
            $this->includeComponentTemplate();
        } catch (Exception $e) {
            ShowError($e->getMessage());
        }
    }

    /**
     * @return bool
     * @throws \Bitrix\Main\LoaderException
     */
    private function loadModules()
    {
        return Loader::includeModule('iblock');
    }

    /**
     * @return int|bool
     */
    private function getIblockId()
    {
        if ($this->iblockId) {
            return $this->iblockId;
        }

        if ($this->arParams['IBLOCK_ID'] > 0) {
            $this->iblockId = $this->arParams['IBLOCK_ID'];

            return $this->iblockId;
        }

        $iblock = IblockTable::getList([
            'filter' => ['=CODE' => $this->arParams['IBLOCK_CODE']],
            'select' => ['ID'],
        ])->fetch();

        if ($iblock) {
            $this->iblockId = $iblock['ID'];
        }

        return $this->iblockId;
    }

    /**
     * @return void
     */
    private function processRequest()
    {
        $request = Context::getCurrent()->getRequest();

        $this->arResult['YEAR'] = (int) $request->get('YEAR') ?: (int) $request->getPost('year') ?: (int) date('Y');
        $this->arResult['MONTH'] = (int) $request->get('MONTH') ?: (int) $request->getPost('month') ?: (int) date('m');
        $this->arResult['AJAX_MODE'] = $request->isAjaxRequest();
    }

    /**
     * @return void
     */
    private function prepareResult()
    {
        global $APPLICATION;

        $nav = $this->getNavigation();

        $cacheId = serialize([
            $this->getIblockId(),
            $this->arResult['YEAR'],
            $this->arResult['MONTH'],
            $nav->getCurrentPage(),
            $APPLICATION->GetCurPage(),
            $this->arParams,
        ]);

        if ($this->arResult['AJAX_MODE']) {
            $this->arResult['ITEMS'] = $this->getNewsItems($nav);
            $this->arResult['NAV_OBJECT'] = $nav;
        } else {
            if ($this->startResultCache($this->arParams['CACHE_TIME'], $cacheId)) {
                $this->arResult['ITEMS'] = $this->getNewsItems($nav);
                $this->arResult['NAV_OBJECT'] = $nav;
                $this->arResult['YEARS'] = $this->getAvailableYears();
                $this->arResult['MONTHS'] = $this->getMonthsList();

                $this->setResultCacheKeys([
                    'ITEMS',
                    'NAV_OBJECT',
                    'YEAR',
                    'MONTH',
                    'YEARS',
                    'MONTHS',
                ]);

                $this->endResultCache();
            }
        }

        if (empty($this->arResult['YEARS'])) {
            $this->arResult['YEARS'] = $this->getAvailableYears();
        }
        if (empty($this->arResult['MONTHS'])) {
            $this->arResult['MONTHS'] = $this->getMonthsList();
        }
    }

    /**
     * @param int|null $page
     *
     * @return PageNavigation
     */
    private function getNavigation(int $page = null): PageNavigation
    {
        $request = Context::getCurrent()->getRequest();
        $currentPage = $page ?: (int) ($request->get('page') ?: $request->getPost('page') ?: 1);

        $nav = new PageNavigation('news-nav');
        $nav->allowAllRecords(false)
            ->setPageSize($this->arParams['PAGE_SIZE'])
            ->setCurrentPage($currentPage);

        return $nav;
    }

    /**
     * @param PageNavigation $nav
     *
     * @return array
     * @throws DateMalformedStringException
     * @throws \Bitrix\Main\ObjectPropertyException
     */
    private function getNewsItems(PageNavigation $nav)
    {
        if (!$this->arResult['YEAR'] || !$this->arResult['MONTH']) {
            return [];
        }

        $startDate = DateTime::createFromPhp(
            new \DateTime(sprintf('%04d-%02d-01 00:00:00', $this->arResult['YEAR'], $this->arResult['MONTH']))
        );

        $endDate = DateTime::createFromPhp(
            new \DateTime(
                sprintf(
                    '%04d-%02d-%02d 23:59:59',
                    $this->arResult['YEAR'],
                    $this->arResult['MONTH'],
                    $startDate->format('t')
                )
            )
        );

        $query = ElementTable::getList([
            'select' => [
                'ID',
                'NAME',
                'ACTIVE_FROM',
                'PREVIEW_TEXT',
                'DETAIL_PAGE_URL' => 'IBLOCK.DETAIL_PAGE_URL',
                'CODE',
            ],
            'filter' => [
                '=IBLOCK_ID' => $this->getIblockId(),
                '=ACTIVE' => 'Y',
                '>=ACTIVE_FROM' => $startDate,
                '<=ACTIVE_FROM' => $endDate,
            ],
            'order' => ['ACTIVE_FROM' => 'DESC'],
            'count_total' => true,
            'offset' => $nav->getOffset(),
            'limit' => $nav->getLimit(),
        ]);

        $totalCount = $query->getCount();
        $nav->setRecordCount($totalCount);

        $items = [];
        while ($item = $query->fetch()) {
            if ($item['CODE']) {
                $item['DETAIL_PAGE_URL'] = str_replace(
                    ['#ELEMENT_CODE#', '#ID#'],
                    [$item['CODE'], $item['ID']],
                    $item['DETAIL_PAGE_URL']
                );
            }
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return array
     */
    private function getAvailableYears(): array
    {
        $years = [];
        $currentYear = (int) date('Y');

        for ($year = 2020; $year <= $currentYear + 1; $year++) {
            $years[$year] = $year;
        }

        return $years;
    }

    /**
     * @return array
     */
    private function getMonthsList(): array
    {
        return [
            1 => 'Январь',
            2 => 'Февраль',
            3 => 'Март',
            4 => 'Апрель',
            5 => 'Май',
            6 => 'Июнь',
            7 => 'Июль',
            8 => 'Август',
            9 => 'Сентябрь',
            10 => 'Октябрь',
            11 => 'Ноябрь',
            12 => 'Декабрь',
        ];
    }

    /**
     * @param int $year
     * @param int $month
     * @param int $page
     *
     * @return array
     * @throws \Bitrix\Main\LoaderException
     */
    public function getNewsAction(int $year, int $month, int $page = 1, $pageSize = null)
    {
        if ($pageSize) {
            $this->arParams['PAGE_SIZE'] = (int) $pageSize;
        }

        $this->arParams = $this->onPrepareComponentParams($this->arParams);

        if (!$this->loadModules()) {
            return ['success' => false, 'error' => 'Модуль iblock не загружен'];
        }

        if (!$this->getIblockId()) {
            return ['success' => false, 'error' => 'Инфоблок не найден'];
        }

        $this->arResult['YEAR'] = $year;
        $this->arResult['MONTH'] = $month;
        $this->arResult['AJAX_MODE'] = true;

        $nav = $this->getNavigation($page);

        $items = $this->getNewsItems($nav);

        ob_start();
        foreach ($items as $item) {
            ?>
            <div class="news-item fade-in">
                <h3 class="news-title"><?= htmlspecialcharsbx($item['NAME']) ?></h3>
                <div class="news-date"><?= formatDate('j F Y', makeTimeStamp($item['ACTIVE_FROM'])) ?></div>
                <div class="news-preview"><?= htmlspecialcharsbx($item['PREVIEW_TEXT']) ?></div>
                <?php
                if ($item['DETAIL_PAGE_URL']) { ?>
                    <a href="<?= htmlspecialcharsbx($item['DETAIL_PAGE_URL']) ?>" class="news-detail-link">
                        <?= Loc::getMessage('NEWS_BY_PERIOD_DETAIL_LINK') ?>
                    </a>
                    <?php
                } ?>
            </div>
            <?php
        }
        $html = ob_get_clean();

        return [
            'success' => true,
            'html' => $html,
            'navHtml' => $this->getNavHtml($nav),
            'year' => $year,
            'month' => $month,
            'currentPage' => $nav->getCurrentPage(),
            'pageCount' => $nav->getPageCount(),
            'totalCount' => $nav->getRecordCount(),
        ];
    }

    /**
     * @param PageNavigation $nav
     *
     * @return string
     */
    private function getNavHtml(PageNavigation $nav): string
    {
        if ($nav->getPageCount() <= 1) {
            return '';
        }

        ob_start();
        ?>
        <div class="news-pagination">
            <?php
            if ($nav->getCurrentPage() > 1) {
                ?>
                <button class="pagination-btn" data-page="<?= $nav->getCurrentPage() - 1 ?>">
                    <?= Loc::getMessage('NEWS_BY_PERIOD_PREV_BUTTON') ?>
                </button>
                <?php
            }

            for ($i = 1; $i <= $nav->getPageCount(); $i++) {
                $activeClass = $i == $nav->getCurrentPage() ? 'active' : '';
                ?>
                <button class="pagination-btn <?= $activeClass ?>" data-page="<?= $i ?>">
                    <?= $i ?>
                </button>
                <?php
            }

            if ($nav->getCurrentPage() < $nav->getPageCount()) {
                ?>
                <button class="pagination-btn" data-page="<?= $nav->getCurrentPage() + 1 ?>">
                    <?= Loc::getMessage('NEWS_BY_PERIOD_NEXT_BUTTON') ?>
                </button>
                <?php
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
