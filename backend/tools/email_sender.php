<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once '../vendor/autoload.php';
require_once '../config/config.php';

class EmailSender {
    private $pass = 'qcqszukvsbqaotnv';
    private $address = 'mirak.infosystem@gmail.com'; 
    private $mail;

    public function __construct() {

        $this->mail = new PHPMailer(true);
        try{
            $this->mail->isSMTP();
            $this->mail->Host       = 'smtp.gmail.com;'; 
            $this->mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            $this->mail->SMTPAuth   = true;                            
            $this->mail->Username   = $this->address;
            $this->mail->Password   = $this->pass;
            $this->mail->SMTPSecure = 'tls';
            $this->mail->Port = 587;
            $this->mail->CharSet = "UTF-8";
            $this->mail->SetLanguage("cs", "phpmailer/language");
            $this->mail->Encoding='base64';   
            $this->mail->setFrom($this->address);
            $this->mail->isHTML(true);     
        }
        catch(Exception $e){
        }
        
    }

    public function send_otp($email, $code, $title, $description, $best_regards, $mirak_team){
        try{
            $this->mail->addAddress($email);
            $Email_contents = file_get_contents(filename: "../tools/email_templates/email_template_verification.html"); 
            $this->mail->Subject = mb_convert_encoding($title, "UTF-8");
            $Email_contents = str_replace("{email_title}", $title, $Email_contents);
            $Email_contents = str_replace("{email_description}", $description, $Email_contents);
            $Email_contents = str_replace("{verify_code}", $code, $Email_contents);
            $Email_contents = str_replace("{best_regards}", $best_regards, $Email_contents);
            $Email_contents = str_replace("{mirak_team}", $mirak_team, $Email_contents);
            $this->mail->Body = mb_convert_encoding($Email_contents, "UTF-8");
            $this->mail->send();
        }catch(Exception $e){

        }
    }

    public function send_email($email, $title, $description, $best_regards, $mirak_team){
        try{
            $this->mail->addAddress($email);
            $Email_contents = file_get_contents(filename: "../tools/email_templates/email_template.html"); 
            $this->mail->Subject = mb_convert_encoding($title, "UTF-8");
            $Email_contents = str_replace("{email_title}", $title, $Email_contents);
            $Email_contents = str_replace("{email_description}", $description, $Email_contents);
            $Email_contents = str_replace("{best_regards}", $best_regards, $Email_contents);
            $Email_contents = str_replace("{mirak_team}", $mirak_team, $Email_contents);
            $this->mail->Body = mb_convert_encoding($Email_contents, "UTF-8");
            $this->mail->send();
        }catch(Exception $e){

        }
    }


}
?>
