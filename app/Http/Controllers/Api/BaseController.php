<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class BaseController extends Controller
{
    public function sendResponse($data, $message = null, $code = 200)
    {
        $response = [
            'success' => true,
            'data'    => $data,
        ];

        if (!is_null($message)) {
            $response['message'] = $message;
        }

        return response()->json($response, $code);
    }

    public function sendError($message, $errors = [], $code = 422)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}