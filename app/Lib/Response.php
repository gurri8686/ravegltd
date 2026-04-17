<?php

/**
 * Description of Response
 *
 * @author
 */
/**
 * server: {        
    * api: "Digi-Billing",        
    * hostname: req.protocol + "://" + req.get("host"),        
    * endPoint: req.originalUrl,        
    * environment: process.env.MODE || "development",        
    * version: process.env.VERSION || "1.0.0",        
    * clientIp: req.ip,        
    * referer: req.headers.referer,        
    * timestamp: Date.now()      
 * },status: status, // integer: HTTP code      
    * success: success, // boolean      
    * payload: payload // mixed: IF success - data | ELSE - error message    
 * }
 */
namespace App\Lib;

trait Response {

    /**
     * Response data key
     *
     * @var string
     */
    protected $dataKey = 'payload';

    /**
     * Success data key.
     *
     * @var string
     */
    protected $successKey = "success";
    
    /**
     * 
     * @param array $data
     * @return array
     */
    public function successResponse($data, $code = 200) {
        return response()->json([
                    'server' => $this->serverResponse(),
                    'status' => '200',
                    $this->successKey => true,
                    $this->dataKey => $data,
        ]);
    }

    /**
     * 
     * @param array $data
     * @return array
     */
    public function exceptionResponse(\Exception $exception, $code = 403) {
        $ex = [
            'server' => $this->serverResponse(),
            $this->successKey => false,
            $this->dataKey => env('DEBUG_MODE') == 1 
                ? $exception->getMessage().'::File::'.$exception->getFile().'::Line::'.$exception->getLine()
                : $exception->getMessage(),
            'status' => $exception->getCode(),
            //'exception' => ''
        ];

        if (config('settings.exception_debug') == 1) {
            $ex['exception'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'code' => $exception->getCode(),
                'trace' => $exception->getTraceAsString()];
        }
        
        //return response()->json($ex, $exception->getCode() == 0 ? 200 : $exception->getCode());
        return response()->json($ex, $exception->getCode() == 0 ? 200 : 200);
    }

    /**
     * 
     * @param array $data
     * @return array
     */
    public function errorResponse($error, $code = 403) {
        return response()->json([
                'server' => $this->serverResponse(),
                $this->successKey => false,
                $this->dataKey => $error,
                'status' => 200
        ], 200);
    }
    
    /**
     * 
     * @param type $params
     * @param type $code
     * @return type
     */
    public function validationErrorResponse($errors, $code = 200, $msg = 'Invalid Form Data!'){
        return response()->json([
            'server' => $this->serverResponse(),
            $this->successKey => false,
            'form_errors' => $errors,
            $this->dataKey => $msg,
            'status' => 200
        ], 200);
    }
    
    public function emptyResponse($errors, $code = 200){
        return response()->json([
            'server' => $this->serverResponse(),
            $this->successKey => false,
            'form_errors' => $errors,
            $this->dataKey => "",
            'status' => 200
        ], 200);
    }

    /**
     * Server Json Array
     *
     * @return array
     */
    protected function serverResponse(){
        return [
            'api' => config('settings.app_name'),
            'hostname' => $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1' ? 'http://'.$_SERVER['HTTP_HOST'] : $_SERVER['SERVER_PROTOCOL'].$_SERVER['HTTP_HOST'],
            'endPoint' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "",
            'environment' => env('APP_ENV'),
            'version' => '1.0',
            'clientIp' => getenv('HTTP_CLIENT_IP')?:
                            getenv('HTTP_X_FORWARDED_FOR')?:
                            getenv('HTTP_X_FORWARDED')?:
                            getenv('HTTP_FORWARDED_FOR')?:
                            getenv('HTTP_FORWARDED')?:
                            getenv('REMOTE_ADDR'),
            'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "",
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }
    
}