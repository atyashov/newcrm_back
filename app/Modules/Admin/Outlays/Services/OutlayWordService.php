<?php

namespace App\Modules\Admin\Outlays\Services;

use App\Modules\Admin\Clients\Models\Individual;
use App\Modules\Admin\Clients\Models\LegalEntity;
use App\Modules\Admin\Outlays\Models\Outlay;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

class OutlayWordService
{
    public function generate(Outlay $outlay, Model $partner, string $type, string $templateDirectory, string $output): void
    {
        if ($type === 'return-act' && ! $partner instanceof LegalEntity) {
            throw ValidationException::withMessages(['client_id' => 'The legacy return act is available only for a legal entity.']);
        }
        if ($type === 'contract' && ! $partner instanceof Individual) {
            throw ValidationException::withMessages(['client_id' => 'The offer contract template is available only for an individual.']);
        }
        $template = match ($type) {
            'return-act' => 'akt_priema_vozvrata.docx',
            'contract' => 'outlay-contract-template.docx',
            'lease-contract' => $partner instanceof Individual ? 'Arenda_IP_fiz_l_1.docx' : 'dogovor_arendy.docx',
        };
        $required = $type === 'return-act' ? ['organization_name', 'contact_name']
            : ($partner instanceof Individual
                ? ['name', 'phone', 'birthday', 'passport_serial', 'passport_number', 'date_of_passport', 'issued_by', 'registration']
                : ['organization_name', 'contact_name', 'tin', 'ogrn', 'legal_address', 'bank_name', 'bik', 'cpr', 'k_s', 'r_s', 'phone']);
        $errors = [];
        foreach ($required as $field) {
            if ($partner->getAttribute($field) === null || $partner->getAttribute($field) === '') {
                $errors['client.'.$field] = 'This counterparty field is required for the selected template.';
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
        abort_unless(is_file($templateDirectory.'/'.$template), 503, 'The requested document template is missing.');
        Settings::setOutputEscapingEnabled(true);
        $processor = new TemplateProcessor($templateDirectory.'/'.$template);
        $values = [
            'current_year' => now()->format('Y'), 'outlay_number' => $outlay->id,
            'outlay_create_date' => $outlay->created_at?->format('d.m.Y'),
            'partner_full_name' => $partner instanceof Individual ? $partner->name : $partner->contact_name,
            'partner_name' => $partner instanceof Individual ? $partner->name : $partner->contact_name,
            'partner_organisation_name' => $partner->organization_name,
            'partner_organization_name' => $partner->organization_name,
            'partner_date_birth' => $partner->birthday ? CarbonImmutable::parse($partner->birthday)->format('d.m.Y') : null,
            'partner_birthday' => $partner->birthday ? CarbonImmutable::parse($partner->birthday)->format('d.m.Y') : null,
            'partner_passport_series' => $partner->passport_serial,
            'partner_passport_id' => $partner->passport_number, 'partner_passport_number' => $partner->passport_number,
            'partner_passport_issued_by' => $partner->issued_by,
            'partner_passport_registration' => $partner->registration,
            'partner_passport_issue_date' => $partner->date_of_passport ? CarbonImmutable::parse($partner->date_of_passport)->format('d.m.Y') : null,
            'partner_address' => $partner->registration,
            'partner_phone' => $partner->mobile_phone ?: $partner->phone,
            'partner_tin' => $partner->tin, 'partner_orgn' => $partner->ogrn,
            'partner_legal_address' => $partner->legal_address, 'partner_bank_name' => $partner->bank_name,
            'partner_bic' => $partner->bik, 'partner_cpr' => $partner->cpr,
            'partner_k_s' => $partner->k_s, 'partner_r_s' => $partner->r_s,
        ];
        foreach ($processor->getVariables() as $variable) {
            if (! array_key_exists($variable, $values)) {
                throw ValidationException::withMessages(['template' => 'Unsupported template variable: '.$variable]);
            }
            $processor->setValue($variable, (string) ($values[$variable] ?? ''));
        }
        $processor->saveAs($output);
    }
}
