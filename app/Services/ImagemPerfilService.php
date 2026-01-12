<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Service para processamento de imagens de perfil
 */
class ImagemPerfilService
{
    private const TAMANHO_MAXIMO = 300; // pixels
    private const QUALIDADE_JPEG = 85;

    /**
     * Processa e salva a foto de perfil
     * Redimensiona para 300x300px se GD estiver disponível
     */
    public function processarEsalvar(UploadedFile $foto, int $userId): string
    {
        $extensao = $foto->extension();
        $filename = "user_{$userId}_" . time() . ".{$extensao}";
        $path = "fotos_perfil/{$filename}";

        // Tenta redimensionar se GD estiver disponível
        if (extension_loaded('gd')) {
            $imagemRedimensionada = $this->redimensionar($foto);
            if ($imagemRedimensionada) {
                Storage::disk('public')->put($path, $imagemRedimensionada);
                return $path;
            }
        }

        // Fallback: salva sem redimensionar
        $foto->storeAs('fotos_perfil', $filename, 'public');
        return $path;
    }

    /**
     * Redimensiona a imagem para tamanho quadrado mantendo proporção
     */
    private function redimensionar(UploadedFile $foto): ?string
    {
        $mimeType = $foto->getMimeType();
        $imagemOriginal = null;

        // Cria imagem baseada no tipo
        switch ($mimeType) {
            case 'image/jpeg':
                $imagemOriginal = imagecreatefromjpeg($foto->getPathname());
                break;
            case 'image/png':
                $imagemOriginal = imagecreatefrompng($foto->getPathname());
                break;
            default:
                return null;
        }

        if (!$imagemOriginal) {
            return null;
        }

        $larguraOriginal = imagesx($imagemOriginal);
        $alturaOriginal = imagesy($imagemOriginal);

        // Calcula dimensões mantendo proporção
        $ratio = min(self::TAMANHO_MAXIMO / $larguraOriginal, self::TAMANHO_MAXIMO / $alturaOriginal);
        $novaLargura = (int) ($larguraOriginal * $ratio);
        $novaAltura = (int) ($alturaOriginal * $ratio);

        // Cria imagem redimensionada
        $imagemNova = imagecreatetruecolor($novaLargura, $novaAltura);

        // Preserva transparência para PNG
        if ($mimeType === 'image/png') {
            imagealphablending($imagemNova, false);
            imagesavealpha($imagemNova, true);
        }

        imagecopyresampled(
            $imagemNova,
            $imagemOriginal,
            0, 0, 0, 0,
            $novaLargura,
            $novaAltura,
            $larguraOriginal,
            $alturaOriginal
        );

        // Captura output em string
        ob_start();
        if ($mimeType === 'image/png') {
            imagepng($imagemNova);
        } else {
            imagejpeg($imagemNova, null, self::QUALIDADE_JPEG);
        }
        $conteudo = ob_get_clean();

        // Libera memória
        imagedestroy($imagemOriginal);
        imagedestroy($imagemNova);

        return $conteudo;
    }

    /**
     * Remove foto do storage
     */
    public function remover(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
