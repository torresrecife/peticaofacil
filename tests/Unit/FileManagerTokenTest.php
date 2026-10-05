<?php

namespace Tests\Unit;

use App\Support\FileManagerToken;
use PHPUnit\Framework\TestCase;

class FileManagerTokenTest extends TestCase
{
    private $key = 'base64:chave-de-teste-apenas';

    public function test_valid_token_returns_the_user_id()
    {
        $token = FileManagerToken::issue(42, 2000, $this->key);

        $this->assertSame(42, FileManagerToken::verify($token, $this->key, 1999));
    }

    public function test_expired_token_is_rejected()
    {
        $token = FileManagerToken::issue(42, 2000, $this->key);

        $this->assertNull(FileManagerToken::verify($token, $this->key, 2000));
        $this->assertNull(FileManagerToken::verify($token, $this->key, 2001));
    }

    public function test_token_signed_with_another_key_is_rejected()
    {
        $token = FileManagerToken::issue(42, 2000, 'outra-chave');

        $this->assertNull(FileManagerToken::verify($token, $this->key, 1999));
    }

    public function test_tampered_user_or_expiry_is_rejected()
    {
        $parts = explode('.', FileManagerToken::issue(42, 2000, $this->key));

        $otherUser = implode('.', [$parts[0], '1', $parts[2], $parts[3]]);
        $laterExpiry = implode('.', [$parts[0], $parts[1], '9999999999', $parts[3]]);

        $this->assertNull(FileManagerToken::verify($otherUser, $this->key, 1999));
        $this->assertNull(FileManagerToken::verify($laterExpiry, $this->key, 1999));
    }

    public function test_malformed_or_empty_values_are_rejected()
    {
        foreach (['', 'abc', 'v1.42.2000', 'v2.42.2000.assinatura', 'v1.x.2000.y', 'v1.0.2000.y', null, ['v1']] as $value) {
            $this->assertNull(FileManagerToken::verify($value, $this->key, 1999));
        }
    }

    public function test_nothing_is_issued_or_accepted_without_a_key()
    {
        $this->assertSame('', FileManagerToken::issue(42, 2000, ''));

        $token = FileManagerToken::issue(42, 2000, $this->key);
        $this->assertNull(FileManagerToken::verify($token, '', 1999));
    }

    public function test_key_is_read_from_env_file()
    {
        $path = tempnam(sys_get_temp_dir(), 'pfenv');
        file_put_contents($path, "APP_NAME=\"Peticao Facil\"\nAPP_KEY=\"base64:abc123==\"\nAPP_DEBUG=true\n");

        try {
            $this->assertSame('base64:abc123==', FileManagerToken::keyFromEnvFile($path));
            $this->assertSame('', FileManagerToken::keyFromEnvFile($path . '.inexistente'));
        } finally {
            @unlink($path);
        }
    }
}
