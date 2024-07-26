<?php
namespace App\Controller;

use App\Entity\Customer;
use App\Form\Type\LoginType;
use App\Form\Type\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{

    public function index(AuthenticationUtils $authenticationUtils): Response
    {

        $form = $this->createForm(LoginType::class);

        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user/login.html.twig', [
             'form'          => $form,
             'last_username' => $lastUsername,
             'error'         => $error,
        ]);
      }

//    public function login(): Response
//    {
//
//        $form = $this->createForm(LoginType::class, $user);
//
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//
//            $user = $form->getData();
//
//            $em = $this->manager;
//            $em->persist($user);
//
//            $plaintextPassword = $user->password;
//            $hashedPassword = $passwordHasher->hashPassword(
//                $user,
//                $plaintextPassword
//            );
//            $user->setPassword($hashedPassword);
//
//            if ($user->remember_me){
//                //TODO setCookie(){ }
//            }
//
//            $em->flush();
//
//            return $this->redirectToRoute('user_home');
//        }
//    }
}