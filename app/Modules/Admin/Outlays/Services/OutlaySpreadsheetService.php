<?php

namespace App\Modules\Admin\Outlays\Services;

use App\Modules\Admin\CashBoxes\Services\DocumentCashFlow;
use App\Modules\Admin\Outlays\Models\Outlay;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OutlaySpreadsheetService
{
    public function generate(Outlay $outlay, Model $partner, string $type, string $templateDirectory, string $output): void
    {
        abort_unless(extension_loaded('gd'), 503, 'Enable the PHP gd extension to generate spreadsheet documents.');
        $filename = match ($type) {
            'estimate' => 'outlay-template.xlsx', 'service-act' => 'act-template.xlsx',
            'specification-1' => 'spetsifikatsia_1.xlsx', 'specification-2' => 'spetsifikatsia_2.xlsx',
        };
        abort_unless(is_file($templateDirectory.'/'.$filename), 503, 'The requested document template is missing.');
        $book = IOFactory::load($templateDirectory.'/'.$filename);
        try {
            $sheet = $book->getActiveSheet();
            $start = $type === 'service-act' ? 12 : 13;
            if ($sheet->getHighestRow() >= $start) {
                $sheet->removeRow($start, $sheet->getHighestRow() - $start + 1);
            }
            $text = fn ($cell, $value) => $sheet->setCellValueExplicit($cell, (string) ($value ?? ''), DataType::TYPE_STRING);
            $date = $outlay->created_at?->format('d.m.Y') ?? now()->format('d.m.Y');
            $display = app(OutlayCounterpartyService::class)->display($partner);
            $partnerText = $display['name'].', '.$display['phone'];
            if ($type === 'estimate') {
                $text('W3', "№ {$outlay->id} от $date");
                $text('B5', "Смета к договору № {$outlay->id} от $date");
                $text('G8', $partnerText);
            } elseif ($type === 'service-act') {
                $text('B3', "Акт об оказании услуг № {$outlay->id} от $date");
                $text('F7', $partnerText);
                $text('F8', "Договор-оферта на оказание услуг № {$outlay->id} от $date");
            } else {
                $text('Q1', ($type === 'specification-1' ? 'Приложение №1' : 'Приложение №2')." к договору аренды № {$outlay->id} от $date");
                $text('B4', "Спецификация к договору № {$outlay->id} от $date");
                $text('B10', $outlay->title.' / '.$partnerText);
            }
            $row = $start;
            $number = 1;
            $lastColumn = $type === 'service-act' ? 'AG' : ($type === 'estimate' ? 'AM' : 'AN');
            foreach ($outlay->groups as $group) {
                foreach ($group->items as $item) {
                    $ranges = $type === 'estimate'
                        ? ['B:C', 'D:T', 'U:V', 'W:Y', 'Z:AC', 'AD:AH', 'AI:AM']
                        : ($type === 'service-act' ? ['B:C', 'D:T', 'U:W', 'X:Y', 'Z:AC', 'AD:AG']
                            : ['B:C', 'D:T', 'U:W', 'X:Y', 'Z:AC', 'AD:AG', 'AH:AJ', 'AK:AN']);
                    foreach ($ranges as $range) {
                        [$left, $right] = explode(':', $range);
                        $sheet->mergeCells($left.$row.':'.$right.$row);
                    }
                    $title = ($group->groupName?->name ? $group->groupName->name.' / ' : '').$item->product_title_snapshot;
                    $text('B'.$row, $number++);
                    $text('D'.$row, $title);
                    if (BigDecimal::of($item->quantity)->isLessThanOrEqualTo(0)) {
                        throw ValidationException::withMessages(['items.'.$item->id.'.quantity' => 'The saved quantity must be positive.']);
                    }
                    $unitNet = BigDecimal::of($item->total_cost)->dividedBy($item->quantity, 2, RoundingMode::HALF_UP);
                    if ($type === 'estimate') {
                        $text('U'.$row, 'шт.');
                        $numbers = ['W' => $item->quantity, 'Z' => (string) $unitNet, 'AD' => $item->total_cost];
                    } elseif ($type === 'service-act') {
                        $text('X'.$row, 'шт.');
                        $numbers = ['U' => $item->quantity, 'Z' => (string) $unitNet, 'AD' => $item->total_cost];
                    } else {
                        $text('U'.$row, 'шт.');
                        $numbers = ['X' => $item->quantity, 'Z' => $item->price, 'AD' => (string) $unitNet, 'AH' => $item->total_cost];
                    }
                    foreach ($numbers as $column => $value) {
                        $sheet->setCellValueExplicit($column.$row, (string) $value, DataType::TYPE_NUMERIC);
                        $sheet->getStyle($column.$row)->getNumberFormat()->setFormatCode('0.00');
                    }
                    $sheet->getStyle('B'.$row.':'.$lastColumn.$row)->applyFromArray([
                        'font' => ['name' => 'Arial', 'size' => 10],
                        'alignment' => ['wrapText' => true, 'vertical' => 'top'],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(max(28, min(120, 16 * (int) ceil(mb_strlen($title) / 50))));
                    $row++;
                }
            }
            $row++;
            $footer = [
                'Итого: '.$outlay->total_cost.' руб.',
                'Предоплата: '.($outlay->prepayment ? $outlay->prepayment_amount : '0.00').' руб.',
                'Финальная оплата по смете: '.DocumentCashFlow::money(DocumentCashFlow::cents($outlay->total_cost) - ($outlay->prepayment ? DocumentCashFlow::cents($outlay->prepayment_amount) : 0)).' руб.',
                'Дата заказа: '.$outlay->execution_date?->format('d.m.Y H:i').' / '.$outlay->date_comment,
                'Дата возврата: '.$outlay->return_date?->format('d.m.Y H:i').' / '.$outlay->return_date_comment,
                'Место оказания услуг: '.$outlay->address,
                'Исполнитель ____________________     Заказчик ____________________',
            ];
            foreach ($footer as $line) {
                $sheet->mergeCells('B'.$row.':'.$lastColumn.$row);
                $text('B'.$row, $line);
                $sheet->getStyle('B'.$row)->getAlignment()->setWrapText(true);
                $sheet->getRowDimension($row)->setRowHeight(30);
                $row++;
            }
            $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0)->setPrintArea('B1:'.$lastColumn.$row);
            (new Xlsx($book))->save($output);
        } finally {
            $book->disconnectWorksheets();
        }
    }
}
