<?php
    require_once("dbInteractions.php");
    /**
     * Parses the filetype of a file
     * @author Ignacy Debicki
     * @param  object $file File to get filetype of
     * @return string The filetype of the file
     */
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
    /**
     * Checks the file size of a file to be below 2 MiB
     * @author Ignacy Debicki
     * @param object $file File
     */
    function check_file_size($file){
       $filesize = filesize($file['tmp_name'][0]);
        //allowed file size <= 2 MiB
        if ($filesize > 1024**2){
           throw new Exception("File larger than 1 MiB",603);
        }
    }
    /**
     * Generates a random alphanumeric 10 character name
     * @author Ignacy Debicki
     * @param integer $length Length of name to produce. Defualt 10
     * @return string  Random string generated
     */
    function generate_filename($length){
        if (!$length){
            $length = 10;
        }
         $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMONPQRSTUVWXYZ0123456789';
         $filename = '';
         for ($i = 0; $i < $length; $i++) {
              $filename .= $characters[rand(0, strlen($characters) - 1)];
         }
        return $filename;
    }
    /**
     * Uploads a file and assigns it to the user / save
     * @author Ignacy Debicki
     * @param  object  $file           File to upload
     * @param  string  $targetLocation Where to upload to. Either "avatars" or "mapThumbnails"
     * @return boolean If succesfull
     */
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
                $filename = generate_filename(10) . "." . $fileType;
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
                if ($targetLocation == "avatars"){
                    return set_avatar($filename);
                }else{
                    return $filename;
                }
                
            } else {
                throw new Exception("Failed to move file",604);
            }
        }catch(Exception $e){
            throw $e;   
        }
    }
    /**
     * Scales and crops a file to a width and height of 250 px
     * @author Ignacy Debicki
     * @param object $file File to scale
     * @param string $ext  Extenstion of file
     */
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
/**
 * Removes an avatar file from the directory
 * @author Ignacy Debicki
 * @param string $file Name of file
 */
function remove_avatar_file($file){
    try{
        if ($file!=NULL || $file!=""){
            if (!unlink("/var/www/html/ca/restricted/images/avatars/" . $file)){
                throw new Exception("Failed to delete file",607);
            }
        }
    } catch(Exception $e){
        throw $e;
    }  
}
/**
 * Remove a map thumbnail from the directory
 * @author Ignacy Debicki
 * @param string $file Name of file to remove
 */
function remove_thumbnail_file($file){
        try{
        if ($file!=NULL || $file!=""){
            if (!unlink("/var/www/html/ca/restricted/images/mapThumbnails/" . $file)){
                throw new Exception("Failed to delete file",607);
            }
        }
    } catch(Exception $e){
        throw $e;
    }  
}

/**
 * Remove a map thumbnail from the directory
 * @author Ignacy Debicki
 * @param string $file Name of file to remove
 */
function remove_save_file($file){
        try{
        if ($file!=NULL || $file!=""){
            if (!unlink("/var/www/private/ca/saves/" . $file)){
                throw new Exception("Failed to delete file",607);
            }
        }
    } catch(Exception $e){
        throw $e;
    }  
}
?>