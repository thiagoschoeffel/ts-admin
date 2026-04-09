<?php

namespace App\Enums;

enum LeadSource: string
{
  case SITE = 'site';
  case INDICACAO = 'indicacao';
  case EVENTO = 'evento';
  case MANUAL = 'manual';
}
