<?php

namespace Idea\Http\Response;

use Idea\Http\JsonResponse;

class ApiResponse extends JsonResponse
{
    public function __construct(mixed $data = null, int $status = 200, mixed $errors = [], array $headers = [], bool $json = false)
    {
        parent::__construct($data, $status, $headers, $json);
        $data = ['status' => $this->isSuccessful() ? 'success' : 'error', 'data' => $data, 'errors' => $errors];
        $this->setData($data);
    }
}
