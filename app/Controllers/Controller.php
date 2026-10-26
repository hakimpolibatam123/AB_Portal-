<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

abstract class Controller
{
    /** Render a page; outside local the portal stays closed until authentication is implemented. */
    protected function render(string $template, array $data = [], string $layout = 'main'): void
    {
        if (config('app')['environment'] !== 'local') {
            http_response_code(503);
            View::render('errors/unavailable', ['pageTitle' => 'Portal belum tersedia']);
            return;
        }
        View::render($template, $data, $layout);
    }

    /** Toast shown after a form has been sent to its rekap page (see submit()). */
    protected function submittedFlash(string $formName): ?array
    {
        return ($_GET['terkirim'] ?? '') === '1'
            ? ['title' => 'Laporan Berhasil Terkirim!', 'description' => 'Data ' . $formName . ' telah tersimpan ke rekapitulasi audit.']
            : null;
    }
}
