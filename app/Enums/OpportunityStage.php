<?php

namespace App\Enums;

enum OpportunityStage: string
{
  case NOVO = 'new';
  case CONTATO = 'contact';
  case PROPOSTA = 'proposal';
  case NEGOCIACAO = 'negotiation';
  case GANHOU = 'won';
  case PERDEU = 'lost';
}
