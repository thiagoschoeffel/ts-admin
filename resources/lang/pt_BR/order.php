<?php

return [
    'delete_blocked_not_pending' => 'Pedido não pode ser excluído porque não está com status pendente.',
    'client_inactive_on_create' => 'Não é possível criar pedido para cliente inativo.',
    'product_inactive_on_create' => 'Não é possível incluir produto inativo no pedido.',
    'client_inactive_on_change' => 'Não é possível trocar o cliente para um inativo.',
    'product_inactive_on_change' => 'Não é possível trocar o produto do item para um inativo.',
    'pdf' => [
        'empty_items' => 'Não é possível gerar PDF para pedidos sem itens.',
        'generation_error' => 'Erro ao gerar PDF do pedido.',
        'forbidden' => 'Você não tem permissão para gerar o PDF deste pedido.',
    ],
];
