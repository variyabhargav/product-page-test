<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Response;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class JwtMiddleware extends BaseMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$token = $this->auth->setRequest($request)->getToken()) {
            return Response::json(array('status' => 400, 'message' => 'Required field token is missing or empty.', 'data' => json_decode("{}")));
        }

        try {
            JWTAuth::parseToken()->authenticate();
        } catch (TokenInvalidException $e) {
            return Response::json(array('status' => $e->getCode(), 'message' => 'Invalid token.', 'data' => json_decode('{}')));
        } catch (TokenExpiredException $e) {
            try {
                $new_token = JWTAuth::refresh($token);
                return Response::json(array('status' => 423, 'message' => 'Token expired.', 'data' => ['new_token' => $new_token]));
            } catch (TokenExpiredException $e) {
                return Response::json(array('status' => $e->getCode(), 'message' => $e->getMessage(), 'data' => json_decode('{}')));
            } catch (TokenBlacklistedException $e) {
                return Response::json(array('status' => 400, 'message' => $e->getMessage(), 'data' => json_decode("{}")));
            } catch (JWTException $e) {
                return Response::json(array('status' => $e->getCode(), 'message' => $e->getMessage(), 'data' => json_decode("{}")));
            }
        } catch (JWTException $e) {
            return Response::json(array('status' => $e->getCode(), 'message' => $e->getMessage(), 'data' => json_decode("{}")));
        }

        return $next($request);
    }
}
