<?php

namespace App\Support;

/**
 * Credencial assinada que libera o gerenciador de arquivos (CKFinder).
 *
 * O conector do CKFinder roda fora do Laravel e nao enxerga a sessao. O
 * Laravel emite este token em um cookie para quem esta logado, e o
 * public/ckfinder/config.php confere a assinatura antes de atender.
 *
 * Esta classe e carregada direto pelo config.php do CKFinder, sem o
 * autoloader do framework: nao pode depender de nada do Laravel.
 */
class FileManagerToken
{
    const COOKIE = 'pf_filemanager';

    const VERSION = 'v1';

    /**
     * Gera o token para um usuario, valido ate o instante informado.
     * Devolve string vazia quando nao ha chave para assinar.
     */
    public static function issue($userId, $expiresAt, $key)
    {
        $userId = (int) $userId;
        $expiresAt = (int) $expiresAt;
        $key = (string) $key;

        if ($key === '' || $userId < 1) {
            return '';
        }

        return implode('.', [
            self::VERSION,
            $userId,
            $expiresAt,
            self::sign($userId, $expiresAt, $key),
        ]);
    }

    /**
     * Confere o token. Devolve o id do usuario quando a assinatura e
     * valida e o prazo nao venceu; caso contrario, null.
     */
    public static function verify($token, $key, $now = null)
    {
        $key = (string) $key;

        if ($key === '' || !is_string($token) || $token === '') {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 4 || $parts[0] !== self::VERSION) {
            return null;
        }

        if (!ctype_digit($parts[1]) || !ctype_digit($parts[2])) {
            return null;
        }

        $userId = (int) $parts[1];
        $expiresAt = (int) $parts[2];
        if ($userId < 1) {
            return null;
        }

        if (!hash_equals(self::sign($userId, $expiresAt, $key), (string) $parts[3])) {
            return null;
        }

        $now = $now === null ? time() : (int) $now;
        if ($expiresAt <= $now) {
            return null;
        }

        return $userId;
    }

    /**
     * Le o APP_KEY do arquivo .env, para uso fora do Laravel.
     * Vale a primeira ocorrencia, como no carregador do framework.
     */
    public static function keyFromEnvFile($path)
    {
        if (!is_string($path) || !is_file($path) || !is_readable($path)) {
            return '';
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return '';
        }

        foreach ($lines as $line) {
            if (!preg_match('/^\s*(?:export\s+)?APP_KEY\s*=\s*(.*)$/', $line, $matches)) {
                continue;
            }

            $value = trim($matches[1]);
            $length = strlen($value);
            if ($length >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[$length - 1] === $value[0]) {
                $value = substr($value, 1, -1);
            }

            return $value;
        }

        return '';
    }

    protected static function sign($userId, $expiresAt, $key)
    {
        return hash_hmac(
            'sha256',
            self::VERSION . '|' . (int) $userId . '|' . (int) $expiresAt,
            'filemanager|' . $key
        );
    }
}
