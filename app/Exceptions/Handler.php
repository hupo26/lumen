<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
//        $code = $e->getCode() == 0 ? 500 : $e->getCode();
//        $msg = sprintf("%s:%s:%s", $e->getFile(), $e->getLine(), $e->getMessage());
//        return response()->json(array('errorNo' => $code, 'errorMsg' => $msg));

        //参数验证错误的异常，我们需要返回400 的http code  和一句错误信息
        if($e instanceof ValidationException){
            return response(['error'=>array_first(array_collapse($e->errors()))],400);
        }
        //用户认证的异常，我们需要返回401的 http code 和错误信息
        if($e instanceof UnauthorizedHttpException){
            return response($e->getMessage(),401);
        }

        //http错误，返回404 错误
        if($e instanceof HttpException){
            return response($e->getMessage(),404);
        }

        return parent::render($request, $e);
    }

}
