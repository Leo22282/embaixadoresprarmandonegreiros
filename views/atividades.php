<div><h3>Atividades</h3></div>

    <?php if ($nivel !== 'embaixador'): ?>
        <div class="d-flex justify-content-end g-2 mb-3">
            <a href="index.php?pagina=inserir_atividade" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Cadastrar Atividade
            </a>
        </div>
    <?php endif; ?>

<?php
require_once 'builders/cardGridBuilder.php';

$destinations = [
    [
        'image' => 'images/vitoria.jpg',
        'title' => 'Visit Vitória',
        'text' => 'Discover the beautiful beaches and historic sites of the capital city.'
    ],
    [
        'image' => 'images/vilavelha.jpg',
        'title' => 'Explore Vila Velha',
        'text' => 'Home to the famous Convento da Penha and beautiful coastlines.'
    ],
    [
        'image' => 'images/guarapari.jpg',
        'title' => 'Relax in Guarapari',
        'text' => 'Famous for its therapeutic beaches and vibrant summer life.'
    ]
];

$grid = new CardGridBuilder($destinations);
$grid->render();

