/* 
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
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

IServ.AdvancedPrivilege = {};

IServ.AdvancedPrivilege.Form = IServ.register(function(IServ) {
    "use strict";
    
    var currentForm;
    
    function smoothScrollToOutput()
    {
        $('html, body').animate({
            scrollTop: $('.output').offset().top
        }, 1000);
    }
    
    function resetForm()
    {
        currentForm[0].reset();
        $('#' + currentForm.attr('name') + '_owner').select2('val', '');
        $('#' + currentForm.attr('name') + '_privileges').select2('val', '');
        $('#' + currentForm.attr('name') + '_flags').val('val', '');
        showPattern(currentForm.attr('name'));
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
        var submitHandler = function(e) {
            $('#multiple-confirm').modal('show');
            
            currentForm = $(this);
            
            e.preventDefault();
            return false;
        };
        
        $('form').submit(submitHandler);
        
        $('#multiple-confirm-approve').click(function () {
            $('#multiple-confirm').modal('hide');
            currentForm.unbind('submit', submitHandler);
            var target = IServ.Routing.generate('admin_adv_priv_send');
            var spinner = IServ.Spinner.add('#' + currentForm.attr('name') + '_submit');
                
            var sendHandler = function(e) {
                $.ajax({
                    beforeSend: function() {
                        IServ.Loading.on('stsbl.adv-priv.form');
                        spinner.data('spinner').start();
                    },
                    error: function() {
                        IServ.Loading.off('stsbl.adv-priv.form');
                        spinner.data('spinner').stop();
                    
                        IServ.Message.error(_('Error during applying changes.'), false, '.output');
                    },
                    success: function() {    
                        IServ.Loading.off('stsbl.adv-priv.form');
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
            
            currentForm.submit(sendHandler);
            // submit the form
            currentForm.submit();
            
            // set back handler to default
            currentForm.unbind('submit', sendHandler);
            currentForm.submit(submitHandler);
        });
    }
    
    function initialize()
    {
        // Bind AJAX interceptor
        $(document).ajaxSuccess(function(event, xhr, settings) {
            if (typeof xhr.responseJSON !== 'undefined' && typeof xhr.responseJSON.msg !== 'undefined') {
                $.each(xhr.responseJSON.msg, function(k, v) {
                    if (v.type === 'info') {
                        IServ.Message.info(v.message, false, '.output');
                    } else if (v.type === 'alert') {
                        IServ.Message.warning(v.message, false, '.output');
                    } else if (v.type === 'error') {
                        IServ.Message.error(v.message, false, '.output');
                    } else if (v.type === 'success') {
                        IServ.Message.success(v.message, false, '.output');
                    }
                });
            }
        });
        
        registerTargetHandler('assign');
        registerTargetHandler('revoke');
        registerTargetHandler('owner');
        registerFormHandler();
    }

    // Public API
    return {
        init: initialize
    };
    
}(IServ));
