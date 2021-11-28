<?php


namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends AbstractController
{

    /**
     * @var integer HTTP status code - 200 (OK) by default
     */
    protected $statusCode = 200;

    /**
     * Gets the value of statusCode.
     *
     * @return integer
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Sets the value of statusCode.
     *
     * @param integer $statusCode the status code
     *
     * @return self
     */
    protected function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Returns a JSON response
     *
     * @param array $data
     *
     * @return JsonResponse
     */
    public function response($data)
    {
        return new JsonResponse($data, $this->getStatusCode());
    }

    /**
     * Sets an error message and returns a JSON response
     *
     * @param string $errors
     * @return JsonResponse
     */
    public function respondWithErrors($errors)
    {
        $data = [
            'error' => true,
            'message' => $errors,
        ];

        return new JsonResponse($data, $this->getStatusCode());
    }

    /**
     * Sets an data  and returns a JSON response
     *
     * @param array $data
     * @param string $type
     * @return JsonResponse
     */
    public function respond($data = [], $type = 'users')
    {
        $datas = [
            'error' => false,
            $type => $data,
        ];

        return new JsonResponse($datas, $this->getStatusCode());
    }


    /**
     * Sets an error message and returns a JSON response
     *
     * @param $message
     * @param $token
     * @param $refreshToken
     * @param $created_At
     * @return JsonResponse
     */
    public function respondWithSuccess($message, $token, $refreshToken, $created_At)
    {
        $data = [
            'error' => false,
            'message' => $message,
            'tokens' => [
                'token' => $token,
                'refresh-token' => $refreshToken,
                'createdAt' => $created_At
            ]
        ];

        return new JsonResponse($data, $this->getStatusCode());
    }

    /**
     * Sets an error message and returns a JSON response
     *
     * @param $message
     * @return JsonResponse
     */
    public function respondSuccess($message)
    {
        $data = [
            'error' => false,
            'message' => $message
        ];

        return new JsonResponse($data, $this->getStatusCode());
    }


    /**
     * Returns a 401 Unauthorized http response
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    public function respondUnauthorized($message)
    {
        return $this->setStatusCode(409)->respondWithErrors($message);
    }

    /**
     * Returns a 422 Unprocessable Entity
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    public function respondValidationError($message)
    {
        return $this->setStatusCode(401)->respondWithErrors($message);
    }

    /**
     * Returns a 404 Not Found
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    public function respondNotFound($message)
    {
        return $this->setStatusCode(404)->respondWithErrors($message);
    }

    /**
     * Returns a 201 Created
     *
     * @param array $data
     *
     * @return JsonResponse
     */
    public function respondCreated($data = [])
    {
        return $this->setStatusCode(201)->response($data);
    }

    /**
     * Returns a 200 fecth
     *
     * @param array $data
     * @param string $type
     * @return JsonResponse
     */
    public function respondFecthed($data = [], $type = 'users')
    {
        return $this->setStatusCode(200)->respond($data, $type);
    }

    /**
     * Returns a 200 delete
     *
     * @param $message
     * @return JsonResponse
     */
    public function respondDeleted($message)
    {
        return $this->setStatusCode(200)->respondSuccess($message);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function transformJsonBody(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $request;
        }

        $request->request->replace($data);

        return $request;
    }


}