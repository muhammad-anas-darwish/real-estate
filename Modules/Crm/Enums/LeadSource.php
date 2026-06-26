<?php

namespace Modules\Crm\Enums;

enum LeadSource: string
{
    case WEBSITE = 'website';
    case WHATSAPP = 'whatsapp';
    case REFERRAL = 'referral';
    case WALK_IN = 'walk_in';
    case PHONE = 'phone';
    case OTHER = 'other';
}
