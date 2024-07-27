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
use Defuse\Crypto\Key;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LoginController extends AbstractController
{

    public function index(ParameterBagInterface $params, AuthenticationUtils $authenticationUtils): Response
    {

        $form = $this->createForm(LoginType::class);

        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
//        $key = $params->get('encryption_key');//Key::loadFromAsciiSafeString($params->get('encryption_key'));

//        $key = Key::createNewRandomKey()->saveToAsciiSafeString();

//        $baseKey = $params->get('encryption_key');//getenv('ENCRYPTION_KEY');
////        $rawKey = $baseKey->getRawBytes();
//        $key = Key::loadFromAsciiSafeString($baseKey);

        return $this->render('user/login.html.twig', [
             'form'          => $form,
             'last_username' => $lastUsername,
             'error'         => $error,
//            'key'           => $key,
        ]);
    }
}