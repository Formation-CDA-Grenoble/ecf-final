<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Security\TokenAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class SecurityController extends AbstractController
{
    private $userRepository;
    private $authenticator;
    private $guardHandler;
    private $passwordEncoder;

    public function __construct(
        UserRepository $userRepository,
        TokenAuthenticator $authenticator,
        GuardAuthenticatorHandler $guardHandler,
        UserPasswordEncoderInterface $passwordEncoder
    )
    {
        $this->userRepository = $userRepository;
        $this->authenticator = $authenticator;
        $this->guardHandler = $guardHandler;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @Route("/login", methods={"POST"}, name="login")
     */
    public function login(Request $request)
    {
        $data = \json_decode($request->getContent());

        $username = $data->username;
        $password = $data->password;

        $user = $this->userRepository->findOneBy([ 'email' => $username ]);

        if (is_null($user)) {
            throw new NotFoundHttpException('User "' . $username . '" does not exist.');
        }

        if ($this->passwordEncoder->isPasswordValid($user, $password)) {
            $this->guardHandler->authenticateUserAndHandleSuccess(
                $user,          // the User object you just created
                $request,
                $this->authenticator, // authenticator whose onAuthenticationSuccess you want to use
                'main'          // the name of your firewall in security.yaml
            );
    
            return $this->redirectToRoute('user_get-current');
        } else {
            throw new UnauthorizedHttpException('', 'Invalid password for user ' . $username . '.');
        }
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout() { }

    /**
     * @Route("/", name="info")
     */
    public function info ()
    {
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
