<?php

declare(strict_types=1);

namespace Wiperawa\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReCaptcha\ReCaptcha;
use ReCaptcha\Response;
use Yiisoft\Http\Status;

class RecaptchaMiddleware implements MiddlewareInterface
{
    public const DEFAULT_POST_FIELD = 'g-recaptcha-response';

    private string $postParameterName;

    private ?string $expectedAction = '';
    protected array $errors = [];
    protected bool $success;

    protected ResponseFactoryInterface $responseFactory;
    private ReCaptcha $googleRecaptcha;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ReCaptcha $recaptcha,
        string $postParameterName = self::DEFAULT_POST_FIELD,
        string $expectedAction = ''
    ) {
        $this->responseFactory = $responseFactory;
        $this->googleRecaptcha = $recaptcha;

        $this->postParameterName = $postParameterName;
        $this->expectedAction = $expectedAction;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->verifyToken($request);
        if ($result->isSuccess()) {
            // SUCCESS ACTION
            return $handler->handle($request->withAttribute(static::class, $result));
        } else {
            // FAIL ACTION
            return $this->responseFactory->createResponse(Status::BAD_REQUEST, implode(',', $result->getErrorCodes()));
        }
    }

    /**
     * @psalm-mutation-free
     */
    public function withRecaptcha(ReCaptcha $recaptcha): self
    {
        $new = clone $this;
        $new->googleRecaptcha = $recaptcha;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withPostParameterName(string $postParameterName): self
    {
        $new = clone $this;
        $new->postParameterName = $postParameterName;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withExpectedAction(string $expectedAction): self
    {
        $new = clone $this;
        $new->expectedAction = $expectedAction;
        return $new;
    }

    protected function verifyToken(ServerRequestInterface $request): Response
    {
        $token = $this->getToken($request);
        $remoteIp = $request->getServerParams()['REMOTE_ADDR'] ?? null;

        return $this->googleRecaptcha
            ->setExpectedAction($this->expectedAction)
            ->verify($token, $remoteIp);
    }

    private function getToken(ServerRequestInterface $request): string
    {
        return $request->getParsedBody()[$this->postParameterName] ?? '';
    }
}
