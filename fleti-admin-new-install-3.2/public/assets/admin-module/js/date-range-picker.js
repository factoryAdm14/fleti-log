"use strict";

$(function () {

    function initDateRangePicker($element) {
        const picker = window.FletiDatePicker || {};
        const labels = picker.getLabels ? picker.getLabels() : {};
        const placeholder = $element.attr('placeholder') || labels.selectDate || 'Select Date';
        let lastValue = $element.val();

        $element.daterangepicker({
            ranges: picker.getRanges ? picker.getRanges() : {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [
                    moment().subtract(1, 'month').startOf('month'),
                    moment().subtract(1, 'month').endOf('month')
                ]
            },
            showCustomRangeLabel: true,
            autoUpdateInput: false,
            drops: 'auto',
            locale: picker.getLocaleOptions ? picker.getLocaleOptions({ cancelLabel: labels.cancel || 'Clear' }) : { cancelLabel: 'Clear' },
            alwaysShowCalendars: true
        });

        $element.on('apply.daterangepicker', function (ev, pickerInstance) {
            $(this).val(
                picker.formatRange
                    ? picker.formatRange(pickerInstance.startDate, pickerInstance.endDate)
                    : `${pickerInstance.startDate.format('MM/DD/YYYY')} - ${pickerInstance.endDate.format('MM/DD/YYYY')}`
            );
            lastValue = $(this).val();
        });

        $element.on('cancel.daterangepicker', function () {
            if (lastValue?.trim()) {
                $(this).val(lastValue);
            } else {
                $(this).val('').attr('placeholder', placeholder);
            }
        });

       $element.on('show.daterangepicker', function (ev, picker) {
            if ($(this).closest('.select_date_range_wrapper').length) {
                picker.container.find('.ranges').hide();
            } else {
                picker.container.find('.ranges').show();
            }

            const offset = $element.offset();
            const winHeight = $(window).height();
            if (offset.top > winHeight / 2) {
                picker.drops = 'up';
            } else {
                picker.drops = 'down';
            }
            picker.move();
        });


    }

    $('.date-range-picker').each(function () {
        initDateRangePicker($(this));
    });

    $('.offcanvas-body, .modal-body, .selected-list-inner').on('scroll', function () {
        $(this).find('.date-range-picker:focus, .time-range-picker:focus').each(function () {
            const $input = $(this);
            if ($input.data('daterangepicker')) {
                $input.data('daterangepicker').hide();
                $input.blur();
            }
        });
    });

});
