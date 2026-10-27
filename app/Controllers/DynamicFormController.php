<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\DashboardData;

/**
 * Dynamic forms made on the dashboard, frontend only: the fill-in page (GET /form-dinamis?id=…) and its
 * responses (GET /form-dinamis/respons?id=…). The form and its responses live in the browser
 * (public/assets/js/dashboard-store.js: dummy data + sessionStorage); public/assets/js/form-dinamis.js
 * draws both pages and keeps a submitted answer in the browser. Nothing is sent to the server.
 */
final class DynamicFormController extends Controller
{
    private const ICONS = ['send', 'arrow-left', 'calendar', 'clock', 'file-up', 'star', 'check', 'circle-check', 'circle-alert', 'table-2', 'plus', 'x', 'list-checks', 'info'];

    public function fill(): void
    {
        $this->page('form-dinamis/fill', 'Isi Form');
    }

    public function responses(): void
    {
        $this->page('form-dinamis/respons', 'Rekap Respons');
    }

    private function page(string $view, string $title): void
    {
        $id = (string) ($_GET['id'] ?? '');
        if (preg_match('/^[a-z0-9-]{1,64}$/', $id) !== 1) {
            redirect('/');
            return;
        }
        $data = dummy('dashboard');
        $this->render($view, [
            'pageTitle' => $title,
            'portalPage' => true,
            'backToDashboard' => true,
            'formId' => $id,
            'clientState' => DashboardData::clientState($data),
            'iconNames' => DashboardData::iconNames($data, self::ICONS),
            'pageScripts' => ['js/dashboard-store.js', 'js/form-dinamis.js'],
        ], 'main');
    }
}
