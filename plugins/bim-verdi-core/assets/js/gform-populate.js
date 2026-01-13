/**
 * Populate Gravity Form 4 with user profile data
 * 
 * This script fills Form 4 fields with current user's profile data
 * Runs after form is rendered in DOM
 */

jQuery(document).ready(function($) {
    
    // Wait for Gravity Forms to render
    if (typeof gform !== 'undefined') {
        
        // Populate Form 4
        gform.addAction('gform_post_render', function() {
            console.log('BIM - Populating Form 4...');
            
            // Field mapping: Form Field ID => Data Value
            var fieldMap = {
                1: bimGformData.firstName,      // First Name
                2: bimGformData.lastName,       // Last Name
                3: bimGformData.userEmail,      // Email
                4: bimGformData.phone,          // Phone
                5: bimGformData.jobTitle,       // Job Title
                6: bimGformData.linkedinUrl,    // LinkedIn URL
            };
            
            // Populate each field
            $.each(fieldMap, function(fieldId, value) {
                if (value) {
                    var selector = '#input_4_' + fieldId;
                    $(selector).val(value).trigger('change');
                    console.log('BIM - Set field ' + fieldId + ' to: ' + value);
                }
            });
            
        }, 10);
        
    } else {
        console.log('BIM - Gravity Forms not loaded yet, waiting...');
        setTimeout(arguments.callee, 100);
    }
});
