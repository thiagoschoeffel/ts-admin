<?php

return [
    'resources' => [
        'clients' => [
            'label' => 'Gestão de clientes',
            'abilities' => [
                'view' => 'Visualizar clientes',
                'create' => 'Criar clientes',
                'update' => 'Editar clientes',
                'delete' => 'Excluir clientes',
            ],
        ],
        'products' => [
            'label' => 'Gestão de produtos',
            'abilities' => [
                'view' => 'Visualizar produtos',
                'create' => 'Criar produtos',
                'update' => 'Editar produtos',
                'delete' => 'Excluir produtos',
            ],
        ],
        'orders' => [
            'label' => 'Gestão de pedidos',
            'abilities' => [
                'view' => 'Visualizar pedidos',
                'create' => 'Criar pedidos',
                'update' => 'Editar pedidos',
                'delete' => 'Excluir pedidos',
                'update_status' => 'Alterar status de pedidos',
                'export_pdf' => 'Exportar PDF de pedidos',
            ],
        ],
        'leads' => [
            'label' => 'Gestão de leads',
            'abilities' => [
                'view' => 'Visualizar leads',
                'create' => 'Criar leads',
                'update' => 'Editar leads',
                'delete' => 'Excluir leads',
            ],
        ],
        'opportunities' => [
            'label' => 'Gestão de oportunidades',
            'abilities' => [
                'view' => 'Visualizar oportunidades',
                'create' => 'Criar oportunidades',
                'update' => 'Editar oportunidades',
                'delete' => 'Excluir oportunidades',
            ],
        ],
        'sectors' => [
            'label' => 'Gestão de setores',
            'abilities' => [
                'view' => 'Visualizar setores',
                'create' => 'Criar setores',
                'update' => 'Editar setores',
                'delete' => 'Excluir setores',
            ],
        ],
        'raw_materials' => [
            'label' => 'Gestão de matérias-primas',
            'abilities' => [
                'view' => 'Visualizar matérias-primas',
                'create' => 'Criar matérias-primas',
                'update' => 'Editar matérias-primas',
                'delete' => 'Excluir matérias-primas',
            ],
        ],
        'inventory_movements' => [
            'label' => 'Movimentos de Estoque',
            'abilities' => [
                'view' => 'Visualizar movimentos de estoque',
                'create' => 'Criar movimentos de estoque',
                'update' => 'Editar movimentos de estoque',
                'delete' => 'Excluir movimentos de estoque',
            ],
        ],
        'production_pointings' => [
            'label' => 'Apontamento de produção EPS',
            'abilities' => [
                'view' => 'Visualizar apontamentos de produção',
                'create' => 'Criar apontamentos de produção',
                'update' => 'Editar apontamentos de produção',
                'delete' => 'Excluir apontamentos de produção',
            ],
        ],
        'block_productions' => [
            'label' => 'Produções de blocos',
            'abilities' => [
                'view' => 'Visualizar produções de blocos',
                'create' => 'Criar produções de blocos',
                'update' => 'Editar produções de blocos',
                'delete' => 'Excluir produções de blocos',
            ],
        ],
        'block_dispatches' => [
            'label' => 'Saída de blocos',
            'abilities' => [
                'view' => 'Visualizar saídas de blocos',
                'create' => 'Criar saídas de blocos',
                'update' => 'Editar saídas de blocos',
                'delete' => 'Excluir saídas de blocos',
            ],
        ],
        'molded_dispatches' => [
            'label' => 'Saída de moldados',
            'abilities' => [
                'view' => 'Visualizar saídas de moldados',
                'create' => 'Criar saídas de moldados',
                'update' => 'Editar saídas de moldados',
                'delete' => 'Excluir saídas de moldados',
            ],
        ],
        'molded_productions' => [
            'label' => 'Produções moldadas',
            'abilities' => [
                'view' => 'Visualizar produções moldadas',
                'create' => 'Criar produções moldadas',
                'update' => 'Editar produções moldadas',
                'delete' => 'Excluir produções moldadas',
            ],
        ],
        'silos' => [
            'label' => 'Gestão de silos',
            'abilities' => [
                'view' => 'Visualizar silos',
                'create' => 'Criar silos',
                'update' => 'Editar silos',
                'delete' => 'Excluir silos',
            ],
        ],
        'block_types' => [
            'label' => 'Gestão de tipos de blocos',
            'abilities' => [
                'view' => 'Visualizar tipos de blocos',
                'create' => 'Criar tipos de blocos',
                'update' => 'Editar tipos de blocos',
                'delete' => 'Excluir tipos de blocos',
            ],
        ],
        'almoxarifados' => [
            'label' => 'Gestão de almoxarifados',
            'abilities' => [
                'view' => 'Visualizar almoxarifados',
                'create' => 'Criar almoxarifados',
                'update' => 'Editar almoxarifados',
                'delete' => 'Excluir almoxarifados',
            ],
        ],
        'machines' => [
            'label' => 'Gestão de máquinas',
            'abilities' => [
                'view' => 'Visualizar máquinas',
                'create' => 'Criar máquinas',
                'update' => 'Editar máquinas',
                'delete' => 'Excluir máquinas',
            ],
        ],
        'reason_types' => [
            'label' => 'Tipos de Motivos',
            'abilities' => [
                'view' => 'Visualizar tipos de motivos',
                'create' => 'Criar tipos de motivos',
                'update' => 'Editar tipos de motivos',
                'delete' => 'Excluir tipos de motivos',
            ],
        ],
        'reasons' => [
            'label' => 'Motivos',
            'abilities' => [
                'view' => 'Visualizar motivos',
                'create' => 'Criar motivos',
                'update' => 'Editar motivos',
                'delete' => 'Excluir motivos',
            ],
        ],
        'machine_downtimes' => [
            'label' => 'Paradas de Máquina',
            'abilities' => [
                'view' => 'Visualizar paradas de máquina',
                'create' => 'Criar paradas de máquina',
                'update' => 'Editar paradas de máquina',
                'delete' => 'Excluir paradas de máquina',
            ],
        ],
        'operators' => [
            'label' => 'Gestão de operadores',
            'abilities' => [
                'view' => 'Visualizar operadores',
                'create' => 'Criar operadores',
                'update' => 'Editar operadores',
                'delete' => 'Excluir operadores',
            ],
        ],
    ],
];
