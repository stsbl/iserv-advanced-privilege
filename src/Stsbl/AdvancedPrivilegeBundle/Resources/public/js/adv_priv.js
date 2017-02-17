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
    function hideAssignPattern()
    {
        $('#multiple-assign-form-group-pattern').hide();
        $('#assign_pattern').prop('required', false);
    }

    function showAssignPattern()
    {
        $('#multiple-assign-form-group-pattern').show();
        $('#assign_pattern').prop('required', true);
    }
    
    function hideRevokePattern()
    {
        $('#multiple-revoke-form-group-pattern').hide();
        $('#revoke_pattern').prop('required', false);
    }

    function showRevokePattern()
    {
        $('#multiple-revoke-form-group-pattern').show();
        $('#revoke_pattern').prop('required', true);
    }

    function hideOwnerPattern()
    {
        $('#multiple-owner-form-group-pattern').hide();
        $('#owner_pattern').prop('required', false);
    }

    function showOwnerPattern()
    {
        $('#multiple-owner-form-group-pattern').show();
        $('#owner_pattern').prop('required', true);
    }
    
    function initialize()
    {
        if ($('#assign_target_0').is(':checked')) {
            $('#multiple-assign-form-group-pattern').hide();
            $('#assign_pattern').prop('required', false);
        }
        
        if ($('#revoke_target_0').is(':checked')) {
            $('#multiple-revoke-form-group-pattern').hide();
            $('#revoke_pattern').prop('required', false);
        }
        
        if ($('#owner_target_0').is(':checked')) {
            $('#multiple-owner-form-group-pattern').hide();
            $('#owner_pattern').prop('required', false);
        }
        
        $('#assign_target_0').change(function () {
            hideAssignPattern();
        });
        $('#assign_target_1').change(function () {
            showAssignPattern();
        });
        $('#assign_target_2').change(function () {
            showAssignPattern();
        });
        $('#assign_target_3').change(function() {
            showAssignPattern();
        });
        $('#assign_target_4').change(function() {
            showAssignPattern();
        });
        
        $('#revoke_target_0').change(function () {
            hideRevokePattern();
        });
        $('#revoke_target_1').change(function () {
            showRevokePattern();
        });
        $('#revoke_target_2').change(function () {
            showRevokePattern();
        });
        $('#revoke_target_3').change(function() {
            showRevokePattern();
        });
        $('#revoke_target_4').change(function() {
            showRevokePattern();
        });
        
        $('#owner_target_0').change(function () {
            hideOwnerPattern();
        });
        $('#owner_target_1').change(function () {
            showOwnerPattern();
        });
        $('#owner_target_2').change(function () {
            showOwnerPattern();
        });
        $('#owner_target_3').change(function() {
            showOwnerPattern();
        });
        $('#owner_target_4').change(function() {
            showOwnerPattern();
        });
    }

    // Public API
    return {
        init: initialize
    };
    
}(IServ));
