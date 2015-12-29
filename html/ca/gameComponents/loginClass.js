window.login=(function(){
    
    var username = null;
    var userAvatarCache = [];
    var isLoggedIn = false;
    
    function inspectIsOpen()
    {
        console.profile(); 
        console.profileEnd(); 
        if (console.clear) console.clear();
        return console.profiles.length > 0;
    }
    
    function fetchAvatar(user,callback){
        if (user==""){
            if(userAvatarCache[username]){
                if(userAvatarCache[username].hasOwnProperty("file")){
                    if (userAvatarCache[username].expiry>Date.now() && userAvatarCache[username].file){
                        callback(userAvatarCache[username].file,username);
                        return;
                    }
                }
            }
        }
        if(userAvatarCache[user]){
            if(userAvatarCache[user].hasOwnProperty("file")){
                    if (userAvatarCache[user].expiry>Date.now() && userAvatarCache[user].file){
                        callback(userAvatarCache[user].file,user);
                        return;
                    }
            }
        }
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: {"action":"fetchAvatar", "json":'{"user":"'+user+'"}'},
            error:logError,
            success:function(response){
                var file = null;
                if (response["success"]==true){
                    var file = response["data"];
                }else{
                    alert(response["error"],response["errorCode"]);   
                } 
                if (file == null || file == ""){
                    file = "defaultAvatar.png";
                }
                file = "restricted/images/avatars/" + file;
                var userAvatar = {"file":file,"expiry":Date.now()+10000*60} //check again in ten minutes
                //cache
                if (user==""){
                    user=username;
                }
                userAvatarCache[user]=userAvatar;
                callback(userAvatar.file,user);
            }
        });
    }
    
    function login(user,pass,callback){
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
            },
            error:logError
        });
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
            },
            error:logError
        });
    }
    
    function createAccount(user,pass,passR,email,callback){
        if (validateAccountCreationDetails(user,pass,passR,email)){
            $.ajax({
                url: 'restricted/login.php',
                type: 'POST',
                method: 'POST',
                dataType: "json",
                data: { "type": "create", "user":user, "password":pass, "passwordR":passR, "email":email},
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
                },
                error:logError
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
            },
            error:logError
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
            },
            error:logError
        });
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
            },
            error:logError
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
            },
            error:logError
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
                if (response["success"]==true){
                    isLoggedIn=false;
                    username=null;
                    userAvatar=null;
                }
                callback(response["success"],response["error"],response["errorCode"]);
            },
            error:logError
        });  
    }
    
    function getFriends(user,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "getFriends", "json":'{"user":"' + user +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    var responseData = response["data"];
                    callback(true,responseData);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    
    function acceptFriendRequest(userFrom,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "acceptFriendRequest", "json":'{"userFrom":"' + userFrom +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    
    function refuseFriendRequest(userFrom,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "refuseFriendRequest", "json":'{"userFrom":"' + userFrom +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    
    function deleteFriendRequest(userTo,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "deleteFriendRequest", "json":'{"userTo":"' + userTo +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    
    function sendFriendRequest(userTo,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "createFriendRequest", "json":'{"userTo":"' + userTo +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    
    function blockUser(user,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "blockUser", "json":'{"user":"' + user +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    
    function markFRAsRead(userFrom,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "markFRAsRead", "json":'{"userFrom":"' + userFrom +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    
    function unblockUser(user,callback){
         $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "unblockUser", "json":'{"user":"' + user +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    
    function deleteFriend(user,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "deleteFriend", "json":'{"user":"' + user +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    
    function getFriendRequests(callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "getFriendRequests", "json":'{}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true,response["data"]);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    
    function getSentFriendRequests(callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "getSentFriendRequests", "json":'{}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true,response["data"]);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    
    function getBlockedUsers(callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "getBlockedUsers", "json":'{}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true,response["data"]);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    
    function getExactUsers(needle,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "getExactUsers", "json":'{"needle":"'+needle+'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true,response["data"]);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    
    function getAllUsers(needle,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "getAllUsers", "json":'{"needle":"'+needle+'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true,response["data"]);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    
    function setScore(score,map,callback){
        
    }
    
    return{
        login:function(user,pass,callback){
            login(user,pass,callback);
        },
        checkCookie: function(callback){
            login("","",callback);
        },
        userAvatar: function(callback){
            fetchAvatar("",callback);
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
        },
        fetchAvatar: function(user,callback){
            fetchAvatar(user,callback);
        },
        getUserFriends: function(callback){
            return getFriends("",callback);
        },
        getFriends: function(user,callback){
            if(!user){
                user=""
            }
            return getFriends(user,callback);
        },
        acceptFriendRequest: function(userFrom,callback){
            acceptFriendRequest(userFrom,callback);
        },
        refuseFriendRequest: function(userFrom,callback){
            refuseFriendRequest(userFrom,callback);
        },
        deleteFriendRequest: function(userTo,callback){
            deleteFriendRequest(userTo,callback);
        },
        sendFriendRequest: function(userTo,callback){
            sendFriendRequest(userTo,callback);
        },
        markFRAsRead: function(userFrom,callback){
            markFRAsRead(userFrom,callback);
        },
        blockUser: function(user,callback){
            blockUser(user,callback);
        },
        unblockUser: function(user,callback){
            unblockUser(user,callback);
        },
        deleteFriend: function(user,callback){
            deleteFriend(user,callback);
        },
        getFriendRequests: function(callback){
            getFriendRequests(callback);
        },
        getSentFriendRequests: function(callback){
            getSentFriendRequests(callback);
        },
        getBlockedUsers: function(callback){
            getBlockedUsers(callback);
        },
        getAllUsers: function(needle,callback){
            getAllUsers(needle,callback);
        },
        getExactUsers: function(needle,callback){
            getExactUsers(needle,callback);
        }
    }
})();
