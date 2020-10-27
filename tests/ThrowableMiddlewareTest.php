<?php

namespace Wiperawa\Middleware\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReCaptcha\ReCaptcha;
use ReCaptcha\Response;
use Wiperawa\Middleware\RecaptchaMiddleware;
use Yiisoft\DataResponse\DataResponseFactory;

class ThrowableMiddlewareTest extends TestCase {


    public function testThrovableMiddleware(){
        $googleRecaptcha = new ReCaptcha(
            'secret',
            $this->createMockGoogleResponse('{"success": true}')
        );

        $mw = (new RecaptchaMiddleware(
            new Psr17Factory(),
            $googleRecaptcha
        ))->withRecaptcha($googleRecaptcha);

        $requestHandler = $this->getRequestHandler($this->createResponse('{"success": true}'));
        $request = $this->createRequest();

        $res = $mw->process($request, $requestHandler);

        $this->assertEquals(200, $res->getStatusCode());
    }

    private function createMockGoogleResponse($responseJson){
        $method = $this->getMockBuilder(\ReCaptcha\RequestMethod::class)
            ->disableOriginalConstructor()
            ->setMethods(array('submit'))
            ->getMock();
        $method->expects($this->any())
            ->method('submit')
            ->with($this->callback(function ($params) {
                return true;
            }))
            ->will($this->returnValue($responseJson));
        return $method;
    }

    private function getRequestHandler(ResponseInterface $response): RequestHandlerInterface
    {
        return new class($response) implements RequestHandlerInterface {
            private ResponseInterface $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };
    }

    private function createResponse($data){
        return (new Psr17Factory())->createResponse(200,'correct response');
    }

    private function createRequest(): ServerRequest {

        return (new ServerRequest('POST', '/register'))->withParsedBody(['g-recaptcha-response' => 'ttest']);

    }
}
