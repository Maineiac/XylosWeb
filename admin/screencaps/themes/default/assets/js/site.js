/*
 * Leyscreencap Web (https://demo.maddela.org/leyscreencap/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/leyscreencap-web
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2021 kanalumaddela
 * @license   MIT
 */

const elem = (tag, attrs, ...children) => {
    let elem = document.createElement(tag);
    Object.keys(attrs).forEach(function (key) {
        if (key in document.createElement(tag)) {
            elem[key] = attrs[key];
        } else {
            elem.setAttribute(key, attrs[key]);
        }
    });

    children.forEach(child => {
        if (typeof child === "string") {
            child = document.createTextNode(child);
        }
        elem.appendChild(child);
    });
    return elem;
};

const api = (path, data, callback = null) => {
    let url = app.route + '/api/' + path;
    if (app.debug) {
        console.log('API: ' + path);
        console.log('DATA:');
        console.log(data);
        console.log('CALLBACK:');
        console.log(callback);
    }
    $.post(url, data, function (data, status) {
        if (app.debug) {
            console.log('RESPONSE:');
            console.log(data);
        }
        if (typeof window[callback] === 'function') {
            window[callback](data, status);
        }
    });
};

const lang = (key) => {
    let phrase = key;
    if (typeof locale[key] !== 'undefined') {
        phrase = locale[key]
    } else if (typeof locale_fallback[key] !== 'undefined') {
        locale_fallback[key] = locale[key];
    }

    return phrase;
};

const time = (timestamp) => new Date(timestamp * 1000);

$('span[data-timestamp]').each(function () {
    let timestamp = time($(this).data('timestamp'));
    timestamp = timestamp.toLocaleDateString() + ' @ ' + timestamp.toLocaleTimeString();
    this.innerText = timestamp;
});

$('.lightbox').click(function () {
    $(this).toggleClass('expanded');
    $('.overlay').toggleClass('opened');
});

$('[data-toggle="tooltip"]').tooltip();
$('.delete-screenshot').click(function () {
    let screenshot = $(this).data('screenshot');
    if (screenshot) {

        if (window.confirm(lang('screenshot.trash.confirm'))) {
            $(this).parent().addClass('deleting');

            api('screenshots/delete', {screenshot}, 'deleteScreenshot');
        }
    }
});

function deleteScreenshot(data, status) {
    if (app.debug) {
        console.log('DELETING SCREENSHOT');
        console.log('DATA:');
        console.log(data);
        console.log('STATUS:');
        console.log(status);
    }
    let image_id = data.image_id;
    if (data.success) {
        if (app.debug) {
            console.log('image_id:');
            console.log(image_id);
        }
        $('#' + image_id).parents('.screenshot').fadeOut(300, function () {
            $(this).remove();
        });

        toast(lang('screenshot.trashed'));
    } else {
        toast(lang('screenshot.trashed.failed'));
        $('#' + image_id).parents('.screenshot').removeClass('deleting');
    }
}

$("#screenshot-player-form").submit(function (event) {
    let inputs = $('#screenshot-player-form :input');
    let values = {};

    inputs.each(function () {
        values[this.name] = $(this).val();
    });

    api('users/capture', values);
    toast(lang('screenshot.requested'));
    $('#screenshot-player').modal('hide');

    event.preventDefault();
});

var toast_count = 0;

function toast(message, duration = 3000, html = false) {
    let toast_id = 'toast_' + toast_count;
    let toast_data = {
        id: toast_id,
        className: 'toast'
    };

    if (html) {
        toast_data.innerHTML = message;
    } else {
        toast_data.innerText = message;
    }

    let toast = elem('div', toast_data);

    document.getElementById('toasts').appendChild(toast);
    toast_count++;

    setTimeout(function () {
        $('#' + toast_id).fadeOut(250, function () {
            $(this).remove();
        })
    }, duration);
}

$('#remember_me').click(function () {
    let checked = this.checked;
    api('rememberme', {checked});
});