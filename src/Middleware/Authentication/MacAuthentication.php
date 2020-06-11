<?php

namespace Paysera\Component\RestClientCommon\Middleware\Authentication;

use GuzzleHttp\Psr7\Uri;
use Paysera\Component\RestClientCommon\Exception\AuthenticationConfigurationException;
use Paysera\Component\RestClientCommon\Util\ConfigHandler;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 */
class MacAuthentication implements AuthenticationMiddlewareInterface
{
    const TYPE = 'mac';

    public function __invoke(callable $nextHandler, RequestInterface $request, array $options)
    {
        $auth = ConfigHandler::getAuthentication($options, self::TYPE);

        if ($auth === null) {
            return $nextHandler($request, $options);
        }

        $nextRequest = $request->withHeader('Authorization', $this->buildMacHeader($request, $auth));

        return $nextHandler($nextRequest, $options);
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
        $ext = $this->generateExt($request, $auth);
        $mac = $this->calculateMac($request, $timestamp, $nonce, $ext, $auth['mac_secret']);

        $params = [
            'id' => $auth['mac_id'],
            'ts' => $timestamp,
            'nonce' => $nonce,
            'mac' => $mac,
        ];

        if ($ext !== '') {
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

    private function generateExt(RequestInterface $request, array $auth)
    {
        $content = $request->getBody()->getContents();
        $extParts = [];

        if ($content !== '') {
            $extParts['body_hash'] = base64_encode(hash('sha256', $content, true));
        }
        if (
            isset($auth['parameters'])
            && is_array($auth['parameters'])
            && count($auth['parameters']) > 0
        ) {
            $extParts = $extParts + $auth['parameters'];
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
            Uri::composeComponents(null, null, $request->getUri()->getPath(), $request->getUri()->getQuery(), null),
            $request->getUri()->getHost(),
            $request->getUri()->getPort() !== null
                ? $request->getUri()->getPort()
                : $this->extractPortFromRequest($request),
            $ext,
            ''
        ]);
        return base64_encode(hash_hmac('sha256', $normalizedRequest, $secret, true));
    }

    protected function extractPortFromRequest(RequestInterface $request)
    {
        return $request->getUri()->getScheme() === 'https' ? 443 : 80;
    }
}
