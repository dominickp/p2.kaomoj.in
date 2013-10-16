<?php

namespace Dominick\RoommateBundle\Controller;

// Stuff for DB insert
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dominick\RoommateBundle\Entity\User;
use Dominick\RoommateBundle\Entity\Apartment;
use Symfony\Component\HttpFoundation\Response;

// Login/Security
use Symfony\Component\Security\Core\SecurityContext;

// Forms
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller
{
    public function indexAction()
    {
        return $this->render('DominickRoommateBundle:Default:index.html.twig', array());
    }
    public function registerAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = new User();

        // This is how you auto fill form data
        // $task->setTask('Write a blog post');

        $form = $this->createFormBuilder($user)
            ->add('username', 'email')
            ->add('fullname', 'text')
            ->add('password', 'repeated', array(
                'first_name'  => 'password',
                'second_name' => 'confirm',
                'type'        => 'password',
            ))
            ->add('save', 'submit')
            ->getForm();

        // If initially loading the page, handleRequest() recognizes that the form was not submitted and does nothing.
        // When the user submits the form, handleRequest() recognizes this and immediately writes the submitted data
        // back into the task and dueDate properties of the $task object.
        $form->handleRequest($request);

        // If the form is valid, we should be submitting the data, right?
        if ($form->isValid()) {
            $user = $form->getData();
            // Forget about that unencrypted password the silly person gave you
            unset($user->plainPassword);
            // Grab the security settings for this class from security.yml
            $factory = $this->get('security.encoder_factory');
            // Get the encoder
            $encoder = $factory->getEncoder($user);
            // Encrypt, then set the password. Salt is generated in the User entity.
            $user->setPassword($encoder->encodePassword($user->getPassword(), $user->getSalt()));

            // SET ROLE SOMEHOW
            // $user->getRoles()->addRole('ROLE_USER');
        //    $user->getRoles();
        //    $user->addRole('ROLE_USER');
        //    print_r($user->getRoles());



            // Save the new row you created when you initialized User
            $em->persist($user);
            // Fire!
            $em->flush();

            // You did a good job, billy.
            return $this->redirect($this->generateUrl('dominick_roommate_loggedin'));
        }

        return $this->render('DominickRoommateBundle:User:register.html.twig', array(
            'form' => $form->createView(),
        ));
    }
    public function loginAction(Request $request)
    {
    /*
        $em = $this->getDoctrine()->getManager();
        $user = new User();

        $form = $this->createFormBuilder($user)
            ->add('username', 'email')
            ->add('password', 'password')
            ->add('Login', 'submit')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {

        }

        return $this->render('DominickRoommateBundle:User:login.html.twig', array(
            'form' => $form->createView(),
        ));
        */
        $request = $this->getRequest();
        $session = $request->getSession();

        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
        }

        return $this->render('DominickRoommateBundle:User:login.html.twig', array(
            // last username entered by the user
            'last_username' => $session->get(SecurityContext::LAST_USERNAME),
            'error'         => $error,
        ));
    }
    public function apartmentAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $apt = new Apartment();

        // This is how you auto fill form data
        // $task->setTask('Write a blog post');

        $form = $this->createFormBuilder($apt)
            ->add('nickname', 'text')
            ->add('address1', 'text')
            ->add('address2', 'text')
            ->add('city', 'text')
            ->add('state', 'text')
            ->add('zip', 'text')

            ->add('save', 'submit')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $apt = $form->getData();

            $em->persist($apt);
            $em->flush();

            // Attempt to get the ID
            $aptId = $apt->getId();

            // Load up the user ID so I can inject the apartment ID into the user's row
            $currentUser = $this->get('security.context')->getToken()->getUser();
            $currentUserId = $currentUser->getId();
            // Use the entity manager to get my User entity and find the user of the ID I have
            $user = $em->getRepository('DominickRoommateBundle:User')->find($currentUserId);

            if (!$user) {
                // I'll have to figure out how to properly throw an error later. But if no user can be found with that ID, then this should be an error.
            }

            //$user = new User();
            $user->setApartmentId($aptId);

            $em->persist($user);
            $em->flush();

            // Send to the apartment overview page, now that the apartment has been created an tied to them.
            return $this->redirect($this->generateUrl('dominick_roommate_apartmenthome'));
        }
        return $this->render('DominickRoommateBundle:User:register.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
