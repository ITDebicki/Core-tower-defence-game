<?php
    require_once("dbInteractions.php");
    function get_filetype($file){
        $info = getimagesize($file['tmp_name'][0]);
        if ($info === FALSE) {
           throw new Exception("Unable to determine file type",602);
        }
        switch($info[2]){
            case IMAGETYPE_GIF:
                return "gif";
                break;
            case IMAGETYPE_JPEG:
                return "jpeg";
                break;
            case IMAGETYPE_PNG:
                return "png";
                break;
            default:
                throw new Exception("File is not an image",601);  
                break;
        }
        
    }

    function check_file_size($file){
       $filesize = filesize($file['tmp_name'][0]);
        //allowed file size <= 1 MiB
        if ($filesize > 1024**2){
           throw new Exception("File larger than 1 MiB",603);
        }
    }
    
    function generate_filename(){
         $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMONPQRSTUVWXYZ0123456789';
         $filename = '';
         for ($i = 0; $i < 10; $i++) {
              $filename .= $characters[rand(0, strlen($characters) - 1)];
         }
        return $filename;
    }

    function upload_file($file,$targetLocation){
        try{
            $target_dir = "/var/www/html/ca/restricted/images/";
            $target_dir .= $targetLocation . "/";
            //check upload was succesfull
            if ($file['error'][0] !== UPLOAD_ERR_OK) {
               throw new Exception("Upload failed with error code " . $file['error'][0], 600);
            }
            //check file type
            $fileType = get_filetype($file);
            //check file size
            check_file_size($file);
            //generate unique filename
            $uniqueFilename = false;
            $filename=null;
            $repetitions = 0;
            do{
                $filename = generate_filename() . "." . $fileType;
                $uniqueFilename = !(file_exists($target_dir . $filename));
                $repetitions++;
            }while($uniqueFilename==false && $repetitions < 5);
            if ($uniqueFilename==false){
                throw new Exception("Unable to generate unique file name",605);   
            }
            
            $target_file = $target_dir . $filename;
            //move file to correct location
            if (move_uploaded_file($file['tmp_name'][0], $target_file)) {
                //set location in database
                scale_file($target_file,$fileType);
                return set_avatar($filename);
            } else {
                throw new Exception("Failed to move file",604);
            }
        }catch(Exception $e){
            throw $e;   
        }
    }
    
    function scale_file($file,$ext){
        try{
            $height = 250;
            $width = 250;
            $img = new imagick($file);
            $img->setImageFormat($ext);
            $img->scaleImage($width, $height, true);
            $img->cropImage($width, $height, 0, 0);
            $img->writeImage($file);
        }catch(Exception $e){
            throw new Exception("Failed to scale image",606,$e);
        }
    }
?>