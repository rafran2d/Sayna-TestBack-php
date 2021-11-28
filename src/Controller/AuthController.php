<?php


namespace App\Controller;


use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends ApiController
{

    /**
     * @var JWTTokenManagerInterface
     */
    protected $JWTManager;
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;
    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var UserPasswordEncoderInterface
     */
    protected $encoder;


    /**
     * AuthController constructor.
     * @param JWTTokenManagerInterface $JWTManager
     * @param EntityManagerInterface $em
     * @param UserRepository $userRepository
     * @param UserPasswordEncoderInterface $encoder
     */
    public function __construct(JWTTokenManagerInterface $JWTManager, EntityManagerInterface $em, UserRepository $userRepository, UserPasswordEncoderInterface $encoder)
    {
        $this->JWTManager = $JWTManager;
        $this->entityManager = $em;
        $this->userRepository = $userRepository;
        $this->encoder = $encoder;
    }

    /**
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param AuthenticationSuccessHandler $successHandler
     * @return JsonResponse
     * @throws Exception
     */
    public function register(Request $request, ValidatorInterface $validator, AuthenticationSuccessHandler $successHandler): JsonResponse
    {
        $request = $this->transformJsonBody($request);
        $firstname = $request->get('firstname');
        $lastname = $request->get('lastname');
        $dateNaissance = $request->get('date_naissance');
        $sexe = $request->get('sexe');
        $password = $request->get('password');
        $email = $request->get('email');

        if (empty($firstname) || empty($password) || empty($email) || empty($lastname) || empty($dateNaissance) || empty($sexe)){
            return $this->respondValidationError("L'une ou plusieurs des donnees obligatoire sont maquantes");
        }

        $input = ['firstname' => $firstname, 'lastname' => $lastname, 'email' => $email, 'password' => $password];

        $pattern = "/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/";
        $constraints = new Assert\Collection([
            'firstname' => [new Assert\Length(['min' => 3]), new Assert\NotBlank],
            'lastname' => [new Assert\Length(['min' => 3]), new Assert\NotBlank],
            'email' => [new Assert\Email(), new Assert\notBlank],
            'password' => [new Assert\Regex($pattern)],
        ]);

        $violations = $validator->validate($input, $constraints);
        if (count($violations) > 0) {
            return $this->respondValidationError("L'une des données obligatoire ne sont pas conformes");
        }

        if($this->userRepository->findOneBy(['email' => $email])) {
            return $this->respondValidationError("Votre email n'est pas correct");
        }

        $user = new User();
        $user->setPassword($this->encoder->encodePassword($user, $password));
        $user->setEmail($email);
        $user->setLastname($lastname);
        $user->setFirstname($firstname);
        $user->setDateNaissance(new \DateTime($dateNaissance));
        $user->setSexe($sexe);
        $user->setCreatedAt(new \DateTime());
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $jwtUser = new JWTUser($email);

        $jwtResponse = json_decode($successHandler->handleAuthenticationSuccess($jwtUser)->getContent());


        return $this->respondWithSuccess("L'utilisateur a bien été crée avec succès" , $jwtResponse->token , $jwtResponse->refresh_token, $user->getCreatedAt()->format("d/m/Y H:i:s"));
    }


    /**
     * @param Request $request
     * @param AuthenticationSuccessHandler $successHandler
     * @return JsonResponse
     */
    public function login(Request $request, AuthenticationSuccessHandler $successHandler): JsonResponse
    {
        $request = $this->transformJsonBody($request);
        $password = $request->get('password');
        $email = $request->get('email');

        if (empty($password) || empty($email)) {
            return $this->respondValidationError("L'Email/password est manquant");
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->respondValidationError("Votre Email ou password est erroné");
        }

        $passwordCheck = $this->encoder->isPasswordValid($user, $password);

        if(!$passwordCheck) {
            $nbAttempt = (int)$user->getNumberAttempt();
            if($nbAttempt >= 3) {
                return $this->respondUnauthorized("Trop de tentative sur email $email - Veuillez patientez 1h");
            }

            $user->setNumberAttempt($nbAttempt+1);
            $user->setAttemptAt(new \DateTime());
            $user->setUpdatedAt(new \DateTime());
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            return $this->respondValidationError("Votre Email ou password est erroné");
        }

        if ($user->getAttemptAt() && $user->getAttemptAt()->diff(new \DateTime())->h < 1) {
            return $this->respondUnauthorized("Trop de tentative sur email $email - Veuillez patientez 1h");
        }

        $user->setNumberAttempt(0);
        $user->setAttemptAt(null);
        $user->setUpdatedAt(new \DateTime());
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $jwtUser = new JWTUser($email);

        $jwtResponse = json_decode($successHandler->handleAuthenticationSuccess($jwtUser)->getContent());

        return $this->respondWithSuccess("L'utilisateur a été authentifié succès" , $jwtResponse->token , $jwtResponse->refresh_token, $user->getCreatedAt()->format("d/m/Y H:i:s"));
    }

    /**
     * @param $token
     * @return JsonResponse
     */
    public function logout($token): JsonResponse
    {
        return $this->respondDeleted("L'utilisateur a été déconnecté succès");
    }

}