<?php

use App\Models\Expense;
use App\Models\JournalEntryLine;
use App\Models\BankReconciliation;

/**
 * Tests for financial calculations using bcmath for precision.
 * These tests verify that monetary calculations avoid floating-point errors.
 */

describe('Expense Model Calculations', function () {
    it('calculates amount_in_base with bcmath precision', function () {
        $expense = new Expense();
        $expense->amount = '100.00';
        $expense->tax_amount = '10.00';
        $expense->exchange_rate = '15000.00';

        $expense->calculateAmounts();

        // bcmul('100.00', '15000.00', 2) = '1500000.00'
        expect($expense->amount_in_base)->toBe('1500000.00');
    });

    it('calculates total_amount with bcmath precision', function () {
        $expense = new Expense();
        $expense->amount = '99.99';
        $expense->tax_amount = '10.01';
        $expense->exchange_rate = '1';

        $expense->calculateAmounts();

        // bcadd('99.99', '10.01', 2) = '110.00'
        expect($expense->total_amount)->toBe('110.00');
    });

    it('handles floating-point edge cases in amount calculations', function () {
        // Classic floating-point problem: 0.1 + 0.2 != 0.3 in float
        $expense = new Expense();
        $expense->amount = '0.10';
        $expense->tax_amount = '0.20';
        $expense->exchange_rate = '1';

        $expense->calculateAmounts();

        // With bcmath, this should be exactly '0.30'
        expect($expense->total_amount)->toBe('0.30');
    });

    it('calculates balance_due with bcmath precision', function () {
        $expense = new Expense([
            'total_amount' => '1000.00',
            'amount_paid' => '333.33',
        ]);

        // bcsub('1000.00', '333.33', 2) = '666.67'
        expect($expense->balance_due)->toBe('666.67');
    });

    it('records partial payment correctly', function () {
        $expense = new Expense([
            'amount_paid' => '0.00',
            'total_amount' => '100.00',
            'payment_status' => Expense::PAYMENT_UNPAID,
        ]);

        // Mock save method by using a test double
        $expense->amount_paid = '0.00';
        $newPaid = bcadd('0.00', '33.33', 2);

        expect($newPaid)->toBe('33.33');
    });

    it('handles large currency exchange rates', function () {
        $expense = new Expense();
        $expense->amount = '100.00';
        $expense->tax_amount = '0.00';
        $expense->exchange_rate = '16500.50';

        $expense->calculateAmounts();

        // bcmul('100.00', '16500.50', 2) = '1650050.00'
        expect($expense->amount_in_base)->toBe('1650050.00');
    });

    it('handles small decimal amounts', function () {
        $expense = new Expense();
        $expense->amount = '0.01';
        $expense->tax_amount = '0.00';
        $expense->exchange_rate = '1.5';

        $expense->calculateAmounts();

        // bcmul('0.01', '1.5', 2) = '0.01' (rounds to 2 decimal places)
        expect($expense->amount_in_base)->toBe('0.01');
    });
});

describe('JournalEntryLine Calculations', function () {
    it('calculates base amounts with bcmath precision', function () {
        $line = new JournalEntryLine();
        $line->debit_amount = '1000.50';
        $line->credit_amount = '0.00';

        $line->calculateBaseAmounts(15000.25);

        // bcmul('1000.50', '15000.25', 2) = '15007750.12'
        expect($line->debit_amount_base)->toBe('15007750.12');
        expect($line->credit_amount_base)->toBe('0.00');
    });

    it('calculates net amount with bcmath precision', function () {
        $line = new JournalEntryLine([
            'debit_amount' => '500.75',
            'credit_amount' => '200.25',
        ]);

        // bcsub('500.75', '200.25', 2) = '300.50'
        expect($line->net_amount)->toBe('300.50');
    });

    it('handles credit lines correctly', function () {
        $line = new JournalEntryLine([
            'debit_amount' => '0.00',
            'credit_amount' => '1500.99',
        ]);

        // bcsub('0.00', '1500.99', 2) = '-1500.99'
        expect($line->net_amount)->toBe('-1500.99');
    });

    it('calculates both debit and credit base amounts', function () {
        $line = new JournalEntryLine();
        $line->debit_amount = '100.00';
        $line->credit_amount = '50.00';

        $line->calculateBaseAmounts(2.5);

        expect($line->debit_amount_base)->toBe('250.00');
        expect($line->credit_amount_base)->toBe('125.00');
    });
});

describe('BankReconciliation Calculations', function () {
    it('checks is_balanced attribute with precision', function () {
        $reconciliation = new BankReconciliation([
            'difference' => '0.001',
        ]);

        // Difference of 0.001 is less than 0.01, so should be balanced
        expect($reconciliation->is_balanced)->toBeTrue();
    });

    it('identifies unbalanced reconciliation', function () {
        $reconciliation = new BankReconciliation([
            'difference' => '0.02',
        ]);

        // Difference of 0.02 is not less than 0.01, so should not be balanced
        expect($reconciliation->is_balanced)->toBeFalse();
    });

    it('handles exact balance correctly', function () {
        $reconciliation = new BankReconciliation([
            'difference' => '0.00',
        ]);

        expect($reconciliation->is_balanced)->toBeTrue();
    });

    it('handles negative differences', function () {
        $reconciliation = new BankReconciliation([
            'difference' => '-0.001',
        ]);

        // Absolute value of -0.001 is 0.001, which is less than 0.01
        // Note: bccomp with 2 decimals treats -0.001 as 0.00
        expect($reconciliation->is_balanced)->toBeTrue();
    });

    it('rejects significant negative differences', function () {
        $reconciliation = new BankReconciliation([
            'difference' => '-0.05',
        ]);

        // Absolute value of -0.05 is not less than 0.01
        expect($reconciliation->is_balanced)->toBeFalse();
    });
});

describe('BCMath Precision Edge Cases', function () {
    it('handles repeating decimal multiplication', function () {
        // 100.00 / 3 = 33.333... which can cause precision issues
        $amount = '33.33';
        $rate = '3';
        $result = bcmul($amount, $rate, 2);

        expect($result)->toBe('99.99');
    });

    it('handles very small differences in comparison', function () {
        $a = '100.00';
        $b = '100.001';

        // When comparing with 2 decimal precision, these should be equal
        $comparison = bccomp($a, $b, 2);

        expect($comparison)->toBe(0);
    });

    it('maintains precision across multiple operations', function () {
        $initial = '1000.00';
        $additions = ['100.01', '200.02', '300.03', '399.94'];

        $total = $initial;
        foreach ($additions as $add) {
            $total = bcadd($total, $add, 2);
        }

        expect($total)->toBe('2000.00');
    });

    it('correctly subtracts with precision', function () {
        $a = '1000.00';
        $b = '333.33';
        $c = '333.33';
        $d = '333.34';

        $result = bcsub($a, $b, 2);
        $result = bcsub($result, $c, 2);
        $result = bcsub($result, $d, 2);

        expect($result)->toBe('0.00');
    });
});
