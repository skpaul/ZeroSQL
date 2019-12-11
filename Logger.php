
<?php 

   
    class Logger
    {

        private $root_directory = "";

        //sub-directory is needed to prevent guess the log file path from hacker.
        private $sub_directory = "_logs";

        private $site_log_file_name = "site_logs.log";
        private $site_log_file_path = "";

        private $error_log_file_name = "error_logs.log";
        private $error_log_file_path = "";

        //Constructor this class.
        //If user provides values in this, it will call connect() method.
        //Otherwise, user have to call connect() method by himself.
        public function __construct($root_directory) {

            $this->root_directory = $root_directory;
            if (!file_exists($this->root_directory . "/" . $this->sub_directory)) {
                mkdir($this->root_directory . "/" . $this->sub_directory, 0777, true);
            }

            $this->site_log_file_path = $this->root_directory . "/" . $this->sub_directory . "/" . $this->site_log_file_name;
    
            if(!file_exists($this->site_log_file_path)){
                $handle = fopen($this->site_log_file_path, 'w') or die("Can't create file");
                fclose($handle);
            }

            $this->error_log_file_path = $this->root_directory . "/" . $this->sub_directory . "/" . $this->error_log_file_name;
    
            if(!file_exists($this->error_log_file_path)){
                $handle = fopen($this->error_log_file_path, 'w') or die("Can't create file");
                fclose($handle);
            }

            error_reporting( E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
            //ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
            //error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
            ini_set('log_errors', '1');
           // ini_set('display_errors', 1); //show errors in response stream.
           // ini_set('display_startup_errors', 1); //show errors in response stream.


            ini_set('error_log', $this->error_log_file_path);
        }
       
        private function _create_log($log_text){
            $bt = debug_backtrace();
            $caller = array_shift($bt);
            $path = $caller['file'];
            $file_name = basename($path); // $file is set to "file.php"
            $line_number = $caller['line'];
            
            $currentdatetime = new DateTime("now", new DateTimeZone('Asia/Dhaka'));
            $FormattedDateTime = $currentdatetime->format('d-m-Y h:i:s A');  //date('Y-m-d H:i:s');
            
            $final_log = $log_text . "\n";
            $final_log .= "File:$file_name, Line:$line_number, Datetime:$FormattedDateTime  " . "\n";
            $final_log .= "------------------------------------------------------------------------------------\n";

            //error_log($final_log . "\n",3, $this->root_directory . "/site_logs.log");
            //Default path to error log is /var/log/apache2/error.log

            
            file_put_contents($this->site_log_file_path, $final_log, FILE_APPEND | LOCK_EX );

            /*
                Use the "a" mode. It stands for append.

                $myfile = fopen("logs.txt", "a") or die("Unable to open file!");
                $txt = "user id date";
                fwrite($myfile, "\n". $txt);
                fclose($myfile);
            */
        }
       
        public function create_log($log_text){
            $this->_create_log($log_text);
        }

        public function CreateLog($log_text){
            $this->_create_log($log_text);
        }

        private function _clear_logs($file){
            file_put_contents($file, "");
            // $fh = fopen( 'filelist.txt', 'w' );
            // fclose($fh);
        }

        public function clear_site_log(){
            $this->_clear_logs($this->site_log_file_path);
        }

        public function ClearSiteLog(){
            $this->_clear_logs($this->site_log_file_path);
        }

        public function clear_site_logs(){
            $this->_clear_logs($this->site_log_file_path);
        }

        public function ClearSiteLogs(){
            $this->_clear_logs($this->site_log_file_path);
        }


        public function delete_site_logs(){
            $this->_clear_logs($this->site_log_file_path);
        }
        
        public function DeleteSiteLogs(){
            $this->_clear_logs($this->site_log_file_path);
        }

        public function delete_site_log(){
            $this->_clear_logs($this->site_log_file_path);
        }
        
        public function DeleteSiteLog(){
            $this->_clear_logs($this->site_log_file_path);
        }




        public function clear_error_log(){
            $this->_clear_logs($this->error_log_file_path);
        }

        public function ClearErrorLog(){
            $this->_clear_logs($this->error_log_file_path);
        }

        public function clear_error_logs(){
            $this->_clear_logs($this->error_log_file_path);
        }

        public function ClearErrorLogs(){
            $this->_clear_logs($this->error_log_file_path);
        }

        public function delete_error_logs(){
            $this->_clear_logs($this->error_log_file_path);
        }
        
        public function DeleteErrorLogs(){
            $this->_clear_logs($this->error_log_file_path);
        }

        public function delete_error_log(){
            $this->_clear_logs($this->error_log_file_path);
        }
        
        public function DeleteErrorLog(){
            $this->_clear_logs($this->error_log_file_path);
        }

        // public function ClearErrorLogs(){
        //     $this->_clear_logs($this->error_log_file_path);
        // }


        private function _read_file($file){
            $fp = fopen($file, "r");

            if(filesize($file) > 0){
                $content = fread($fp, filesize($file));
                $lines = explode("\n", $content);
                fclose($fp);
               
                foreach($lines as $newline){
                    echo ''.$newline.'<br>';
                }
            }
            else{
                echo "Hurray!! No log found.";
            }
        }

        public function read_site_logs(){
             $file = $this->site_log_file_path;
            // $content=file_get_contents($file);
            // $lines=explode("\n",$content);
            // foreach($lines as $newline){
            //     echo ''.$newline.'<br>';
            // }

            $this->_read_file($file);
        }

        public function read_site_log(){
            $this->_read_file($this->site_log_file_path);
        }

        public function ReadSiteLogs(){
            $this->_read_file($this->site_log_file_path);
        }

        public function ReadSiteLog(){
            $this->_read_file($this->site_log_file_path);
        }


        public function read_error_logs(){
            $file = $this->error_log_file_path;
           // $content=file_get_contents($file);
           // $lines=explode("\n",$content);
           // foreach($lines as $newline){
           //     echo ''.$newline.'<br>';
           // }

           $this->_read_file($file);
        }

       public function read_error_log(){
           $this->_read_file($this->error_log_file_path);
        }

       public function ReadErrorLogs(){
           $this->_read_file($this->error_log_file_path);
        }

       public function ReadErrorLog(){
           $this->_read_file($this->error_log_file_path);
        }


    } //<--class

?>