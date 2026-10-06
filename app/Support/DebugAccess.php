<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Decide quem pode ver a tela de depuracao.
 *
 * Com APP_DEBUG=true o Laravel mostra, em qualquer erro, o codigo-fonte, os
 * caminhos do servidor e as consultas SQL, e libera as rotas /_ignition. Isso
 * so e aceitavel para quem esta na propria maquina. Quem chega pela rede
 * recebe a pagina de erro comum, como se a depuracao estivesse desligada.
 */
class DebugAccess
{
    /**
     * Atras de um proxy na mesma maquina todo visitante chega como 127.0.0.1;
     * qualquer um destes cabecalhos denuncia que o acesso nao e local.
     */
    const PROXY_HEADERS = ['X-Forwarded-For', 'X-Forwarded-Host', 'X-Real-IP', 'Forwarded', 'Via'];

    /**
     * Desliga a depuracao para o restante desta requisicao quando ela nao
     * vem de um endereco autorizado. Nunca liga: sem APP_DEBUG nada muda.
     */
    public static function restrict(Request $request)
    {
        if (config('app.debug') && !static::allows($request)) {
            config(['app.debug' => false]);
        }
    }

    public static function allows(Request $request)
    {
        foreach (self::PROXY_HEADERS as $header) {
            if ($request->headers->has($header)) {
                return false;
            }
        }

        // REMOTE_ADDR direto: $request->ip() aceita o que um proxy confiavel informar.
        $address = static::normalize($request->server->get('REMOTE_ADDR'));

        return static::isLoopback($address) || in_array($address, static::allowedAddresses(), true);
    }

    public static function isLoopback($address)
    {
        $address = static::normalize($address);
        if ($address === '::1' || $address === '0:0:0:0:0:0:0:1') {
            return true;
        }

        return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false
            && strpos($address, '127.') === 0;
    }

    /**
     * Enderecos extras liberados em APP_DEBUG_IPS (separados por virgula).
     */
    protected static function allowedAddresses()
    {
        $addresses = [];
        foreach ((array) config('app.debug_ips', []) as $address) {
            $address = static::normalize($address);
            if ($address !== '') {
                $addresses[] = $address;
            }
        }

        return $addresses;
    }

    protected static function normalize($address)
    {
        $address = strtolower(trim((string) $address));

        // IPv4 escrito como IPv6 (::ffff:127.0.0.1).
        return strpos($address, '::ffff:') === 0 ? substr($address, 7) : $address;
    }
}
