<?php
/* Copyright (C) 2021-2024 Maxim Maximovich Isaev <isayev95117@gmail.com>
*/
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    if (!$conf->approval->enabled || !isset($_SESSION['idmenu'])) header("Location: ../../../index.php");
    if (isset($_FILES['keyfile']['tmp_name']) && trim($_FILES['keyfile']['tmp_name'])) {
        $newfile = DOL_DOCUMENT_ROOT . '/custom/approval/Key.pem';
        $file_OK = is_uploaded_file($_FILES['keyfile']['tmp_name']);
        if (file_exists($newfile)) {
            $file = DOL_DOCUMENT_ROOT . '/custom/approval/Key_'.date('Y-m-d').',pem';
            @rename($newfile, $file);
        }
        $result = dol_move_uploaded_file($_FILES['keyfile']['tmp_name'], $newfile, 1, 0, $_FILES['keyfile']['error']);
        if (!$result > 0) {
            setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
            $mesg = 'No se pudo cargar el archivo';
            exitHandler($mesg, 'errors', 1);
        } else {
            $mesg = 'Has subido correctamente el archivo Key.pem';
            exitHandler($mesg, 'mesgs', 1);
        }

    }

    if (isset($_FILES['certfile']['tmp_name']) && trim($_FILES['certfile']['tmp_name'])) {
        $newfile = DOL_DOCUMENT_ROOT . '/custom/approval/Cert.pem';
        $file_OK = is_uploaded_file($_FILES['certfile']['tmp_name']);
        if (file_exists($newfile)) {
            $file = DOL_DOCUMENT_ROOT . '/custom/approval/Cert_'.date('Y-m-d').',pem';
            @rename($newfile, $file);
        }
        $result = dol_move_uploaded_file($_FILES['certfile']['tmp_name'], $newfile, 1, 0, $_FILES['certfile']['error']);
                
        if (!$result > 0) {
            setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
            $mesg = 'No se pudo cargar el archivo';
            exitHandler($mesg, 'errors', 1);
        } else {
            $mesg = 'Ha cargado correctamente el archivo Cert.pem';
            exitHandler($mesg, 'mesgs', 1);
        }
    } 

    header("Location: setup.php");
    
function exitHandler($mesg, $style, $event)
{
    $_SESSION['dol_events'][$style][] = $mesg;
}