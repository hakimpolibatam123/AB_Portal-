<?php
declare(strict_types=1);

namespace App\Controllers;

final class CctvController extends Controller
{
    public function form(): void
    {
        $this->render('cctv/form', [
            'pageTitle' => 'Form Monitoring CCTV',
            'page' => 'form-cctv',
            'navStatus' => 'ONLINE',
            'bodyClass' => 'min-h-screen flex flex-col justify-between antialiased rs-forms',
            'footer' => ['class' => 'w-full py-3 mt-4', 'text' => null],
            'modal' => ['formName' => 'Monitoring CCTV', 'formId' => 'cctvMonitoringForm'],
            'module' => dummy('cctv'),
        ]);
    }

    public function rekap(): void
    {
        $this->render('cctv/rekap', [
            'pageTitle' => 'Rekap Monitoring CCTV',
            'page' => 'rekap-cctv',
            'navStatus' => 'ONLINE',
            'htmlClass' => 'rs-no-scrollbar',
            'bodyClass' => 'antialiased min-h-screen flex flex-col justify-between',
            'footer' => ['class' => 'w-full py-space-md', 'text' => null],
            'flash' => $this->submittedFlash('Monitoring CCTV'),
            'module' => dummy('cctv'),
        ]);
    }

    /** Dummy submit: nothing is stored yet; the form lands on its rekap page. */
    public function submit(): void
    {
        redirect('/rekap/cctv?terkirim=1');
    }
}
