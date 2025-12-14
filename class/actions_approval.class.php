<?php
class ActionsApproval
{
    /**
     * Overloading the addMoreActionsButtons function
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice view, an order if you are in order view, ...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $db, $user;
        $error = 0;

        if (in_array('invoicecard', $parameters['context'])) {
             // Check if module enabled and user has permission
             if (!empty($conf->approval->enabled) && $user->rights->approval->approval->read) {
                 // Add button
                 // Link to approval_invoice.php?index=OBJECT_ID
                 // Check if invoice is validated? Usually you send validated invoices.
                 if ($object->statut == 1 || $object->statut == 2) { // Validated or Payment started
                     print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/approval/approval_invoice.php', 1).'?index='.$object->id.'">'.$langs->trans("SendToSRI").'</a></div>';
                 }
             }
        }

        return 0;
    }
}
