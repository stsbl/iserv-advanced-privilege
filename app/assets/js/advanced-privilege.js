(function ($) {
    'use strict';

    let currentForm;
    const $output = $('.output');
    const previewTimers = {};
    const previewVersions = {};
    const previewDelay = 500;

    function escapeHtml(value) {
        return $('<div>').text(value).html();
    }

    function showAlert(type, message) {
        IServ.Message[type](message, false, '.output');
    }

    function clearAlerts() {
        $output.children('.alert').each(function () {
            $(this).alert('close');
        });
    }

    function clearPreviewErrors() {
        $output.children('.advanced-privilege-preview-error').each(function () {
            $(this).alert('close');
        });
    }

    function showPreviewError(message) {
        const $existingAlerts = $output.children('.alert');

        clearPreviewErrors();
        showAlert('error', message);
        $output.children('.alert').not($existingAlerts).addClass('advanced-privilege-preview-error');
    }

    function patternVisibility(type) {
        const target = $('[name="' + type + '[target]"]:checked').val();
        const needsPattern = target && target !== 'all';
        const $pattern = $('#' + type + '_pattern');

        $pattern.closest('.form-group').toggle(needsPattern);
        $pattern.prop('required', needsPattern);
    }

    function groupsPreview(type) {
        const target = $('[name="' + type + '[target]"]:checked').val();
        const $pattern = $('#' + type + '_pattern');
        const $preview = $('#' + type + '-groups-preview');
        const version = (previewVersions[type] || 0) + 1;

        previewVersions[type] = version;
        clearTimeout(previewTimers[type]);
        if (!target || (target !== 'all' && !$pattern.val())) {
            clearPreviewErrors();
            $preview.find('ul').empty();
            $preview.prop('hidden', true);
            return;
        }
        previewTimers[type] = setTimeout(function () {
            $.getJSON($('form[name="' + type + '"]').attr('action').replace(/\/apply$/, '/groups-preview'), {
                target: target,
                pattern: $pattern.val(),
            }).done(function (response) {
                if (previewVersions[type] !== version) {
                    return;
                }
                const groups = $.map(response.groups, function (group) {
                        return '<li class="media">'
                            + '<div class="media-left media-middle">' + (group.avatarHtml || '') + '</div>'
                        + '<div class="media-body media-middle"><a href="/iserv/admin/group/show/' + encodeURIComponent(group.group) + '">' + escapeHtml(group.name) + '</a></div>'
                        + '</li>';
                });
                clearPreviewErrors();
                $preview.find('ul').html(groups.join(''));
                $preview.prop('hidden', false);
            }).fail(function (xhr) {
                if (previewVersions[type] !== version) {
                    return;
                }
                const message = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : $output.data('error');
                showPreviewError(message);
            });
        }, previewDelay);
    }

    const formTypes = ['assign', 'revoke', 'owner'];

    formTypes.forEach(function (type) {
        patternVisibility(type);
    });

    formTypes.forEach(function (type) {
        $(document).on('change', '[name="' + type + '[target]"]', function () {
            patternVisibility(type);
            groupsPreview(type);
        });
        $(document).on('input change', 'form[name="' + type + '"] :input', function () {
            groupsPreview(type);
        });
    });

    $('form[name="assign"], form[name="revoke"], form[name="owner"]').on('submit', function (event) {
        event.preventDefault();
        currentForm = this;
        $.ajax({
            url: this.action.replace(/\/apply$/, '/preview'),
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
        }).done(function (response) {
            const groups = $.map(response.groups, function (group) {
                return '<li>' + escapeHtml(group.name) + '</li>';
            });
            const changes = $.map(response.changes, function (change) {
                return '<li>' + escapeHtml(change) + '</li>';
            });
            $('#multiple-confirm-groups').html(groups.join(''));
            $('#multiple-confirm-changes').html(changes.join(''));
            $('#multiple-confirm').modal('show');
        }).fail(function (xhr) {
            const message = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : $output.data('error');
            showAlert('error', message);
        });
    });

    $('#multiple-confirm-approve').on('click', function () {
        if (!currentForm) {
            return;
        }

        const form = currentForm;
        $('#multiple-confirm').modal('hide');
        $.ajax({
            url: form.action,
            type: 'POST',
            data: new FormData(form),
            processData: false,
            contentType: false,
        }).done(function (response) {
            clearAlerts();
            $.each(response.messages, function (_, message) {
                showAlert('success', message);
            });
            form.reset();
            patternVisibility(form.name);
        }).fail(function (xhr) {
            const message = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : $output.data('error');
            showAlert('error', message);
        });
    });
}(jQuery));
