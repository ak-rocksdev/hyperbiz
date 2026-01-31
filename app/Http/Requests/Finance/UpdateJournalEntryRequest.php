<?php

namespace App\Http\Requests\Finance;

use App\Models\FiscalPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateJournalEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $entry = $this->route('entry');
        return $this->user()->can('finance.journal_entries.edit') && $entry->canEdit();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'entry_date' => ['required', 'date'],
            'fiscal_period_id' => ['required', 'exists:fin_fiscal_periods,id'],
            'memo' => ['nullable', 'string', 'max:255'],
            'currency_code' => ['required', 'string', 'size:3'],
            'exchange_rate' => ['required', 'numeric', 'gt:0'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:fin_chart_of_accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.debit_amount' => ['required', 'numeric', 'gte:0', 'decimal:0,2'],
            'lines.*.credit_amount' => ['required', 'numeric', 'gte:0', 'decimal:0,2'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateBalance($validator);
            $this->validateFiscalPeriod($validator);
            $this->validateLineAmounts($validator);
        });
    }

    /**
     * Validate that debits equal credits.
     */
    protected function validateBalance(Validator $validator): void
    {
        $lines = $this->input('lines', []);
        $totalDebit = '0';
        $totalCredit = '0';

        foreach ($lines as $line) {
            $totalDebit = bcadd($totalDebit, (string) ($line['debit_amount'] ?? 0), 2);
            $totalCredit = bcadd($totalCredit, (string) ($line['credit_amount'] ?? 0), 2);
        }

        if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
            $validator->errors()->add(
                'lines',
                'Journal entry must be balanced. Total debits (' . $totalDebit . ') must equal total credits (' . $totalCredit . ').'
            );
        }
    }

    /**
     * Validate fiscal period is open and entry date is within period.
     */
    protected function validateFiscalPeriod(Validator $validator): void
    {
        $periodId = $this->input('fiscal_period_id');
        $entryDate = $this->input('entry_date');

        if (!$periodId || !$entryDate) {
            return;
        }

        $period = FiscalPeriod::find($periodId);

        if (!$period) {
            return;
        }

        // Check if period is open for posting
        if (!in_array($period->status, ['open', 'adjusting'])) {
            $validator->errors()->add(
                'fiscal_period_id',
                'The selected fiscal period is not open for posting.'
            );
            return;
        }

        // Check if entry date is within period
        $date = Carbon::parse($entryDate);
        if ($date->lt($period->start_date) || $date->gt($period->end_date)) {
            $validator->errors()->add(
                'entry_date',
                'Entry date must be within the selected fiscal period (' .
                $period->start_date->format('M d, Y') . ' - ' .
                $period->end_date->format('M d, Y') . ').'
            );
        }
    }

    /**
     * Validate that each line has either debit or credit (not both).
     */
    protected function validateLineAmounts(Validator $validator): void
    {
        $lines = $this->input('lines', []);

        foreach ($lines as $index => $line) {
            $debit = (float) ($line['debit_amount'] ?? 0);
            $credit = (float) ($line['credit_amount'] ?? 0);

            // Line must have either debit or credit, not both
            if ($debit > 0 && $credit > 0) {
                $validator->errors()->add(
                    "lines.{$index}",
                    'Line ' . ($index + 1) . ' cannot have both debit and credit amounts.'
                );
            }
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'entry_date.required' => 'The entry date is required.',
            'fiscal_period_id.required' => 'Please select a fiscal period.',
            'fiscal_period_id.exists' => 'The selected fiscal period does not exist.',
            'currency_code.size' => 'Currency code must be exactly 3 characters.',
            'exchange_rate.gt' => 'Exchange rate must be greater than zero.',
            'lines.required' => 'Journal entry must have at least one line.',
            'lines.min' => 'Journal entry must have at least 2 lines.',
            'lines.*.account_id.required' => 'Each line must have an account selected.',
            'lines.*.account_id.exists' => 'The selected account does not exist.',
            'lines.*.debit_amount.gte' => 'Debit amount cannot be negative.',
            'lines.*.credit_amount.gte' => 'Credit amount cannot be negative.',
        ];
    }
}
