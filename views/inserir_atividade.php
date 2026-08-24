<?php
$nivelAtual = $_SESSION['nivel'] ?? 'embaixador';
?>

<style>
    .activity-editor .ck-editor__editable {
        min-height: 220px;
    }
</style>

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="page-title mb-1">Cadastrar atividade</h2>
            <p class="text-muted mb-0">Preencha as informações para criar uma nova atividade.</p>
        </div>
        <a href="index.php?pagina=atividades" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <form action="controllers/atividades/inserir.php" method="post" enctype="multipart/form-data">
        <div class="row g-3">
            <div class="col-12">
                <label for="imagem" class="form-label">Imagem</label>
                <input type="file" class="form-control" id="imagem" name="imagem" accept="image/*">
                <div class="form-text">Selecione uma imagem para aparecer no card da atividade.</div>
            </div>

            <div class="col-12">
                <label for="titulo" class="form-label">Título</label>
                <input type="text" class="form-control" id="titulo" name="titulo" maxlength="150" required>
            </div>

            <div class="col-12">
                <label for="texto_curto" class="form-label">Texto curto</label>
                <textarea class="form-control" id="texto_curto" name="texto_curto" rows="3" maxlength="300" required></textarea>
            </div>

            <div class="col-12">
                <label for="conteudo_html" class="form-label">Texto em HTML</label>
                <div class="activity-editor">
                    <textarea class="form-control" id="conteudo_html" name="conteudo_html" rows="8"></textarea>
                </div>
                <div class="form-text">Use a barra de ferramentas para formatar o conteúdo da atividade.</div>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-circle"></i> Cadastrar atividade
            </button>
            <a href="index.php?pagina=atividades" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var formAtividade = document.querySelector('form[action="controllers/atividades/inserir.php"]');
        var campoConteudo = document.querySelector('#conteudo_html');
        var editorAtividade = null;

        if (!formAtividade || !campoConteudo) {
            return;
        }

        formAtividade.addEventListener('submit', function (event) {
            var valorConteudo = '';

            if (editorAtividade) {
                valorConteudo = editorAtividade.getData().trim();
                campoConteudo.value = valorConteudo;
            } else {
                valorConteudo = campoConteudo.value.trim();
            }

            if (valorConteudo === '') {
                event.preventDefault();
                alert('Preencha o conteúdo da atividade antes de cadastrar.');
            }
        });

        if (typeof ClassicEditor === 'undefined') {
            return;
        }

        ClassicEditor.create(campoConteudo, {
            language: 'pt-br',
            toolbar: [
                'heading', '|',
                'bold', 'italic', 'link', '|',
                'bulletedList', 'numberedList', '|',
                'blockQuote', 'undo', 'redo'
            ]
        }).then(function (editor) {
            editorAtividade = editor;
        }).catch(function (error) {
            console.error('Não foi possível iniciar o editor de atividades.', error);
        });
    });
</script>
