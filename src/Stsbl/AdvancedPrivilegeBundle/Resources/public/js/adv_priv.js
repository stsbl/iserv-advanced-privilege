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
            currentForm.unbind('submit', submitHandler);
            currentForm.submit();
        });
    }
    
    function initialize()
    {
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
