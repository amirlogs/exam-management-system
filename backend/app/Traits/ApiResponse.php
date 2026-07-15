<?php

namespace App\Traits;

trait ApiResponse
{
    public function success($data, $message = null, int $code = 200)
    {
        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'data' => $data,
                'errors' => null,
            ],
            $code,
        );
    }

    public function error(
        $errors,
        string $message = 'An error occurred',
        int $code = 400,
    ) {
        return response()->json(
            [
                'success' => false,
                'message' => $message,
                'data' => null,
                'errors' => $errors,
            ],
            $code,
        );
    }
}
