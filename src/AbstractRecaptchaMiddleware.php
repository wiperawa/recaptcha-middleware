<?php
declare(strict_types=1);
namespace Wiperawa\Middleware\RecaptchaMiddleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReCaptcha\ReCaptcha;
use ReCaptcha\Response;

abstract class AbstractRecaptchaMiddleware implements MiddlewareInterface {

    protected ResponseFactoryInterface $responseFactory;

    private ServerRequestInterface $request;

    private ReCaptcha $googleRecaptcha ;

    private string $secret ;

    private string $postParameterName ;

    private ?string $expectedAction = '';

    protected array $errors = [];

    protected bool $success;

    public function __construct(ResponseFactoryInterface $responseFactory,
                                ServerRequestInterface $request,
                                string $secret,
                                string $postParameterName = 'g-recaptcha-response',
                                string $expectedAction = '')
    {
        if ($secret === '') {
            throw new \InvalidArgumentException('Secret Dont Provided');
        }

        $this->secret = $secret;
        $this->request = $request;
        $this->setGoogleRecaptcha(new ReCaptcha($this->secret));
        $this->postParameterName = $postParameterName;
        $this->expectedAction = $expectedAction;
        $this->responseFactory = $responseFactory;
    }

    public function withSecret(string $secret): self
    {
        $new = clone $this;
        $new->secret = $secret;
        return $new;
    }

    public function withPostParameterName(string $postParameterName): self
    {
        $new = clone $this;
        $new->postParameterName = $postParameterName;
        return $new;
    }

    public function withExpectedAction(string $expectedAction): self
    {
        $new = clone $this;
        $new->expectedAction = $expectedAction;
        return $new;
    }

    public function isSuccess():bool {
        return $this->success;
    }

    public function getErrors():array {
        return $this->errors;
    }

    protected function getToken(): ?string{
        $body = $this->request->getParsedBody();

        return $body[$this->postParameterName]??null;
    }

    protected function verifyToken(string $token): Response{

        $remote_ip = $_SERVER['REMOTE_ADDR']??'127.0.0.1';

        //var_dump($token); die();
        $resp = $this->googleRecaptcha
            ->setExpectedAction($this->expectedAction)
            //->setScoreThreshold(0.5)
            ->verify($token, $remote_ip);

        return $resp;
    }

    public function setGoogleRecaptcha(ReCaptcha $captcha): void {
        $this->googleRecaptcha = $captcha;
    }
}
