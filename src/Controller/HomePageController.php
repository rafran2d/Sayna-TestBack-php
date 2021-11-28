<?php


namespace App\Controller;


use Symfony\Component\HttpFoundation\JsonResponse;

class HomePageController extends ApiController
{
    public function homePage(): JsonResponse
    {
        return new JsonResponse("Envoi de la page index.html", 200);
    }

}