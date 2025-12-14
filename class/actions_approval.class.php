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

        $contexts = isset($parameters['context']) ? $parameters['context'] : $hookmanager->contextList;
        if (!is_array($contexts)) $contexts = array($contexts);

        if (in_array('invoicecard', $contexts)) {
             if (!empty($conf->approval->enabled) && $user->rights->approval->approval->read) {
                 if ($object->statut == 1 || $object->statut == 2) {
                     print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/approval/approval_invoice.php', 1).'?index='.$object->id.'">'.$langs->trans("SendToSRI").'</a></div>';
                 }
             }
        }

        if (in_array('invoice_supplier_card', $contexts)) {
             if (!empty($conf->approval->enabled) && $user->rights->approval->approval->read) {
                 if ($object->fk_statut == 1) {
                     print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/approval/approval_vendor.php', 1).'?index='.$object->id.'">'.$langs->trans("SendToSRI").'</a></div>';
                 }
             }
        }

        if (in_array('shippingcard', $contexts)) {
             if (!empty($conf->approval->enabled) && $user->rights->approval->approval->read) {
                 if ($object->statut == 1) {
                     $linked_invoice_id = 0;
                     $sql = "SELECT fk_source FROM ".MAIN_DB_PREFIX."element_element WHERE fk_target = ".$object->id." AND targettype = 'shipping' AND sourcetype = 'facture'";
                     $res = $db->query($sql);
                     if ($res && $db->num_rows($res) > 0) {
                         $obj_linked = $db->fetch_object($res);
                         $linked_invoice_id = $obj_linked->fk_source;
                     }

                     if ($linked_invoice_id) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/approval/approval_order.php', 1).'?eid='.$object->id.'&in='.$linked_invoice_id.'&index='.$user->id.'">'.$langs->trans("SendToSRI").'</a></div>';
                     }
                 }
             }
        }

        return 0;
    }

    /**
     * Overloading the formObjectOptions function
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice view, an order if you are in order view, ...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        $contexts = isset($parameters['context']) ? $parameters['context'] : $hookmanager->contextList;
        if (!is_array($contexts)) $contexts = array($contexts);

        // For Supplier Order Creation: Show inputs to import from XML/SRI
        if (in_array('order_supplier_card', $contexts) && $action == 'create') {
             if (!empty($conf->approval->enabled) && $user->rights->approval->approval->read) {
                 $url_xml = dol_buildpath('/approval/approval_xml.php', 1);

                 print '<script type="text/javascript">
                    function submitApprovalXml() {
                        var clave = jQuery("#approval_claveacceso").val();
                        var fileInput = jQuery("#approval_addedfile")[0];
                        var file = fileInput.files[0];

                        if (!clave && !file) {
                            alert("'.$langs->trans("ErrorFieldRequired").'");
                            return;
                        }

                        var form = document.createElement("form");
                        form.method = "POST";
                        form.action = "'.$url_xml.'";
                        form.enctype = "multipart/form-data";

                        var inputClave = document.createElement("input");
                        inputClave.type = "hidden";
                        inputClave.name = "claveacceso";
                        inputClave.value = clave;
                        form.appendChild(inputClave);

                        if (file) {
                             // Cloning file input to preserve file selection
                             var inputFile = fileInput.cloneNode(true);
                             inputFile.name = "addedfile";
                             form.appendChild(inputFile);
                        }

                        document.body.appendChild(form);
                        form.submit();
                    }
                 </script>';

                 print '<tr><td class="tdtop">'.$langs->trans("ImportFromSRI").'</td>';
                 print '<td>';
                 // Inputs without form tag to avoid nesting issues
                 print $langs->trans("ClaveAcceso").': <input type="text" id="approval_claveacceso" name="approval_claveacceso_dummy" size="50"> ';
                 print $langs->trans("OrXMLFile").': <input type="file" id="approval_addedfile" name="approval_addedfile_dummy"> ';
                 print '<input type="button" class="button" value="'.$langs->trans("Import").'" onclick="submitApprovalXml()">';
                 print '</td></tr>';
             }
        }

        return 0;
    }
}
