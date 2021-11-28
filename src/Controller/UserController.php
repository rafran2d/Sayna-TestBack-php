<?php


namespace App\Controller;


use App\Repository\UserRepository;
use App\Services\SaynaTokenStorage;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserController extends ApiController
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;
    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var JWSProviderInterface
     */
    protected $jwsProvider;

    /**
     * AuthController constructor.
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param JWSProviderInterface $jwsProvider
     */
    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository, JWSProviderInterface $jwsProvider)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->jwsProvider = $jwsProvider;
    }

    public function getAllUsers($token): JsonResponse
    {
        $jws = new SaynaTokenStorage($token, $this->jwsProvider);

        if ($jws->isInvalid()) {
            return $this->respondValidationError("Le token envoyer n'est pas conforme");
        }

        if (!$jws->isVerified()) {
            return $this->respondValidationError("Le token envoyer n'existe pas");
        }

        if ($jws->isExpired()) {
            return $this->respondValidationError("Votre token n'est plus valide, veuillez le réinitialiser");
        }

        $listUsers = $this->userRepository->findAllUser();

        return $this->respondFecthed($listUsers);
    }

    public function getOneUser($token): JsonResponse
    {
        $jws = new SaynaTokenStorage($token, $this->jwsProvider);

        if ($jws->isInvalid()) {
            return $this->respondValidationError("Le token envoyer n'est pas conforme");
        }

        if (!$jws->isVerified()) {
            return $this->respondValidationError("Le token envoyer n'existe pas");
        }

        if ($jws->isExpired()) {
            return $this->respondValidationError("Votre token n'est plus valide, veuillez le réinitialiser");
        }

        $email = $jws->getPayload()['username'];

        $user = $this->userRepository->findOneByEmail($email);
        $user['dateNaissance'] = $user['dateNaissance']->format('d/m/Y');
        $user['createdAt'] = $user['createdAt']->format('d/m/Y H:i:s');

        return $this->respondFecthed($user, 'user');
    }

    public function updateUser($token, Request $request): JsonResponse
    {
        $jws = new SaynaTokenStorage($token, $this->jwsProvider);

        if ($jws->isInvalid()) {
            return $this->respondValidationError("Le token envoyer n'est pas conforme");
        }

        if (!$jws->isVerified()) {
            return $this->respondValidationError("Le token envoyer n'existe pas");
        }

        if ($jws->isExpired()) {
            return $this->respondValidationError("Votre token n'est plus valide, veuillez le réinitialiser");
        }

        $email = $jws->getPayload()['username'];

        $request = $this->transformJsonBody($request);

        $firstname = $request->get('firstname');
        $lastname = $request->get('lastname');
        $dateNaissance = $request->get('date_naissance');
        $sexe = $request->get('sexe');

        if (empty($firstname) && empty($lastname) && empty($dateNaissance) && empty($sexe)){
            return $this->respondValidationError("Aucun données n'a été envoyée");
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (isset($firstname) && $firstname != '') {
            $user->setFirstname($firstname);
        }
        if (isset($lastname) && $lastname != '') {
            $user->setLastname($lastname);
        }
        if (isset($sexe) && $sexe != '') {
            $user->setSexe($sexe);
        }
        if (isset($dateNaissance) && $dateNaissance != '') {
            $user->setDateNaissance(new \DateTime($dateNaissance));
        }
        $user->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->respondSuccess("L'utilisateur a été modifiée avec succès");
    }

}