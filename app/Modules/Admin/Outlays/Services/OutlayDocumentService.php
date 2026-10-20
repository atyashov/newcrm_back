<?php

namespace App\Modules\Admin\Outlays\Services;

use App\Modules\Admin\Outlays\Models\Outlay;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OutlayDocumentService
{
    public const TYPES = [
        'return-act' => ['title' => 'Акт приема-передачи', 'format' => 'docx'],
        'lease-contract' => ['title' => 'Договор аренды', 'format' => 'docx'],
        'specification-1' => ['title' => 'Спецификация 1', 'format' => 'xlsx'],
        'specification-2' => ['title' => 'Спецификация 2', 'format' => 'xlsx'],
        'contract' => ['title' => 'Договор-оферта', 'format' => 'docx'],
        'estimate' => ['title' => 'Смета', 'format' => 'xlsx'],
        'service-act' => ['title' => 'Акт оказания услуг', 'format' => 'xlsx'],
        'estimate-pdf' => ['title' => 'Смета PDF', 'format' => 'pdf'],
    ];

    public function catalog(Outlay $outlay): array
    {
        return collect(self::TYPES)->map(fn ($data, $type) => [
            'type' => $type, ...$data,
            'url' => route('api.outlays.documents.download', ['outlay' => $outlay->id, 'type' => $type]),
        ])->values()->all();
    }

    public function generate(Outlay $outlay, string $type): string
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        $format = self::TYPES[$type]['format'];
        abort_unless(class_exists(\ZipArchive::class) || $format === 'pdf', 503, 'Enable the PHP zip extension to generate Office documents.');
        $positionCount = DB::table('outlay_items')->join('outlay_groups', 'outlay_groups.id', '=', 'outlay_items.outlay_group_id')
            ->where('outlay_groups.outlay_id', $outlay->id)->count();
        if ($positionCount > 5000) {
            throw ValidationException::withMessages(['groups' => 'At most 5000 positions can be exported in one document.']);
        }
        $outlay->loadMissing(['groups.groupName', 'groups.items', 'subdivision']);
        $output = $this->newPath($format);
        try {
            if ($format === 'pdf') {
                $this->writePdf([$outlay], $output);
            } else {
                $partner = app(OutlayCounterpartyService::class)->resolve($outlay);
                if ($format === 'docx') {
                    app(OutlayWordService::class)->generate($outlay, $partner, $type, resource_path('document-templates/outlays'), $output);
                } else {
                    app(OutlaySpreadsheetService::class)->generate($outlay, $partner, $type, resource_path('document-templates/outlays'), $output);
                }
            }
        } catch (\Throwable $error) {
            File::delete($output);
            throw $error;
        }

        return $output;
    }

    public function periodPdf($outlays): string
    {
        $output = $this->newPath('pdf');
        try {
            $this->writePdf($outlays, $output);
        } catch (\Throwable $error) {
            File::delete($output);
            throw $error;
        }

        return $output;
    }

    public function download(string $path, string $filename): BinaryFileResponse
    {
        return response()->download($path, $filename, [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }

    private function newPath(string $format): string
    {
        $directory = storage_path('app/private/outlay-documents');
        File::ensureDirectoryExists($directory);

        return $directory.'/'.Str::uuid().'.'.$format;
    }

    private function writePdf($outlays, string $output): void
    {
        $options = new Options(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false, 'defaultFont' => 'DejaVu Sans']);
        $pdf = new Dompdf($options);
        $pdf->setPaper('A4');
        $pdf->loadHtml(view('documents.outlay', ['outlays' => $outlays])->render(), 'UTF-8');
        $pdf->render();
        File::put($output, $pdf->output());
    }
}
