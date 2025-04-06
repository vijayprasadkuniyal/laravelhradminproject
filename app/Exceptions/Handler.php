<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
public function render($request, Throwable $e){
        
        if($request->is('api/*')){
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.',
                    'data' => [],
                ], 401);
            }
    
            if($e instanceof \Illuminate\Validation\ValidationException){
                return response()->json([
                    'status' => false,
                    'message' => $e->validator->errors()->first(),
                    'data' => [],
                ], 422);
            }
    
            if($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException){
                return response()->json([
                    'status' => false,
                    'message' => 'Resource not found.',
                    'data' => [],
                ], 404);
            }
    
            if($e instanceof \Illuminate\Database\QueryException){
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                    'data' => [],
                ], 500);
            }
            
            if($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException){
                return response()->json([
                    'status' => false,
                    'message' => 'URL not found.',
                    'data' => [],
                ], 404);
            }
        }

        return parent::render($request, $e);
    }
}
