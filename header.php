<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Embaixada Armando Negreiros</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body {
            background: #f4f7fb;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 .125rem .75rem rgba(15, 23, 42, .08);
        }

        .table thead th {
            background: #eef3ff;
            color: #1f2d3d;
            font-weight: 600;
        }

        .page-title {
            font-weight: 700;
            color: #1f2937;
        }
    </style>
</head>

<body>
    <?php if (isset($_SESSION['logado'])): ?>
    <?php $nivel = $_SESSION['nivel'] ?? 'embaixador'; ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="index.php?pagina=pessoas">Embaixada</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPrincipal" aria-controls="navPrincipal" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navPrincipal">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <?php if ($nivel === 'admin' || $nivel === 'responsavel' || $nivel === 'embaixador'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?pagina=pessoas">Pessoas</a>
                        </li>
                    <?php endif; ?>

                    <?php if ($nivel === 'admin' || $nivel === 'responsavel'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?pagina=inserir_pessoa">Cadastrar</a>
                        </li>
                    <?php endif; ?>

                    <?php if ($nivel === 'embaixador'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?pagina=meu_cadastro">Meu cadastro</a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="nav-link text-warning" href="controllers/logout.php">Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <main class="container py-4">
