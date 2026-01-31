<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $expense = $this->route('expense');
        return $this->user()->can('finance.expenses.edit') && $expense->canEdit();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'expense_date' => ['required', 'date'],
            'category_id' => ['required', 'exists:mst_expense_categories,id'],
            'account_id' => ['required', 'exists:fin_chart_of_accounts,id'],
            'paid_from_account_id' => ['nullable', 'exists:fin_chart_of_accounts,id'],
            'supplier_id' => ['nullable', 'exists:mst_customers,id'],
            'payee_name' => ['nullable', 'string', 'max:100'],
            'currency_code' => ['required', 'string', 'size:3'],
            'exchange_rate' => ['required', 'numeric', 'gt:0'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'tax_amount' => ['nullable', 'numeric', 'gte:0', 'decimal:0,2'],
            'payment_method' => ['nullable', Rule::in(['cash', 'bank_transfer', 'credit_card', 'check', 'other'])],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_recurring' => ['boolean'],
            'recurring_frequency' => ['nullable', Rule::in(['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])],
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['integer', 'exists:fin_expense_attachments,id'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'expense_date.required' => 'The expense date is required.',
            'category_id.required' => 'Please select an expense category.',
            'category_id.exists' => 'The selected category does not exist.',
            'account_id.required' => 'Please select an expense account.',
            'account_id.exists' => 'The selected account does not exist.',
            'currency_code.size' => 'Currency code must be exactly 3 characters.',
            'exchange_rate.gt' => 'Exchange rate must be greater than zero.',
            'amount.required' => 'The expense amount is required.',
            'amount.gt' => 'The expense amount must be greater than zero.',
            'amount.decimal' => 'The amount can have at most 2 decimal places.',
            'tax_amount.gte' => 'Tax amount cannot be negative.',
            'attachments.*.max' => 'Each attachment must be less than 10MB.',
            'attachments.*.mimes' => 'Attachments must be PDF, Word, Excel, or image files.',
        ];
    }
}
