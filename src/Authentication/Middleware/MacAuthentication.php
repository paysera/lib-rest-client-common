<?php

namespace Paysera\Component\RestClientCommon\Authentication\Middleware;

use Paysera\Component\RestClientCommon\Authentication\Exception\AuthenticationConfigurationException;
use Paysera\Component\RestClientCommon\Util\ConfigHandler;
use Psr\Http\Message\RequestInterface;

class MacAuthentication implements AuthenticationMiddlewareInterface
{
    const TYPE = 'mac';

    public function __invoke(callable $nextHandler, RequestInterface $request, array $options)
    {
        $auth = ConfigHandler::getAuthentication($options, self::TYPE);

        if ($auth === null) {
            return $nextHandler($request, $auth);
        }

        $nextRequest = $request->withHeader('Authorization', $this->buildMacHeader($request, $auth));

        return $nextHandler($nextRequest, $auth);
    }

    public function getPriority()
    {
        return 100;
    }

    private function buildMacHeader(RequestInterface $request, array $auth)
    {
        if (!isset($auth['mac_id']) || !isset($auth['mac_secret'])) {
            throw new AuthenticationConfigurationException(
                'Missing Authentication configuration. MacId and MacSecret is required'
            );
        }

        $timestamp = $this->getTimestamp();
        $nonce = $this->generateNonce();
        $ext = $this->generateExt($request);
        $mac = $this->calculateMac($request, $timestamp, $nonce, $ext, $auth['mac_secret']);

        $params = [
            'id' => $auth['mac_id'],
            'ts' => $timestamp,
            'nonce' => $nonce,
            'mac' => $mac,
        ];

        if ($ext != '') {
            $params['ext'] = $ext;
        }

        $parts = [];
        foreach ($params as $name => $value) {
            $parts[] = $name . '="' . $value . '"';
        }

        return 'MAC ' . implode(', ', $parts);
    }

    private function getTimestamp()
    {
        return time();
    }

    private function generateNonce($length = 32)
    {
        $nonce = '';
        for ($i = 0; $i < $length; $i++) {
            $rnd = mt_rand(0, 92);
            if ($rnd >= 2) {
                $rnd++;
            }
            if ($rnd >= 60) {
                $rnd++;
            }
            $nonce .= chr(32 + $rnd);
        }
        return $nonce;
    }

    private function generateExt(RequestInterface $request)
    {
        $content = $request->getBody()->getContents();
        $extParts = [];

        if ($content != '') {
            $extParts['body_hash'] = base64_encode(hash('sha256', $content, true));
        }

        if (count($extParts) > 0) {
            return http_build_query($extParts);
        } else {
            return '';
        }
    }

    private function calculateMac(RequestInterface $request, $timestamp, $nonce, $ext, $secret)
    {
        $normalizedRequest = implode("\n", [
            $timestamp,
            $nonce,
            $request->getMethod(),
            $request->getUri()->getPath(),
            $request->getUri()->getHost(),
            $request->getUri()->getPort(),
            $ext,
            ''
        ]);
        return base64_encode(hash_hmac('sha256', $normalizedRequest, $secret, true));
    }
}
