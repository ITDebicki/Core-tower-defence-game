window.login=(function(){
    
    var username = null;
    var userAvatar = null;
    var isLoggedIn = false;
    
    function inspectIsOpen()
    {
        console.profile(); 
        console.profileEnd(); 
        if (console.clear) console.clear();
        return console.profiles.length > 0;
    }
    
    function fetchUserAvatar(callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: {"action":"fetchAvatar", "json":"{}"}
        }).done(function(response){
            console.log(response);
            var file = null;
             if (response["success"]==true){
                var file = response["data"];
            }else{
                alert(response["error"],response["errorCode"]);   
            } 
            if (file == null || file == ""){
                file = "defaultAvatar.png";
            }
            userAvatar = {"file":"restricted/images/avatars/" + file,"expiry":Date.now()+10000*60} //check again in ten minutes
            if (!(callback === undefined)){
                callback(userAvatar.file);   
            }
        });

   }
    
    function login(user,pass,callback){
        if (validateUser(user) && validatePassword(pass)){
            $.ajax({
                url: 'restricted/login.php',
                type: 'POST',
                method: 'POST',
                dataType: "json",
                data: {"type":"login", "user":user, "password":pass},
                success:function(response){
                    if (response["success"]==true){
                        isLoggedIn = true;
                        username = response["username"]
                        if (!(callback === undefined)){
                            callback(true);   
                        }
                    }else{
                        isLoggedIn = false;
                        if (!(callback === undefined)){
                            callback(false,response["error"],response["errorCode"]);   
                        }
                    }
                }
            });
        }else{
            if (!(callback === undefined)){
                callback(false);   
            }
            return false;
        }
    }
    
    function logout(callback){
        $.ajax({
                url: 'restricted/logout.php',
                dataType: "json",
                success: function(response){ 
                    if (response["success"]==true){
                        isLoggedIn = false;
                    }
                    if (!(callback === undefined)){
                            callback(response["success"],response["error"],response["errorCode"]);   
                    }
                }
            });
    }
    
    function createAccount(user,pass,passR,email,callback){
        if (validateAccountCreationDetails(username,password,passwordR,email)){
            $.ajax({
                url: 'restricted/login.php',
                type: 'POST',
                method: 'POST',
                dataType: "json",
                data: { "type": "create", "user":username, "password":password, "passwordR":passwordR, "email":email},
                success: function(response){ 
                    if (response["success"]==true){
                        if (!(callback === undefined)){
                            callback(true);   
                        }
                    }else{
                        if (!(callback === undefined)){
                            callback(false,response["error"],response["errorCode"]);   
                        }
                    }
                }
            });
        }
    }
    
    function validateAccountCreationDetails(username,password,passwordR,email){
        //check that username does not breach rules
        if (validateUser(username)==false){
            return false;   
        }
        //check that password does not breach rules
        if (validatePassword(password)==false || validatePassword(passwordR)==false || password != passwordR){
            return false;
        }
        //check email does not breach rules
        if (/^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/.test(email)==false){
            return false;
        }
        return true;
    }
    
    function validateUser(username){
        if (username == "" || /^[a-zA-Z0-9]+$/.test(username)==false){
            return false;
        }
        return true;
    }
    
    function validatePassword(password){
        if(/\s/g.test(password)||password==""){
            return false;   
        }
        return true;
    }
    
    function fetchNotifications(timestamp,limit,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "fetchNotifications", "json":'{"fromDate":' + timestamp +',"limit":' + limit +'}'},
            success: function(response){ 
                if (response["success"]==true){
                    var responseData = response["data"];
                    callback(true,responseData);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            }
        });
    }
    
    function markAsRead(msgIds,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "markAsRead", "json":'{"msgIds":'+ JSON.stringify(msgIds)+'}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            }
        });
    }
    
    function setScore(score,map,callback){
        
    }
    
    function uploadAvatar(formdata,callback){
        $.ajax({
            url: "restricted/performAction.php",
            method: "POST",
            data: formdata,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function (response) {
                if(response["success"]==true){
                    //need to set new userAvatar
                    userAvatar=null;
                }
                callback(response["success"],response["error"],response["errorCode"]);
            }
        });
    }
    
    function deleteAvatar(callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: {"action":"removeAvatar", "json":"{}"},
            success:function(response){
                if(response["success"]==true){
                    //need to set new userAvatar
                    userAvatar=null;
                }
                callback(response["success"],response["error"],response["errorCode"]);
            }
        }); 
    }
    
    function deleteAccount(callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: {"action":"deleteAccount", "json":"{}"},
            success:function(response){
                callback(response["success"],response["error"],response["errorCode"]);
            }
        });  
    }
    
    return{
        login:function(user,pass,callback){
            login(user,pass,callback);
        },
        userAvatar: function(callback){
            //check 10 minute cache first
            if (userAvatar.hasOwnProperty("file")){
                if (userAvatar.expiry < Date.now()){
                    callback(userAvatar.file);
                    return;
                }
            }
            fetchUserAvatar(callback);
        },
        username: function(){
            return username;
        },
        logout: function(callback){
            logout(callback);
        },
        createAccount(user,pass,passR,email,callback){
            createAccount(user,pass,passR,email,callback);
        },
        isLoggedIn: function(){
            return isLoggedIn;
        },
        fetchNotifications: function(timestamp,limit,callback){
            fetchNotifications(timestamp,limit,callback);
        },
        markAsRead: function(msgIds,callback){
            markAsRead(msgIds,callback);
        },
        setScore: function(score,map,callback){
            if (inspectIsOpen()){
                console.error("Stop trying to cheat!!");
                //possibly implement a strikes system?
                return false;
            }
            
        },
        uploadAvatar: function(formData,callback){
            uploadAvatar(formData,callback);
        },
        deleteAvatar: function(callback){
            deleteAvatar(callback);
        },
        deleteAccount: function(callback){
            deleteAccount(callback);
        }
    }
})();
