<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\DashboardData;

/**
 * "Edit Isi Form" (GET /form-editor?id=…), frontend only: tabs Pertanyaan and Jadwal. The form lives in
 * the browser (public/assets/js/dashboard-store.js, dummy data + sessionStorage), so the page only
 * gets the start state; public/assets/js/form-editor.js draws and saves the form in the browser.
 */
final class FormEditorController extends Controller
{
    private const ICONS = [
        'align-left', 'text', 'circle-dot', 'square-check-big', 'chevron-down', 'file-up', 'sliders-horizontal', 'hash', 'star',
        'calendar', 'clock', 'copy', 'trash-2', 'image', 'image-plus', 'circle-plus', 'type', 'rows-3', 'arrow-up', 'arrow-down',
        'plus', 'x', 'save', 'list-checks', 'calendar-days', 'info', 'circle-alert',
        'calendar-range', 'calendar-plus', 'calendar-check', 'chevron-left', 'chevron-right',
    ];

    public function edit(): void
    {
        $id = (string) ($_GET['id'] ?? '');
        if (preg_match('/^[a-z0-9-]{1,64}$/', $id) !== 1) {
            redirect('/');
            return;
        }
        $data = dummy('dashboard');
        $this->render('form-editor/index', [
            'pageTitle' => 'Edit Isi Form',
            'portalPage' => true,
            'backToDashboard' => true,
            'formId' => $id,
            'clientState' => DashboardData::clientState($data),
            'iconNames' => DashboardData::iconNames($data, self::ICONS),
            'pageScripts' => ['js/dashboard-store.js', 'js/form-editor.js'],
        ], 'main');
    }
}
