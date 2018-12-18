import Loading from 'IServ.Loading';
import Message from 'IServ.Message';
import Routing from 'IServ.Routing';
import Select2 from 'IServ.Select2';
import Spinner from 'IServ.Spinner';

/*
 * The MIT License
 *
 * Copyright 2018 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

(function () {
    "use strict";
    
    let $currentForm;
    
    function smoothScrollToOutput()
    {
        $('html, body').animate({
            scrollTop: $('.output').offset().top
        }, 1000);
    }
    
    function resetForm()
    {
        $currentForm[0].reset();
        Select2.init($('#' + $currentForm.attr('name') + '_owner'));
        Select2.init($('#' + $currentForm.attr('name') + '_privileges'));
        $('#' + $currentForm.attr('name') + '_flags').val('val', '');
        showPattern($currentForm.attr('name'));
    }
    
    function removeAlerts()
    {
        // remove old alerts
        $('.output > .alert').each(function () {
            $(this).alert('close');
        });
    }
    
    function hidePattern(type)
    {
        $('#multiple-' + type + '-form-group-pattern').hide();
        $('#' + type + '_pattern').prop('required', false);
    }

    function showPattern(type)
    {
        $('#multiple-' + type + '-form-group-pattern').show();
        $('#' + type + '_pattern').prop('required', true);
    }
    
    function registerTargetHandler(type)
    {
        if ($('#' + type + '_target_0').is(':checked')) {
            hidePattern(type);
        }
        
        $('[id^="' + type + '_target_"').change(function () {
            if ($(this).attr('id') === type + '_target_0') {
                hidePattern(type);
            } else {
                showPattern(type);
            }
        });
    }
    
    function registerFormHandler()
    {
        var submitHandler = function (e) {
            $('#multiple-confirm').modal('show');
            
            $currentForm = $(this);
            
            e.preventDefault();
            return false;
        };
        
        $('form').submit(submitHandler);
        
        $('#multiple-confirm-approve').click(function () {
            $('#multiple-confirm').modal('hide');
            $currentForm.unbind('submit', submitHandler);
            const target = Routing.generate('admin_adv_priv_send');
            const spinner = Spinner.add('#' + $currentForm.attr('name') + '_submit');
                
            const sendHandler = function (e) {
                $.ajax({
                    beforeSend: function () {
                        removeAlerts();
                        Loading.on('stsbl.adv-priv.form');
                        spinner.data('spinner').start();
                    },
                    error: function () {
                        Loading.off('stsbl.adv-priv.form');
                        spinner.data('spinner').stop();
                    
                        Message.error(_('Error during applying changes.'), false, '.output');
                    },
                    success: function () {
                        Loading.off('stsbl.adv-priv.form');
                        spinner.data('spinner').stop();
                        resetForm();
                        smoothScrollToOutput();
                    },
                    url: target,
                    type: 'POST',
                    data: new FormData(this),
                    dataType: 'json',
                    processData: false,
                    contentType: false
                });
                
                e.preventDefault();
                return false;
            };
            
            $currentForm.submit(sendHandler);
            // submit the form
            $currentForm.submit();
            
            // set back handler to default
            $currentForm.unbind('submit', sendHandler);
            $currentForm.submit(submitHandler);
        });
    }
    
    function initialize()
    {
        // Bind AJAX interceptor
        $(document).ajaxSuccess(function (event, xhr, settings) {
            if (typeof xhr.responseJSON !== 'undefined' && typeof xhr.responseJSON.msg !== 'undefined') {
                $.each(xhr.responseJSON.msg, function (k, v) {
                    if (v.type === 'info') {
                        Message.info(v.message, false, '.output');
                    } else if (v.type === 'alert') {
                        Message.warning(v.message, false, '.output');
                    } else if (v.type === 'error') {
                        Message.error(v.message, false, '.output');
                    } else if (v.type === 'success') {
                        Message.success(v.message, false, '.output');
                    }
                });
            }
        });
        
        registerTargetHandler('assign');
        registerTargetHandler('revoke');
        registerTargetHandler('owner');
        registerFormHandler();
    }

    $(document).ready(initialize);
})();
