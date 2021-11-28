<?php


namespace App\Services;


use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;

final class SaynaTokenStorage
{
    /**
     * @var JWSProviderInterface
     */
    protected $jwsProvider;

    public const VERIFIED = 'verified';
    public const EXPIRED = 'expired';
    public const INVALID = 'invalid';

    /**
     * @var
     */
    private $status;
    /**
     * @var
     */
    private $payload;

    /**
     * @param string $token
     * @param JWSProviderInterface $jwsProvider
     */
    public function __construct(
        string $token,
        JWSProviderInterface $jwsProvider
    ) {
        $this->jwsProvider = $jwsProvider;
        $this->decode($token);
    }

    /**
     * @param string $token
     */
    public function decode($token): void
    {
        try {
            $jws = $this->jwsProvider->load($token);

            if ($jws->isInvalid()) {
                $this->status = self::INVALID;
            }

            if ($jws->isVerified()) {
                $this->status = self::VERIFIED;
            }

            if ($jws->isExpired()) {
                $this->status = self::EXPIRED;
            }

            $this->payload = $jws->getPayload();

        } catch (\Exception $e) {
            $this->status = self::INVALID;
        }
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function isVerified(): bool
    {
        return self::VERIFIED === $this->status;
    }

    public function isExpired(): bool
    {
        return self::EXPIRED === $this->status;
    }

    public function isInvalid(): bool
    {
        return self::INVALID === $this->status;
    }
}