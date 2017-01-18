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
    function hidePattern()
    {
        $('#multiple-assign-form-group-pattern').hide();
        $('#multiple-assign-form-group-pattern').removeAttr('required');
    }

    function showPattern()
    {
        $('#multiple-assign-form-group-pattern').show();
        $('#multiple-assign-form-group-pattern').attr('required', 'required');
    }
    
    function initialize()
    {
        if ($('#form_target_0').is(':checked')) {
            $('#multiple-assign-form-group-pattern').hide();
            $('#multiple-assign-form-group-pattern').removeAttr('required');
        }
        
        $('#form_target_0').change(function () {
            hidePattern()
        });
        $('#form_target_1').change(function () {
            showPattern()
        });
        $('#form_target_2').change(function () {
            showPattern()
        });
        $('#form_target_3').change(function() {
            showPattern()
        });
        $('#form_target_4').change(function() {
            showPattern()
        });
    }

    // Public API
    return {
        init: initialize
    };
    
}(IServ));
