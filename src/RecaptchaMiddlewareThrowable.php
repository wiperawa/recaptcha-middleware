<?php

declare(strict_types=1);

namespace Wiperawa\Middleware\RecaptchaMiddleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;

class RecaptchaMiddlewareThrowable extends AbstractRecaptchaMiddleware {

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $this->getToken();
        if ($token === null) {
            return $this->responseFactory->createResponse(Status::BAD_REQUEST,'No reCaptcha Token in POST provided');
        }
        $response = $this->verifyToken($token);

        if (!$response->isSuccess()) {
            return $this->responseFactory->createResponse(Status::BAD_REQUEST,'Recaptcha token verification failed');
        }

        return $handler->handle($request);
    }
}