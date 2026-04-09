<?php

namespace App\Enums;

enum LeadStatus: string
{
    case NOVO = 'new';
    case EM_CONTATO = 'in_contact';
    case QUALIFICADO = 'qualified';
    case DESCARTADO = 'discarded';
}
