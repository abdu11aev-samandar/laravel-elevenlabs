<?php

namespace Samandar\LaravelElevenLabs\Exceptions;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * Base exception class for ElevenLabs API errors
 */
class ElevenLabsException extends Exception
{
    protected ?ResponseInterface $response = null;
    protected ?GuzzleException $guzzleException = null;
    protected ?array $errorData = null;

    public function __construct(
        string $message = "",
        int $code = 0,
        ?Exception $previous = null,
        ?ResponseInterface $response = null,
        ?GuzzleException $guzzleException = null,
        ?array $errorData = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
        $this->guzzleException = $guzzleException;
        $this->errorData = $errorData;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function getGuzzleException(): ?GuzzleException
    {
        return $this->guzzleException;
    }

    public function getErrorData(): ?array
    {
        return $this->errorData;
    }

    public function getStatusCode(): ?int
    {
        return $this->response ? $this->response->getStatusCode() : null;
    }

    public function getResponseBody(): ?string
    {
        if (!$this->response) {
            return null;
        }

        try {
            $this->response->getBody()->rewind();
            return $this->response->getBody()->getContents();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function isRateLimited(): bool
    {
        return $this->getStatusCode() === 429;
    }

    public function isServerError(): bool
    {
        $statusCode = $this->getStatusCode();
        return $statusCode && $statusCode >= 500;
    }

    public function isClientError(): bool
    {
        $statusCode = $this->getStatusCode();
        return $statusCode && $statusCode >= 400 && $statusCode < 500;
    }

    public function isRetryable(): bool
    {
        return $this->isRateLimited() || $this->isServerError();
    }

    public function getRetryAfterSeconds(): ?int
    {
        if (!$this->response) {
            return null;
        }

        $retryAfterHeader = $this->response->getHeader('Retry-After');
        if (empty($retryAfterHeader)) {
            return null;
        }

        $retryAfter = $retryAfterHeader[0];
        
        // Check if it's a date format or seconds
        if (is_numeric($retryAfter)) {
            return (int) $retryAfter;
        }

        // Parse date format
        $retryAfterTime = strtotime($retryAfter);
        if ($retryAfterTime !== false) {
            return max(0, $retryAfterTime - time());
        }

        return null;
    }
}
