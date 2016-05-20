<?php
namespace Wandu\Foundation\Error;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Wandu\Foundation\Contracts\HttpErrorHandlerInterface;

class DefaultHttpErrorHandler implements HttpErrorHandlerInterface
{
    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request, Throwable $exception)
    {
        $this->logger->error($this->prettifyRequest($request));
        $this->logger->error($exception);

        if ($this->isAjax($request)) {
            return \Wandu\Http\json([
                'status' => 500,
                'reason' => 'Internal Server Error',
            ], 500);
        }

        // 에러화면에서는 어떤에러인지 메시지를 출력해서는 안된다.
        return \Wandu\Http\create("500 Internal Server Error", 500);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return bool
     */
    protected function isAjax(ServerREquestInterface $request)
    {
        return $request->hasHeader('x-requested-with') &&
            $request->getHeaderLine('x-requested-with') === 'XMLHttpRequest';
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return string
     */
    protected function prettifyRequest(ServerRequestInterface $request)
    {
        $contents = "{$request->getMethod()} : {$request->getUri()->__toString()}\n";
        $contents .= "HEADERS\n";
        foreach ($request->getHeaders() as $name => $value) {
            $contents .= "    {$name} : {$request->getHeaderLine($name)}\n";
        }
        $contents .= "BODY\n";
        $contents .= "\"{$request->getBody()->__toString()}\"\n";
        return $contents;
    }
}