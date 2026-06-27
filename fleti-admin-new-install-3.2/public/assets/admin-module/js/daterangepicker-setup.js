'use strict';

(function (window) {
    function getConfig() {
        return window.fletiCalendarConfig || {};
    }

    function getMomentLocale() {
        const config = getConfig();
        if (config.momentLocale) {
            return config.momentLocale;
        }
        return config.locale === 'pt' ? 'pt-br' : 'en';
    }

    function getBcp47Locale() {
        return getMomentLocale() === 'pt-br' ? 'pt-BR' : 'en-US';
    }

    function initMomentLocale() {
        if (typeof moment === 'undefined') {
            return;
        }
        moment.locale(getMomentLocale());
    }

    function getDateFormat() {
        const config = getConfig();
        return config.dateFormat || (config.locale === 'pt' ? 'DD/MM/YYYY' : 'MM/DD/YYYY');
    }

    function getLabels() {
        return getConfig().labels || {};
    }

    function getRanges() {
        const labels = getLabels();

        return {
            [labels.today || 'Today']: [moment(), moment()],
            [labels.yesterday || 'Yesterday']: [
                moment().subtract(1, 'days'),
                moment().subtract(1, 'days')
            ],
            [labels.last7Days || 'Last 7 Days']: [moment().subtract(6, 'days'), moment()],
            [labels.last30Days || 'Last 30 Days']: [moment().subtract(29, 'days'), moment()],
            [labels.thisMonth || 'This Month']: [moment().startOf('month'), moment().endOf('month')],
            [labels.lastMonth || 'Last Month']: [
                moment().subtract(1, 'month').startOf('month'),
                moment().subtract(1, 'month').endOf('month')
            ]
        };
    }

    function getLocaleOptions(extra) {
        initMomentLocale();

        const labels = getLabels();
        const locale = {
            format: getDateFormat(),
            separator: ' - ',
            applyLabel: labels.apply || 'Apply',
            cancelLabel: labels.cancel || 'Clear',
            fromLabel: labels.from || 'From',
            toLabel: labels.to || 'To',
            customRangeLabel: labels.custom || 'Custom',
            weekLabel: 'S',
            daysOfWeek: moment.weekdaysMin(),
            monthNames: moment.monthsShort(),
            firstDay: moment.localeData().firstDayOfWeek()
        };

        return Object.assign(locale, extra || {});
    }

    function formatRange(startDate, endDate) {
        const format = getDateFormat();
        return `${startDate.format(format)} - ${endDate.format(format)}`;
    }

    function applyWeekdayLabels($container) {
        const config = getConfig();
        const weekdays = config.weekdaysMin;
        if (!weekdays || !$container || !$container.length) {
            return;
        }

        $container.find('.week div').each(function (index) {
            if (weekdays[index]) {
                $(this).text(weekdays[index]);
            }
        });
    }

    window.FletiDatePicker = {
        initMomentLocale,
        getMomentLocale,
        getBcp47Locale,
        getDateFormat,
        getRanges,
        getLocaleOptions,
        formatRange,
        applyWeekdayLabels,
        getLabels
    };

    initMomentLocale();
}(window));
