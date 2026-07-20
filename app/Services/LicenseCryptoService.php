<?php

declare(strict_types=1);

namespace App\Services;

class LicenseCryptoService
{
    /**
     * Pinned Public Key from the Control Platform
     */
    private const PUBLIC_KEY = "-----BEGIN PUBLIC KEY-----\n" .
        "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArc6HZAFVgHqzvXln7IIT\n" .
        "3M6E4cMyokcYjlodHJabzP0pUKjT3j9UwhNvQXUICgBwxfrPB+g+g0Tq0bM+xURr\n" .
        "Iuru7wVNqJ8sxACW6w646oaxnT760XW41aCXHrCm6IRRjtzen5LKRIuYIkJorurz\n" .
        "J2PsWNM076TxxA2ZCEcfx/v7wCxPK1Fd9jYKVb7h0NnQMSBh22w4nuns5j7vHNd8\n" .
        "rvPSIxD3cbSqXRAy5qxn4BRqbTJ+277ofndqlytM+MYW6Iq0nHpW2/K6f3XVx2mE\n" .
        "1Yavy246aq4GxFcB9aF+tia6D1wnZHdbsTw+XpHi1uRRl3a4zzpPT0j08Ap+oGhG\n" .
        "VwIDAQAB\n" .
        "-----END PUBLIC KEY-----";

    /**
     * Cryptographically verify the payload against the pinned public key
     */
    public static function verifyPayload(string $payloadJson, string $signatureBase64): bool
    {
        $signature = base64_decode($signatureBase64);

        $result = openssl_verify($payloadJson, $signature, self::PUBLIC_KEY, OPENSSL_ALGO_SHA256);

        return $result === 1;
    }
}
