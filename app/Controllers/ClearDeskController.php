<?php
declare(strict_types=1);

namespace App\Controllers;

final class ClearDeskController extends Controller
{
    private function shell(): array
    {
        return [
            'htmlClass' => 'rs-no-scrollbar',
            'navStatus' => 'ONLINE',
            'bodyClass' => 'text-body-md antialiased',
            'footer' => ['class' => 'w-full py-space-md', 'innerClass' => 'w-full px-margin text-center text-on-surface-variant text-body-sm', 'text' => '© ' . date('Y') . ' RS Awal Bros Botania. All rights reserved.'],
        ];
    }

    public function form(): void
    {
        $this->render('cleardesk/form', $this->shell() + [
            'pageTitle' => 'Form Clear Desk & Clear Screen',
            'page' => 'form-cleardesk',
            'modal' => ['formName' => 'Clear Desk & Screen', 'formId' => 'clearDeskForm'],
            'module' => dummy('cleardesk'),
        ]);
    }

    public function rekap(): void
    {
        $this->render('cleardesk/rekap', $this->shell() + [
            'pageTitle' => 'Rekap Clear Desk & Clear Screen',
            'page' => 'rekap-cleardesk',
            'flash' => $this->submittedFlash('Clear Desk & Screen'),
            'module' => dummy('cleardesk'),
        ]);
    }

    /** Dummy submit: nothing is stored yet; the form lands on its rekap page. */
    public function submit(): void
    {
        redirect('/rekap/cleardesk?terkirim=1');
    }
}
