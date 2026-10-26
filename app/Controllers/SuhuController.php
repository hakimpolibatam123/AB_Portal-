<?php
declare(strict_types=1);

namespace App\Controllers;

final class SuhuController extends Controller
{
    private const SHELL = [
        'documentClass' => 'bg-[#f8f9ff] text-[#0b1c30]',
        'bodyClass' => 'antialiased rs-forms',
        'footer' => ['class' => 'text-center py-8'],
    ];

    public function form(): void
    {
        $this->render('suhu/form', self::SHELL + [
            'pageTitle' => 'Form Monitoring Suhu Server',
            'page' => 'form-suhu',
            'modal' => ['formName' => 'Suhu Server', 'formId' => 'monitoringForm'],
            'module' => dummy('suhu'),
        ]);
    }

    public function rekap(): void
    {
        $this->render('suhu/rekap', self::SHELL + [
            'pageTitle' => 'Rekap Suhu Server',
            'page' => 'rekap-suhu',
            'flash' => $this->submittedFlash('Suhu Server'),
            'module' => dummy('suhu'),
        ]);
    }

    /** Dummy submit: nothing is stored yet; the form lands on its rekap page. */
    public function submit(): void
    {
        redirect('/rekap/suhu?terkirim=1');
    }
}
