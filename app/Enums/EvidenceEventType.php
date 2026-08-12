<?php

declare(strict_types=1);

namespace App\Enums;

enum EvidenceEventType: string
{
    case SubscriptionAccepted = 'subscription_accepted';
    case PaymentRecorded = 'payment_recorded';
    case InvoiceIssued = 'invoice_issued';
    case CompanySuspended = 'company_suspended';
    case CompanyArchived = 'company_archived';
    case ClientReleased = 'client_released';
    case TenantPurged = 'tenant_purged';

    public function label(): string
    {
        return match ($this) {
            self::SubscriptionAccepted => 'Aceptación contractual',
            self::PaymentRecorded => 'Pago registrado',
            self::InvoiceIssued => 'Factura emitida',
            self::CompanySuspended => 'Suspensión',
            self::CompanyArchived => 'Archivo comercial',
            self::ClientReleased => 'Retiro de conjunto',
            self::TenantPurged => 'Purga operativa',
        };
    }
}
