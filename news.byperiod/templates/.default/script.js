(function () {
    'use strict';

    class NewsByPeriod {
        constructor(containerId) {
            this.container = document.getElementById(containerId);
            if (!this.container) return;

            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            const applyBtn = this.container.querySelector('#news-apply-filter');
            if (applyBtn) {
                applyBtn.addEventListener('click', () => this.applyFilter());
            }

            const yearSelect = this.container.querySelector('#news-year');
            const monthSelect = this.container.querySelector('#news-month');

            if (yearSelect && monthSelect) {
                yearSelect.addEventListener('change', () => this.onFilterChange());
                monthSelect.addEventListener('change', () => this.onFilterChange());
            }

            this.container.addEventListener('click', (e) => {
                if (e.target.classList.contains('pagination-btn')) {
                    e.preventDefault();
                    const page = parseInt(e.target.getAttribute('data-page'));
                    if (page) {
                        this.loadPage(page);
                    }
                }
            });
        }

        onFilterChange() {
            this.applyFilter();
        }

        applyFilter() {
            const year = this.container.querySelector('#news-year').value;
            const month = this.container.querySelector('#news-month').value;

            if (!year || !month) {
                alert('Пожалуйста, выберите год и месяц');
                return;
            }

            this.loadNews(year, month, 1);
        }

        loadNews(year, month, page = 1) {
            this.showLoading(true);

            BX.ajax.runComponentAction(
                window.newsByPeriodParams.componentName,
                'getNews',
                {
                    mode: 'class',
                    signedParameters: window.newsByPeriodParams.signedParameters,
                    data: {
                        year: parseInt(year),
                        month: parseInt(month),
                        page: parseInt(page),
                        pageSize: window.newsByPeriodParams.pageSize
                    }
                }
            ).then((response) => {
                this.showLoading(false);

                if (response.data.success) {
                    this.updateContent(response.data);
                    this.updateUrl(year, month);
                } else {
                    this.showError('Ошибка загрузки данных');
                }
            }).catch((error) => {
                this.showLoading(false);
                console.error('AJAX Error:', error);
                this.showError('Ошибка загрузки данных: ' + error.status);
            });
        }

        loadPage(page) {
            const year = this.container.querySelector('#news-year').value;
            const month = this.container.querySelector('#news-month').value;

            if (year && month) {
                this.loadNews(year, month, page);
            }
        }

        updateContent(data) {
            const newsList = this.container.querySelector('#news-list');
            const pagination = this.container.querySelector('#news-pagination');

            if (!newsList) return;

            newsList.style.opacity = '0';
            setTimeout(() => {
                if (data.html && data.html.trim() !== '') {
                    newsList.innerHTML = data.html;
                } else {
                    newsList.innerHTML = `<div class="news-empty">${ window.newsByPeriodParams.messages.emptyResult }</div>`;
                }
                newsList.style.opacity = '1';
            }, 300);

            if (pagination) {
                pagination.innerHTML = data.navHtml || '';
            }
        }

        updateUrl(year, month) {
            const newUrl = `/news/${ year }/${ month }/`;
            window.history.pushState({}, '', newUrl);
        }

        showLoading(show) {
            const loading = this.container.querySelector('#news-loading');
            const results = this.container.querySelector('.news-results');

            if (loading) {
                loading.style.display = show ? 'block' : 'none';
            }
            if (results) {
                results.style.display = show ? 'none' : 'block';
            }
        }

        showError(message) {
            alert(message);
        }
    }

    BX.ready(function () {
        const containers = document.querySelectorAll('[id^="newsByPeriod-"]');
        containers.forEach((container) => {
            new NewsByPeriod(container.id);
        });
    });
})();
