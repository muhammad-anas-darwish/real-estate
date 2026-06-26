<?php

namespace Modules\Crm\Enums;

enum LeadStatus: string
{
    case NEW = 'new';
    case CONTACTED = 'contacted';
    case QUALIFIED = 'qualified';
    case WON = 'won';
    case LOST = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::NEW => __('crm.status_new'),
            self::CONTACTED => __('crm.status_contacted'),
            self::QUALIFIED => __('crm.status_qualified'),
            self::WON => __('crm.status_won'),
            self::LOST => __('crm.status_lost'),
        };
    }
}
