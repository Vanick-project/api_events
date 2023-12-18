<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
//use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;

class EventController extends AbstractController
{
    private $connection;
    public function __construct(Connection $connection) {
        $this->connection = $connection;
    }
    /**
     * @Route("/event", name="app_event")
     */
    public function index(MailerInterface $mailer, LoggerInterface $logger): Response
    {
        $emailList = [];
        $filePath = $this->getParameter('kernel.project_dir') . '/public/mail.txt';
        $filesystem = new Filesystem();
        if ($filesystem->exists($filePath)) {
            $fileContent = file_get_contents($filePath);
            $emailList = explode("\n", $fileContent);
        }    
        try{
            $sql = "select * from event_infos";
        $statement = $this->connection->executeQuery($sql); 
        $result = $statement->fetchAllAssociative();

            $sqlSum = "select count(*) from event_infos";
        $statement = $this->connection->executeQuery($sqlSum); 
        $resultSum = $statement->fetchOne();

            $sqlAck = "SELECT ACK FROM event_infos  ";
        $statement = $this->connection->executeQuery($sqlAck);
        $ackValue = $statement->fetchAllAssociative();

        // Envoi d'email
            //The sender mail is store inside mailer.yaml
            $email = (new TemplatedEmail())
                ->to('ve0q8wxqe@lists.mailjet.com')//to modify this the mail that send the mail to a list inside mailjet
                ->subject('Oracle connection exceeding')
                ->cc('djamenvanick@gmail.com')
                ->htmlTemplate('email/welcome.html.twig')// to modify
                ->context([
                    'listEvent' => $result,
                    'sumConnect' => $resultSum
                ]);
                foreach($ackValue as $value){
                    if($value['ACK']  == NULL || $value['ACK']  == '' )
                        { 
                            $sqlInsert = "UPDATE connection SET ACK = 'OUI' WHERE ACK IS NULL OR ACK = '' ";
                            $statement = $this->connection->executeQuery($sqlInsert);
                            $value = $statement->fetchOne();
                            // send mail
                            $mailer->send($email);                            
                            break;
                        } 
                    else return die;
                }    
                
                        

            //$mailer->send($email);
            
            // Rendu de la page avec les données
        return $this->render('email/welcome.html.twig', [
            'listEvent' => $result,
            'sumConnect' => $resultSum,
            'email' => $email 
        ]);
        $logger->debug("001A>>Controller: EventController. Method: index. Route : Email : Empty-> " . var_export(json_encode($result), true));
        
    }
    catch (\Throwable $e) {
        $errorMessage = $e->getMessage();
        
        // Afficher l'erreur ou la journaliser
        return $this->render('email/event.html.twig', [
            'controller_name' => 'EventController', 'message' =>  $errorMessage
        ]);
    }
}
}