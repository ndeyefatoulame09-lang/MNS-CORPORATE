<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/accounting_rules.php';

function ensureNonNegativeAmount(float $amount): void
{
    if ($amount < 0) {
        throw new InvalidArgumentException('Le montant ne peut pas etre negatif.');
    }
}

function calculateVat(float $amountHt, float $rate = TVA_STANDARD_RATE): float
{
    ensureNonNegativeAmount($amountHt);
    ensureNonNegativeAmount($rate);
    return round($amountHt * $rate / 100, 2);
}

function calculateAmountTtc(float $amountHt, float $rate = TVA_STANDARD_RATE): float
{
    ensureNonNegativeAmount($amountHt);
    return round($amountHt + calculateVat($amountHt, $rate), 2);
}

function calculateBalanceDue(float $invoiceTotal, float $paymentsTotal): float
{
    ensureNonNegativeAmount($invoiceTotal);
    ensureNonNegativeAmount($paymentsTotal);
    return round(max(0, $invoiceTotal - $paymentsTotal), 2);
}

function calculateIpresEmployee(float $grossSalary): float
{
    ensureNonNegativeAmount($grossSalary);
    return round($grossSalary * IPRES_PART_SALARIAL / 100, 2);
}

function calculateIpresEmployer(float $grossSalary): float
{
    ensureNonNegativeAmount($grossSalary);
    return round($grossSalary * IPRES_PART_PATRONALE / 100, 2);
}

function calculateCssEmployee(float $grossSalary): float
{
    ensureNonNegativeAmount($grossSalary);
    return round($grossSalary * CSS_PART_SALARIAL / 100, 2);
}

function calculateCssEmployer(float $grossSalary): float
{
    ensureNonNegativeAmount($grossSalary);
    return round($grossSalary * CSS_PART_PATRONALE / 100, 2);
}
