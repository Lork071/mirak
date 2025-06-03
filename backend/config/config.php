<?php
require_once 'database_config.php';
class config{

    public function __construct() {
        global $ServerName, $UserName, $Password, $DBName;
        $this->database_server_name = $ServerName;
        $this->database_user_name = $UserName;
        $this->database_password = $Password;
        $this->database_name = $DBName;

        $this->ticket_items= array(
            "money" => array(
                "meal"=> array(
                    "price" => $this->price_ticket_meal - $this->price_ticket_no_meal,
                    "sat_required" => true,
                    "enable_condition" => "check_meal_availability",
                    "error_message" => "meal_not_available",
                    "no_money" => "Bez jídla",
                ),
                "workshop"=> array(
                    "price" => 0,
                    "sat_required" => true,
                    "enable_condition" => "check_workshop_availability",
                    "error_message" => "workshop_not_available",
                    "no_money" => "NA",
                ),
                "fri_to_sat"=> array(
                    "price" => $this->price_one_night,
                    "sat_required" => true,
                    "enable_condition" => "check_accormondation_availability",
                    "error_message" => "accormodation_not_available_fri_to_sat",
                    "no_money" => 0,
                ),
                "sat_to_sun"=> array(
                    "price" => $this->price_one_night,
                    "sat_required" => true,
                    "enable_condition" => "check_accormondation_availability",
                    "error_message" => "accormodation_not_available_sat_to_sun",
                    "no_money" => 0,
                ),
            ),
        );
    }
    /*
    public $database_server_name = "md84.wedos.net";
    public $database_user_name = "a250867_wp";
    public $database_password = "8q89^HPa";
    public $database_name = "d250867_wp";
*/
    public $database_server_name;
    public $database_user_name;
    public $database_password;
    public $database_name;

    /* Database name */
    public $database_name_error = "errors";
    public $database_name_user_error = "user_errors";
    public $database_name_users = "users";
    public $database_name_permissions = "permissions";
    public $database_name_event = "mirak_test";
    public $database_name_emails = "emails";


    /*************************************
     * Global
     ************************************/
    public $debug = true;
    public $default_role = "participant";
    public $ticket_key = "e4e189e5-5737-47fb-8efc-1162ab126459";

    /*************************************
     * Access
     ************************************/
    public $token_expiration_sec = 3600;

    public $permissions_pages = array(
        'admin','users','all_users','admin_users','permissions','errors', 'all_participant','qrscanner'
    );

    public $permissions_operations = array(
        'delete_normal_user','delete_admin_user',
        'permission_user_update','change_rank_user',
        'scan_meal','read_all_participant','get_qr_reader_info','update_user_scanner','scan_registration_get_info'
    );

    public $scanner_options = array("scanner_registration","scanner_meal","scanner_admin");

    /*************************************
     * Price
     ************************************/
    public $price_ticket_meal = 300;
    public $price_ticket_no_meal = 200;
    public $price_ticket_only_friday = 100;
    public $price_ticket_no_pii = 200;
    public $price_one_night = 50;


    /*************************************
     * Ticket
     ************************************/
    public $url_ticket='/ticket';
    public $url_participant_data = "/app/participant";

    public $ticket_items;
    /*************************************
     * accormodation
     ************************************/
    public $accormodation_info = array(
        'female' => array(
            'friday_saturday'=> array(
                'max_count'=> 200,
                'waring_threshold'=> 20,
            ),
            'saturday_sunday'=> array(
                'max_count'=> 205,
                'waring_threshold'=> 20,
            ),
        ),
        'male' => array(
            'friday_saturday'=> array(
                'max_count'=> 400,
                'waring_threshold'=> 20,
            ),
            'saturday_sunday'=> array(
                'max_count'=> 204,
                'waring_threshold'=> 20,
            ),
        ),
    );

    /*************************************
     * food
     ************************************/

    public $meals = array(
        'Maso' => array(
            'id' => 'Maso',
            'title'=> 'meal_food1_title',
            'img'=> 'menu2.jpg',
            'alerg'=> array(1,2,7),
            'max_count'=> 600,
            'waring_threshold'=> 20,
        ),
        'Vege' => array(
            'id' => 'Vege',
            'title'=> 'meal_food2_title',
            'img'=> 'menu1.jpg',
            'alerg'=> array(1,2,7),
            'max_count'=> 618,
            'waring_threshold'=> 20,
        ),
        'Bez jídla' => array(
            'id' => 'Bez jídla',
            'title'=> 'meal_food2_title',
            'img'=> 'menu3.jpg',
            'alerg'=> array(1,2,7),
            'max_count'=> 159,
            'waring_threshold'=> 50,
        ),
    );

    public $workshops = array(
        'workshop1'=> array(
            'id' => 'workshop1',
            'title' => 'workshop1_title',
            'desc' => 'workshop1_desc',
            'img' => 'workshop1.jpg',
            'max_count' => 500,
            'waring_threshold'=> 50,
        ),
        'workshop2'=> array(
            'id' => 'workshop2',
            'title' => 'workshop2_title',
            'desc' => 'workshop2_desc',
            'img' => 'workshop1.jpg',
            'max_count' => 450,
            'waring_threshold'=> 50,
        ),
        'workshop3'=> array(
            'id' => 'workshop3',
            'title' => 'workshop2_title',
            'desc' => 'workshop2_desc',
            'img' => 'workshop1.jpg',
            'max_count' => 150,
            'waring_threshold'=> 50,
        ),
        'workshop4'=> array(
            'id' => 'workshop4',
            'title' => 'workshop2_title',
            'desc' => 'workshop2_desc',
            'img' => 'workshop1.jpg',
            'max_count' => 150,
            'waring_threshold'=> 50,
        ),
        'workshop5'=> array(
            'id' => 'workshop5',
            'title' => 'workshop5_title',
            'desc' => 'workshop5_desc',
            'img' => 'workshop1.jpg',
            'max_count' => 150,
            'waring_threshold'=> 50,
        )
    );


    
    /*************************************
     * language
     ************************************/
    public $lang_text = array(
        'cs'=> array(
            'email_otp_title' => 'Ověřovací kód',
            'email_otp_desc' => 'Pro dokončení akce využíjte následující ověřovací kód',
            'email_ticket_title' => 'Mírák vstupenka',
            'email_ticket_desc'=> 'Ahoj, děkujeme za tvoji registraci na akci Mírák. Pomocí tlačítka níže si můžeš vstupenku zobrazit a případně tuto registraci zrušit. Tuto vstupenku si ulož, protože ji budeš potřebovat na akci!',
            "email_ticket_important_info_title" => "Důležité informace:",
            "email_ticket_important_info_desc" => array("QR kód slouží pouze jako identifikace účastníka, nikoli QR platba.", "Platba je možná pouze v hotovosti přímo na akci!", "Platba v eurech je možná v aktuálním kurzi vždy zaokrouhlená na celé eura nahoru",),
            'best_regards'=> 'S pozdravem,',
            'mirak_team'=> 'Tým Mírák',
        ),
        'en'=> array(
            'email_otp_title' => 'Authentication code',
            'email_otp_desc' => 'Use the following verification code to complete the action',
            'email_ticket_title' => 'Mírák ticket',
            'email_ticket_desc'=> 'Hi, thank you for registering for the Mírák event. Using the button below, you can view your ticket and, if needed, cancel this registration. Please save this ticket as you will need it for the event!',
            "email_ticket_important_info_title" => "Important information:",
            'best_regards'=> 'Best Regards,',
            'mirak_team'=> 'Mírák Team',
        ),
        'sk'=> array(
            'email_otp_title' => 'Overovací kód',
            'email_otp_desc' => 'Na dokončenie akcie použite nasledujúci overovací kód',
            'email_ticket_title' => 'Mírák vstupenka',
            'email_ticket_desc'=> 'Ahoj, ďakujeme za tvoju registráciu na akciu Mírák. Pomocou tlačidla nižšie si môžeš zobraziť vstupenku a prípadne túto registráciu zrušiť. Túto vstupenku si ulož, pretože ju budeš potrebovať na akciu!',
            "email_ticket_important_info_title" => "Dôležité informácie:",
            'best_regards'=> 'S pozdravom,',
            'mirak_team'=> 'Tím Mírák',
        ),
    );

}

?>