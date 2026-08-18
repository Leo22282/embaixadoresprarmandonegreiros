<div class="row justify-content-center align-items-center min-vh-75">
    <div class="col-lg-5 col-md-7">
        <div class="card p-4 p-md-5">
            <div class="text-center mb-4">
                <h1 class="page-title mb-2">Embaixada Armando Negreiros</h1>
                <p class="text-muted mb-0">Sistema de cadastro de pessoas</p>
            </div>

            <form method="post" action="controllers/login.php">
                <div class="mb-3">
                    <label for="login" class="form-label">Usuário</label>
                    <input type="text" id="login" name="login" class="form-control" placeholder="Digite seu usuário" required>
                </div>

                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" id="senha" name="senha" class="form-control" placeholder="Digite sua senha" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">Entrar</button>
            </form>

            <?php if (isset($_GET['erro'])): ?>
                <div class="alert alert-danger mt-3 mb-0" role="alert">
                    Usuário e/ou senha inválidos.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
