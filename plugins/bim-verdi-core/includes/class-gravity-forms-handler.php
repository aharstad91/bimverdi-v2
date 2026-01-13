<?php
/**
 * Gravity Forms Handler - Deprecated
 * 
 * This file is kept for backwards compatibility only.
 * All Gravity Forms handlers have been refactored into individual handler classes:
 * 
 * - BIM_Verdi_Tool_Form_Handler (Registrer verktøy)
 * - BIM_Verdi_Company_Form_Handler (Bedriftsregistrering)
 * 
 * These are now orchestrated by BIM_Verdi_Gravity_Forms_Manager
 * located in: /includes/class-gravity-forms-manager.php
 * 
 * @package BIM_Verdi_Core
 * @version 2.1.0
 * @deprecated 2.1.0 Use BIM_Verdi_Gravity_Forms_Manager and individual handlers instead
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Deprecated: BIM_Verdi_Gravity_Forms_Handler
 * 
 * This class is deprecated. Use individual handler classes instead:
 * - BIM_Verdi_Tool_Form_Handler (in handlers/class-tool-form-handler.php)
 * - BIM_Verdi_Company_Form_Handler (in handlers/class-company-form-handler.php)
 * 
 * @deprecated 2.1.0
 */
class BIM_Verdi_Gravity_Forms_Handler {
    
    /**
     * This class is deprecated as of v2.1.0
     * 
     * All functionality has been refactored into individual handler classes:
     * 
     * - BIM_Verdi_Tool_Form_Handler (in handlers/class-tool-form-handler.php)
     *   Handles: "Registrer verktøy" form (Form ID 1)
     *   
     * - BIM_Verdi_Company_Form_Handler (in handlers/class-company-form-handler.php)
     *   Handles: Company registration form (Form ID 999)
     * 
     * These handlers are now orchestrated by:
     * - BIM_Verdi_Gravity_Forms_Manager (in class-gravity-forms-manager.php)
     * 
     * This separation improves:
     * - Code maintainability: Each form has its own focused handler
     * - Testing: Individual handlers can be tested in isolation
     * - Scalability: New handlers can be added by creating new files
     * - Debugging: Easier to locate logic for specific forms
     * 
     * @deprecated 2.1.0
     * @see BIM_Verdi_Gravity_Forms_Manager
     * @see BIM_Verdi_Tool_Form_Handler
     * @see BIM_Verdi_Company_Form_Handler
     */
}
