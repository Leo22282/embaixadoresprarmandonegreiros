<?php
class ValidadorImagem
{
    private const TAMANHO_MAXIMO = 5242880;

    private const TIPOS_PERMITIDOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    public static function validar(array $arquivo): string
    {
        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Arquivo de imagem inválido.');
        }

        if (($arquivo['size'] ?? 0) > self::TAMANHO_MAXIMO) {
            throw new RuntimeException('A imagem excede o tamanho máximo permitido.');
        }

        $caminhoTemporario = $arquivo['tmp_name'] ?? '';
        if (!is_uploaded_file($caminhoTemporario)) {
            throw new RuntimeException('Upload de imagem inválido.');
        }

        $tipoImagem = (new finfo(FILEINFO_MIME_TYPE))->file($caminhoTemporario);
        if (!isset(self::TIPOS_PERMITIDOS[$tipoImagem])) {
            throw new RuntimeException('Tipo de imagem não permitido.');
        }

        return self::TIPOS_PERMITIDOS[$tipoImagem];
    }
}
