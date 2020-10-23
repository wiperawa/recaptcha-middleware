<?php

declare(strict_types=1);

namespace Wiperawa\Middleware\RecaptchaMiddleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RecaptchaMiddlewareValidate extends AbstractRecaptchaMiddleware {

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $this->getToken();
        if ($token === null) {
            $this->success = false;
            $this->errors = ['No Token Provided'];
        } else {
            $response = $this->verifyToken($token);
            if ($response->isSuccess()) {
                $this->success = true;
            } else {
                $this->success = false;
                $this->errors = $response->getErrorCodes();
            }
        }

        return $handler->handle($request);
    }
}